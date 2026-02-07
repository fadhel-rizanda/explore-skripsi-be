<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable;

    protected $table = 'mt_user';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'about_me',
        'personality',
        'pet_experience',
        'pet_preferences',
        'open_to_special_needs',
        'password',
        'avatar',
        'attachment_id',
        'address_id',
        'email_verified_at',
        'remember_token',
        'token_version',
        'provider',
        'provider_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'token_version',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'token_version' => 'integer',
            'open_to_special_needs' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'token_version' => $this->token_version ?? 0,
        ];
    }

    public function chatRooms(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class, 'tr_chat_room', 'user_id', 'chat_id')
            ->withTimestamps()
            ->withPivot('last_read_at', 'joined_at');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id', 'id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id', 'id');
    }

    public function personalityTags(): BelongsToMany
    {
        return $this->belongsToMany(AllTag::class, 'tr_all_tag_user_personality_record', 'user_id', 'tag_id');
    }

    public function petExperienceTags(): BelongsToMany
    {
        return $this->belongsToMany(AllTag::class, 'tr_all_tag_user_experience_record', 'user_id', 'tag_id');
    }

    public function petPreferencesTags(): BelongsToMany
    {
        return $this->belongsToMany(AllTag::class, 'tr_all_tag_user_preferences_record', 'user_id', 'tag_id');
    }

    public function adoptionsAsAdopter()
    {
        return $this->hasMany(Adoption::class, 'adopter_id');
    }

    public function adoptionsAsProvider()
    {
        return Adoption::query()
            ->whereHas(
                'pet',
                fn ($q) => $q->where('user_id', $this->id)
            );
    }

    public function adoptionsByRole()
    {
        if ($this->hasRole(RoleEnum::ADOPTER->value)) {
            return $this->adoptionsAsAdopter();
        }

        if ($this->hasRole(RoleEnum::PROVIDER->value)) {
            return $this->adoptionsAsProvider();
        }

        return Adoption::query()->whereRaw('1 = 0');
    }

    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'tr_follow_community', 'user_id', 'community_id');
    }
}
