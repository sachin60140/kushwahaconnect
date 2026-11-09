<?php

namespace App\Support;

use App\Models\HelpRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Supporting documents/photos and donation details (bank account, UPI) for help
 * requests: shared validation, input clean-up and file storage for the public
 * form and the admin create/edit forms.
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

    /** Trim/normalise donation fields before validation. */
    public static function normalize(Request $request): void
    {
        $clean = [];

        if ($request->filled('bank_account_number')) {
            $clean['bank_account_number'] = preg_replace('/[\s-]+/', '', (string) $request->input('bank_account_number'));
        }

        if ($request->filled('bank_ifsc')) {
            $clean['bank_ifsc'] = strtoupper(preg_replace('/\s+/', '', (string) $request->input('bank_ifsc')));
        }

        if ($request->filled('upi_id')) {
            $clean['upi_id'] = preg_replace('/\s+/', '', (string) $request->input('upi_id'));
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
            'documents.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],

            'account_holder_name' => ['nullable', 'string', 'max:255', 'required_with:bank_account_number,bank_ifsc'],
            'bank_account_number' => ['nullable', 'regex:/^\d{6,20}$/', 'required_with:account_holder_name,bank_ifsc'],
            'bank_ifsc' => ['nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/', 'required_with:bank_account_number'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'upi_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]{2,256}@[A-Za-z]{2,64}$/'],
            'upi_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

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

    /** Store new documents/QR and apply removals requested by an admin edit. */
    public static function saveFiles(HelpRequest $help, Request $request): void
    {
        $disk = Storage::disk('local');

        $removeIds = array_map('intval', (array) $request->input('remove_documents', []));
        if ($removeIds) {
            // Model events delete each file.
            $help->documents()->whereIn('id', $removeIds)->get()->each->delete();
        }

        foreach ((array) $request->file('documents', []) as $file) {
            if ($file instanceof UploadedFile) {
                self::storeDocument($help, $file);
            }
        }

        if ($request->boolean('remove_upi_qr') && $help->upi_qr) {
            $disk->delete($help->upi_qr);
            $help->upi_qr = null;
        }

        if ($request->hasFile('upi_qr')) {
            $previous = $help->upi_qr;
            // Stored untouched: re-encoding could blur the QR and break scanning.
            $help->upi_qr = $request->file('upi_qr')->store('help_upi_qr', 'local');

            if ($previous) {
                $disk->delete($previous);
            }
        }

        $help->save();
    }

    private static function storeDocument(HelpRequest $help, UploadedFile $file): void
    {
        $directory = 'help_documents/'.$help->id;
        $isImage = str_starts_with((string) $file->getMimeType(), 'image/');

        // Photos are resized (max 1600x1200 JPEG) to keep pages light; PDFs stored as-is.
        $path = $isImage
            ? ImageUpload::storeGalleryImage($file, $directory, 'local')
            : $file->store($directory, 'local');

        $disk = Storage::disk('local');
        $name = Str::limit(trim($file->getClientOriginalName()) ?: 'document', 200, '');

        $help->documents()->create([
            'path' => $path,
            'original_name' => $name,
            'mime_type' => str_ends_with($path, '.jpg') && $isImage ? 'image/jpeg' : ($file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $disk->size($path),
        ]);
    }
}
