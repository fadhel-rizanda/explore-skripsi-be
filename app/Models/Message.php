<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Message extends Model
{
    protected $table = 'chat_messages';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'room_id',
        'user_id',
        'message',
        'attachment_id',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function room():BelongsTo
    {
        return $this->belongsTo(Chat::class, 'room_id');
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachment():BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }

    public function readBy():BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_message_reads', 'message_id', 'user_id')
            ->withPivot('read_at')
            ->withTimestamps();
    }
}
