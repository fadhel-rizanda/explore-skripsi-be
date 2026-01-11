<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mt_all_status';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'status_name',
        'status_type',
    ];

    /**
     * Get the pets with this status.
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'status_id');
    }
}
