<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Adoption extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'adoptions';

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'id' => 'string',
    ];

    /**
     * Relationships
     */

    // Each adoption may have many related documents
    public function documents()
    {
        return $this->hasMany(AdoptionDocument::class, 'adoption_id');
    }
}
