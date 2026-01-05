<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasUuids;

    protected $table = 'tr_notification';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'title',
        'message',
        'user_id',
        'reference_id',
        'reference_type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(
            __FUNCTION__, // otomatis menggunakan nama fungsi sebagai nama relasi
            'reference_type', // kolom untuk tipe model
            'reference_id' // kolom untuk ID model
        );
    }

    //    scopes, format penulisan: scopeNamaScope
    //    cara pake: Model::namaScope($var) atau $model->namaScope($var)
    //    menambahkan kondisi buildernya
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
