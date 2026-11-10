<?php

namespace Tests\Feature;

use App\Models\HelpRequest;
use App\Models\HelpRequestDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HelpRequestAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_public_submission_stores_documents_and_donation_details_but_stays_hidden(): void
    {
        $this->post(route('help.store'), $this->base([
            'documents' => [
                UploadedFile::fake()->create('report.pdf', 200, 'application/pdf'),
                UploadedFile::fake()->image('patient.jpg', 2400, 1800),
            ],
            'account_holder_name' => 'Komal Kushwaha',
            'bank_account_number' => '1234 5678 9012',
            'bank_ifsc' => 'sbin0001234',
            'bank_name' => 'State Bank of India',
            'upi_id' => 'komal@oksbi',
            'upi_qr' => UploadedFile::fake()->image('qr.png', 400, 400),
        ]))->assertSessionHasNoErrors()->assertRedirect(route('help.index'));

        $help = HelpRequest::with('documents')->firstOrFail();
        $this->assertSame('Pending', $help->status);
        $this->assertSame('123456789012', $help->bank_account_number, 'Spaces stripped.');
        $this->assertSame('SBIN0001234', $help->bank_ifsc, 'IFSC upper-cased.');
        $this->assertCount(2, $help->documents);
        $this->assertSame('image/jpeg', $help->documents[1]->mime_type, 'Photos are resized to JPEG.');
        foreach ($help->documents as $document) {
            Storage::disk('local')->assertExists($document->path);
        }
        Storage::disk('local')->assertExists($help->upi_qr);

        // Pending: nothing is served publicly; admins can still review.
        $this->get($help->documents[0]->url)->assertNotFound();
        $this->get(route('help.upi-qr', $help->id))->assertNotFound();
        $this->get(route('help.show', $help->id))->assertNotFound();

        $admin = User::factory()->create(['role' => 'admin']);
        $hiddenFile = $this->actingAs($admin)->get($help->documents[0]->url)->assertOk();
        $this->assertStringContainsString('private', $hiddenFile->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $hiddenFile->headers->get('Cache-Control'));
        $this->actingAs($admin)->get(route('help.upi-qr', $help->id))->assertOk();
    }

    public function test_validation_of_bank_upi_and_files(): void
    {
        $this->post(route('help.store'), $this->base([
            'account_holder_name' => 'Komal',
            'bank_account_number' => '12AB34',
            'bank_ifsc' => 'BADIFSC',
            'upi_id' => 'not-a-upi',
            'upi_qr' => UploadedFile::fake()->create('qr.pdf', 10, 'application/pdf'),
            'documents' => [UploadedFile::fake()->create('notes.docx', 10, 'application/msword')],
        ]))->assertSessionHasErrors(['bank_account_number', 'bank_ifsc', 'upi_id', 'upi_qr', 'documents.0']);

        // Partial bank details: an account number needs the holder and IFSC.
        $this->post(route('help.store'), $this->base(['bank_account_number' => '123456789']))
            ->assertSessionHasErrors(['account_holder_name', 'bank_ifsc']);

        $this->post(route('help.store'), $this->base([
            'documents' => array_map(fn ($i) => UploadedFile::fake()->create("d{$i}.pdf", 10, 'application/pdf'), range(1, 6)),
        ]))->assertSessionHasErrors('documents');

        $this->assertSame(0, HelpRequest::count());
    }

    public function test_approved_request_shows_documents_and_donation_box_publicly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.help-requests.store'), $this->base([
            'status' => 'Approved',
            'is_public' => true,
            'documents' => [
                UploadedFile::fake()->create('bill.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->image('photo.png', 800, 600),
            ],
            'account_holder_name' => 'Komal Kushwaha',
            'bank_account_number' => '123456789012',
            'bank_ifsc' => 'SBIN0001234',
            'upi_id' => 'komal@oksbi',
            'upi_qr' => UploadedFile::fake()->image('qr.png', 300, 300),
        ]))->assertSessionHasNoErrors();
        auth()->logout();

        $help = HelpRequest::with('documents')->firstOrFail();

        $this->get(route('help.show', $help->id))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Help/Show')
                ->where('helpRequest.bank_ifsc', 'SBIN0001234')
                ->where('helpRequest.upi_id', 'komal@oksbi')
                ->where('helpRequest.upi_qr_url', $help->upi_qr_url)
                ->has('helpRequest.documents', 2)
                ->where('helpRequest.documents.0.is_image', false)
                ->where('helpRequest.documents.1.is_image', true)
                ->missing('helpRequest.documents.0.path')
                ->missing('helpRequest.upi_qr'));

        $publicFile = $this->get($help->documents[0]->url)->assertOk();
        $this->assertStringContainsString('public', $publicFile->headers->get('Cache-Control'));
        $this->get(route('help.upi-qr', $help->id))->assertOk();

        // The listing exposes counts and URLs, not storage paths.
        $this->get(route('help.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('requests.data.0.documents_count', 2)
                ->missing('requests.data.0.upi_qr')
                ->where('requests.data.0.upi_qr_url', $help->upi_qr_url));

        // A document id from another request never resolves.
        $other = HelpRequest::create($this->base(['status' => 'Approved', 'is_public' => true]));
        $this->get(route('help.document', ['id' => $other->id, 'document' => $help->documents[0]->id]))->assertNotFound();
    }

    public function test_admin_can_edit_add_and_remove_files_within_the_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $help = HelpRequest::create($this->base(['status' => 'Approved', 'is_public' => true]));

        $this->actingAs($admin)->get(route('admin.help-requests.edit', $help->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/HelpRequests/Form')
                ->where('helpRequest.id', $help->id)
                ->where('maxDocuments', HelpRequest::MAX_DOCUMENTS));

        // Add 5 documents + QR + bank details to an existing request.
        $this->actingAs($admin)->post(route('admin.help-requests.update', $help->id), $this->base([
            'status' => 'Approved',
            'is_public' => true,
            'title' => 'Updated title',
            'documents' => array_map(fn ($i) => UploadedFile::fake()->create("d{$i}.pdf", 10, 'application/pdf'), range(1, 5)),
            'account_holder_name' => 'Komal Kushwaha',
            'bank_account_number' => '123456789012',
            'bank_ifsc' => 'SBIN0001234',
            'upi_qr' => UploadedFile::fake()->image('qr.png'),
        ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.help-requests.index'));

        $help->refresh()->load('documents');
        $this->assertSame('Updated title', $help->title);
        $this->assertCount(5, $help->documents);
        $oldQr = $help->upi_qr;

        // A 6th file is refused...
        $this->actingAs($admin)->post(route('admin.help-requests.update', $help->id), $this->base([
            'status' => 'Approved',
            'documents' => [UploadedFile::fake()->create('six.pdf', 10, 'application/pdf')],
        ]))->assertSessionHasErrors('documents');

        // ...unless one is removed in the same save; removing the QR deletes its file.
        $removed = $help->documents[0];
        $this->actingAs($admin)->post(route('admin.help-requests.update', $help->id), $this->base([
            'status' => 'Approved',
            'remove_documents' => [$removed->id],
            'documents' => [UploadedFile::fake()->create('six.pdf', 10, 'application/pdf')],
            'remove_upi_qr' => true,
        ]))->assertSessionHasNoErrors();

        $help->refresh()->load('documents');
        $this->assertCount(5, $help->documents);
        $this->assertFalse($help->documents->contains('id', $removed->id));
        Storage::disk('local')->assertMissing($removed->path);
        $this->assertNull($help->upi_qr);
        Storage::disk('local')->assertMissing($oldQr);
    }

    public function test_deleting_a_request_removes_its_files(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.help-requests.store'), $this->base([
            'status' => 'Approved',
            'documents' => [UploadedFile::fake()->create('bill.pdf', 10, 'application/pdf')],
            'upi_qr' => UploadedFile::fake()->image('qr.png'),
        ]));
        $help = HelpRequest::with('documents')->firstOrFail();
        $paths = [$help->documents[0]->path, $help->upi_qr];

        $this->actingAs($admin)->delete(route('admin.help-requests.destroy', $help->id));

        foreach ($paths as $path) {
            Storage::disk('local')->assertMissing($path);
        }
        $this->assertDatabaseCount('help_request_documents', 0);
    }

    // --- Regressions from the PR #8 review -------------------------------------------

    public function test_replacing_the_upi_qr_changes_its_url_so_caches_never_show_the_old_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.help-requests.store'), $this->base([
            'status' => 'Approved',
            'upi_qr' => UploadedFile::fake()->image('qr.png', 300, 300),
        ]));
        $help = HelpRequest::firstOrFail();
        $firstUrl = $help->upi_qr_url;

        $this->actingAs($admin)->post(route('admin.help-requests.update', $help->id), $this->base([
            'status' => 'Approved',
            'upi_qr' => UploadedFile::fake()->image('new-qr.png', 300, 300),
        ]))->assertSessionHasNoErrors();

        $this->assertNotSame($firstUrl, $help->fresh()->upi_qr_url);
        $this->get($help->fresh()->upi_qr_url)->assertOk();
    }

    public function test_upi_qr_is_re_saved_as_png_so_photo_metadata_is_not_published(): void
    {
        $this->post(route('help.store'), $this->base([
            'upi_qr' => UploadedFile::fake()->image('qr-photo.jpg', 2400, 2400),
        ]))->assertSessionHasNoErrors();

        $path = HelpRequest::firstOrFail()->upi_qr;
        $this->assertStringEndsWith('.png', $path);

        $bytes = Storage::disk('local')->get($path);
        $this->assertStringStartsWith("\x89PNG", $bytes, 'Re-encoded by GD (no EXIF/GPS carried over).');
        $size = getimagesizefromstring($bytes);
        $this->assertLessThanOrEqual(1200, max($size[0], $size[1]), 'Large photos are scaled down.');
    }

    public function test_transparent_upi_qr_keeps_a_white_background_so_it_stays_scannable(): void
    {
        $image = imagecreatetruecolor(200, 200);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagefilledrectangle($image, 20, 20, 80, 80, imagecolorallocate($image, 0, 0, 0));
        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        $this->post(route('help.store'), $this->base([
            'upi_qr' => UploadedFile::fake()->createWithContent('qr.png', $png),
        ]))->assertSessionHasNoErrors();

        $stored = imagecreatefromstring(Storage::disk('local')->get(HelpRequest::firstOrFail()->upi_qr));
        $background = imagecolorsforindex($stored, imagecolorat($stored, 150, 150));
        $module = imagecolorsforindex($stored, imagecolorat($stored, 50, 50));

        $this->assertSame([255, 255, 255], [$background['red'], $background['green'], $background['blue']], 'Transparent background flattened to white, not black.');
        $this->assertSame([0, 0, 0], [$module['red'], $module['green'], $module['blue']]);
    }

    public function test_images_with_huge_pixel_dimensions_are_rejected_before_processing(): void
    {
        // A tiny file whose header claims 10000 x 10000 pixels (would exhaust memory in GD).
        $bomb = UploadedFile::fake()->createWithContent('bomb.png', $this->pngHeader(10000, 10000));

        $this->post(route('help.store'), $this->base(['documents' => [$bomb]]))
            ->assertSessionHasErrors('documents.0');
        $this->post(route('help.store'), $this->base([
            'upi_qr' => UploadedFile::fake()->createWithContent('qr.png', $this->pngHeader(10000, 10000)),
        ]))->assertSessionHasErrors('upi_qr');

        $this->assertSame(0, HelpRequest::count());
    }

    public function test_array_values_in_bank_fields_get_validation_errors_not_a_server_error(): void
    {
        $this->post(route('help.store'), $this->base([
            'bank_account_number' => ['123'],
            'bank_ifsc' => ['SBIN0001234'],
            'upi_id' => ['a@b'],
        ]))->assertRedirect()->assertSessionHasErrors(['bank_account_number', 'bank_ifsc', 'upi_id']);
    }

    public function test_a_failed_upload_leaves_no_half_saved_request_or_files(): void
    {
        HelpRequestDocument::creating(fn () => throw new \RuntimeException('disk full'));

        $this->post(route('help.store'), $this->base([
            'documents' => [UploadedFile::fake()->create('report.pdf', 50, 'application/pdf')],
        ]))->assertStatus(500);

        $this->assertSame(0, HelpRequest::count(), 'Request row rolled back.');
        $this->assertSame([], Storage::disk('local')->allFiles('help_documents'), 'Stored file removed again.');
    }

    public function test_description_limit_is_shared_and_old_long_descriptions_stay_editable(): void
    {
        $tooLong = str_repeat('a', HelpRequest::MAX_DESCRIPTION + 1);

        $this->post(route('help.store'), $this->base(['description' => $tooLong]))
            ->assertSessionHasErrors('description');

        // A pre-existing longer description can still be saved unchanged by an admin.
        $help = HelpRequest::create($this->base(['description' => $tooLong, 'status' => 'Approved']));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.help-requests.update', $help->id), $this->base([
            'description' => $tooLong,
            'status' => 'Approved',
        ]))->assertSessionHasNoErrors();
    }

    /** Minimal PNG (signature + IHDR) claiming the given size; getimagesize() reads only this. */
    private function pngHeader(int $width, int $height): string
    {
        $ihdr = 'IHDR'.pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

        return "\x89PNG\r\n\x1a\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr));
    }

    private function base(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Komal Kushwaha',
            'mobile' => '9000000003',
            'whatsapp' => '9000000003',
            'state' => 'Uttar Pradesh',
            'district' => 'Virpur',
            'city' => 'Virpur',
            'category' => 'Medical Help',
            'title' => 'Cancer treatment support',
            'description' => 'Family needs help with chemotherapy costs.',
            'urgency' => 'Urgent',
            'is_public' => true,
        ], $overrides);
    }
}
