<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Message extends Model
{
    use HasUuids;

    protected $table = 'tr_message';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'chat_id',
        'user_id',
        'content',
        'attachment_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }

    public function readBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tr_message_read', 'message_id', 'user_id')
            ->withPivot('read_at')
            ->withTimestamps();
    }
}
