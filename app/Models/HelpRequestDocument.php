<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A supporting document or photo for a help request (medical report, bill, photo).
 * Stored privately; served by help.document with the request's visibility rules.
 */
class HelpRequestDocument extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['path'];

    protected $appends = ['url', 'is_image'];

    protected static function booted(): void
    {
        static::deleting(fn (HelpRequestDocument $document) => Storage::disk('local')->delete($document->path));
    }

    public function helpRequest()
    {
        return $this->belongsTo(HelpRequest::class);
    }

    public function getUrlAttribute(): string
    {
        return route('help.document', ['id' => $this->help_request_id, 'document' => $this->id]);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
