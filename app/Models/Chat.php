<?php

namespace App\Models;

use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends Model
{
    use HasUuids, InteractsWithAttachments;

    protected $table = 'mt_chat';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
        'description',
        'type',
        'created_by',
        'updated_by',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'chat_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tr_chat_room', 'chat_id', 'user_id')
            ->withTimestamps()
            ->withPivot('last_read_at', 'joined_at');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'chat_id')
            ->latest('updated_at');
    }
}
