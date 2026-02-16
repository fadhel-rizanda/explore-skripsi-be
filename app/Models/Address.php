<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasUuids;

    public const TABLE = 'mt_address';

    protected $table = self::TABLE;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'street',
        'city',
        'state',
        'zip_code',
        'country',
        'notes',
        'link',
    ];
}
