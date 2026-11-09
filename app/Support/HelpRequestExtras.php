<?php

namespace App\Support;

use App\Models\HelpRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Supporting documents/photos and donation details (bank account, UPI) for help
 * requests: shared validation, input clean-up and file storage for the public
 * form and the admin create/edit forms.
 *
 * Callers wrap the row changes and saveFiles() in a DB transaction. New files are
 * removed again if saving fails; old files are deleted only after commit.
 */
class HelpRequestExtras
{
    public const PAYMENT_FIELDS = [
        'account_holder_name',
        'bank_account_number',
        'bank_ifsc',
        'bank_name',
        'bank_branch',
        'upi_id',
    ];

    /** Largest photo/QR GD will decode (pixels); bigger images could exhaust memory. */
    public const MAX_IMAGE_PIXELS = 25_000_000;

    /** UPI QR images are scaled down to this longest side (still easily scannable). */
    private const QR_MAX_SIDE = 1200;

    /** Trim/normalise donation fields before validation (non-strings are left for validation to reject). */
    public static function normalize(Request $request): void
    {
        $clean = [];

        $accountNumber = $request->input('bank_account_number');
        if (is_string($accountNumber) && trim($accountNumber) !== '') {
            $clean['bank_account_number'] = preg_replace('/[\s-]+/', '', $accountNumber);
        }

        $ifsc = $request->input('bank_ifsc');
        if (is_string($ifsc) && trim($ifsc) !== '') {
            $clean['bank_ifsc'] = strtoupper(preg_replace('/\s+/', '', $ifsc));
        }

        $upi = $request->input('upi_id');
        if (is_string($upi) && trim($upi) !== '') {
            $clean['upi_id'] = preg_replace('/\s+/', '', $upi);
        }

        $request->merge($clean);
    }

    /**
     * @param  int  $existingDocuments  documents the request will keep (after removals)
     * @return array<string, mixed>
     */
    public static function rules(int $existingDocuments = 0): array
    {
        $remaining = max(0, HelpRequest::MAX_DOCUMENTS - $existingDocuments);

        return [
            'documents' => ['nullable', 'array', 'max:'.$remaining],
            'documents.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120', self::pixelLimit()],

            'account_holder_name' => ['nullable', 'string', 'max:255', 'required_with:bank_account_number,bank_ifsc'],
            'bank_account_number' => ['nullable', 'string', 'regex:/^\d{6,20}$/', 'required_with:account_holder_name,bank_ifsc'],
            'bank_ifsc' => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/', 'required_with:bank_account_number'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'upi_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]{2,256}@[A-Za-z]{2,64}$/'],
            'upi_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', self::pixelLimit()],

            // Admin edit only: drop existing files.
            'remove_documents' => ['nullable', 'array'],
            'remove_documents.*' => ['integer'],
            'remove_upi_qr' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'documents.max' => 'You can attach at most '.HelpRequest::MAX_DOCUMENTS.' documents or photos in total.',
            'documents.*.mimes' => 'Documents must be PDF, JPG, PNG or WebP files.',
            'documents.*.max' => 'Each document must be 5 MB or smaller.',
            'bank_account_number.regex' => 'Account number should contain only digits (6 to 20).',
            'bank_ifsc.regex' => 'Enter a valid 11-character IFSC code, e.g. SBIN0001234.',
            'upi_id.regex' => 'Enter a valid UPI ID, e.g. name@okbank.',
            'upi_qr.max' => 'The UPI QR image must be 2 MB or smaller.',
        ];
    }

    /** Validated donation fields, ready to save on the request. */
    public static function paymentData(array $validated): array
    {
        return collect(self::PAYMENT_FIELDS)
            ->mapWithKeys(fn ($field) => [$field => $validated[$field] ?? null])
            ->all();
    }

    /**
     * Store new documents/QR and apply removals requested by an admin edit.
     * Run inside the caller's DB transaction.
     */
    public static function saveFiles(HelpRequest $help, Request $request): void
    {
        $disk = Storage::disk('local');
        $written = [];
        $obsolete = [];

        try {
            $removeIds = array_map('intval', (array) $request->input('remove_documents', []));
            if ($removeIds) {
                // Only this request's documents; their files go after commit (model event).
                $help->documents()->whereIn('id', $removeIds)->get()->each->delete();
            }

            foreach ((array) $request->file('documents', []) as $file) {
                if ($file instanceof UploadedFile) {
                    $written[] = self::storeDocument($help, $file);
                }
            }

            if ($request->boolean('remove_upi_qr') && $help->upi_qr) {
                $obsolete[] = $help->upi_qr;
                $help->upi_qr = null;
            }

            if ($request->hasFile('upi_qr')) {
                if ($help->upi_qr) {
                    $obsolete[] = $help->upi_qr;
                }
                $help->upi_qr = $written[] = self::storeUpiQr($request->file('upi_qr'));
            }

            $help->save();
        } catch (Throwable $e) {
            // The transaction rolls the rows back; remove the files this attempt wrote.
            foreach ($written as $path) {
                $disk->delete($path);
            }

            throw $e;
        }

        // Old files are only deleted once the new state is committed.
        if ($obsolete) {
            DB::afterCommit(fn () => $disk->delete($obsolete));
        }
    }

    /** Validation rule: images must not exceed MAX_IMAGE_PIXELS (PDFs pass through). */
    private static function pixelLimit(): Closure
    {
        return function (string $attribute, mixed $file, Closure $fail) {
            if (! $file instanceof UploadedFile || ! str_starts_with((string) $file->getMimeType(), 'image/')) {
                return;
            }

            $size = @getimagesize($file->getRealPath());

            if ($size && $size[0] * $size[1] > self::MAX_IMAGE_PIXELS) {
                $fail('Images must be 25 megapixels or smaller. Please resize the photo and try again.');
            }
        };
    }

    /** @return string the stored path */
    private static function storeDocument(HelpRequest $help, UploadedFile $file): string
    {
        $directory = 'help_documents/'.$help->id;
        $isImage = str_starts_with((string) $file->getMimeType(), 'image/');

        // Photos are resized (max 1600x1200 JPEG) to keep pages light; PDFs stored as-is.
        $path = $isImage
            ? ImageUpload::storeGalleryImage($file, $directory, 'local')
            : $file->store($directory, 'local');

        $disk = Storage::disk('local');
        if (! $path || ! $disk->exists($path)) {
            throw new RuntimeException('Could not store the uploaded document.');
        }

        try {
            $help->documents()->create([
                'path' => $path,
                'original_name' => Str::limit(trim($file->getClientOriginalName()) ?: 'document', 200, ''),
                'mime_type' => str_ends_with($path, '.jpg') && $isImage ? 'image/jpeg' : ($file->getMimeType() ?: 'application/octet-stream'),
                'size' => (int) $disk->size($path),
            ]);
        } catch (Throwable $e) {
            $disk->delete($path);
            throw $e;
        }

        return $path;
    }

    /**
     * Re-save the QR as a lossless PNG: strips photo metadata (EXIF/GPS) without
     * blurring the code. Falls back to the original file only if GD is unavailable.
     *
     * @return string the stored path
     */
    private static function storeUpiQr(UploadedFile $file): string
    {
        $disk = Storage::disk('local');
        $image = extension_loaded('gd') ? @imagecreatefromstring((string) file_get_contents($file->getRealPath())) : false;

        if ($image === false) {
            $path = $file->store('help_upi_qr', 'local');
        } else {
            $width = imagesx($image);
            $height = imagesy($image);

            if (max($width, $height) > self::QR_MAX_SIDE) {
                $scale = self::QR_MAX_SIDE / max($width, $height);
                $scaled = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
                if ($scaled !== false) {
                    imagedestroy($image);
                    $image = $scaled;
                }
            }

            ob_start();
            imagepng($image, null, 9);
            $png = (string) ob_get_clean();
            imagedestroy($image);

            $path = 'help_upi_qr/'.Str::uuid().'.png';
            if (! $disk->put($path, $png)) {
                $path = false;
            }
        }

        if (! $path || ! $disk->exists($path)) {
            throw new RuntimeException('Could not store the UPI QR image.');
        }

        return $path;
    }
}
