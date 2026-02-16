<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Permission extends \Spatie\Permission\Models\Permission
{
    use HasUuids;

    public const TABLE = 'mt_permission';

    protected $keyType = 'string';

    public $incrementing = false;
}
