<?php

namespace App\Models;

class Role extends \Spatie\Permission\Models\Role
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'roles_2';
}
