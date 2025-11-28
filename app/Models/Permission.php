<?php

namespace App\Models;

class Permission extends \Spatie\Permission\Models\Permission
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'permissions_2';
}
