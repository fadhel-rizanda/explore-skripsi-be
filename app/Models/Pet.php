<?php

namespace App\Models;

use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pet extends Model
{
    use HasUuids, InteractsWithAttachments;

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
        'is_active',
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
    public function profilePictures(): BelongsToMany
    {
        return $this->belongsToMany(
            Attachment::class,
            'tr_pet_profile_picture',
            'pet_id',
            'attachment_id'
        );
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
     * Get personality tags for the pet.
     */
    public function additionalRecords(): BelongsToMany
    {
        return $this->belongsToMany(
            Attachment::class,
            'tr_pet_additional_record',
            'pet_id',
            'attachment_id'
        );
    }

    /**
     * Get the main profile picture for the pet (only one).
     */
    public function profilePicture()
    {
        return $this->belongsToMany(
            Attachment::class,
            'tr_pet_profile_picture',
            'pet_id',
            'attachment_id'
        )->orderBy('tr_pet_profile_picture.attachment_id')->limit(1);
    }
}
