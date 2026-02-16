<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Role extends \Spatie\Permission\Models\Role
{
    use HasUuids;

    public const TABLE = 'mt_role';

    protected $keyType = 'string';

    public $incrementing = false;
}
