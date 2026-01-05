<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasUuids;

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

    public function latestMessage(): HasMany
    {
        return $this->messages()->latest();
    }
}
