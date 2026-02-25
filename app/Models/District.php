<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class District extends Model
{
    protected $table = 'mt_district';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'regency_id',
        'name',
    ];

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }
}
