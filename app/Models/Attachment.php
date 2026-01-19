<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasUuids;

    protected $table = 'mt_attachment';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'uploaded_by',
        'filename',
        'path',
        'file_size',
        'mime_type',
        'status',
        'uploaded_at',
        'is_public',
        'public_url',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function deleteFromStorage(): bool
    {
        if (Storage::disk('s3')->exists($this->path)) {
            Storage::disk('s3')->delete($this->path);
        }

        return $this->delete();
    }
}
