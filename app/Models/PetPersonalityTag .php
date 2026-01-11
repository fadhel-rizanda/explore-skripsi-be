<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetPersonalityTag extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tr_all_tag_pet_personality_record';
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
        'pet_id',
        'all_tag_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */

    /**
     * Get the pet that owns the additional record.
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    public function allTag(): BelongsTo
    {
        return $this->belongsTo(AllTag::class, 'all_tag_id');
    }
}