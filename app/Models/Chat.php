<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Chat extends Model
{
    protected $table = 'chat_rooms';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
        'type',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'room_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_room_user', 'chat_room_id', 'user_id')
            ->withTimestamps()
            ->withPivot('last_read_at', 'joined_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->messages()->latest();
    }
}
