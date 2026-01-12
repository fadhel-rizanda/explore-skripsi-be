<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetPhysiqueTag extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tr_all_tag_pet_physique_record';

    public $incrementing = false;

    public $timestamps = false;

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
     * Get the pet that owns the physique tag.
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