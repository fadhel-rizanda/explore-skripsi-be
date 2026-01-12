<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pet extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tr_pet';

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
        'user_id',
        'status_id',
        'type_of_animal_id',
        'size',
        'name',
        'date_of_birth',
        'gender',
        'about',
        'breed',
        'special_needs',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'special_needs' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];  

    /**
     * Get the user that owns the pet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the status of the pet.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Get the profile pictures for the pet.
     */
    public function profilePictures(): HasMany
    {
        return $this->hasMany(PetProfilePicture::class, 'pet_id');
    }

    /**
     * Get the type of animal tag.
     */
    public function typeOfAnimal(): BelongsTo
    {
        return $this->belongsTo(AllTag::class, 'type_of_animal_id');
    }

    /**
     * Get personality tags for the pet.
     */
    public function personalityTags(): BelongsToMany
    {
        return $this->belongsToMany(
            AllTag::class,
            'tr_all_tag_pet_personality_record',
            'pet_id',
            'all_tag_id'
        );
    }

    /**
     * Get physique tags for the pet.
     */
    public function physiqueTags(): BelongsToMany
    {
        return $this->belongsToMany(
            AllTag::class,
            'tr_all_tag_pet_physique_record',
            'pet_id',
            'all_tag_id'
        );
    }

    /**
     * Get physique tags for the pet.
     */
    public function physiqueTags(): BelongsToMany
    {
        return $this->belongsToMany(
            AllTag::class,
            'tr_all_tag_pet_physique_record',
            'pet_id',
            'all_tag_id'
        );
    }
}
