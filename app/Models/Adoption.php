<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Adoption extends Model
{
    use HasUuids;

    public const TABLE = 'mt_adoption_application';

    protected $table = self::TABLE;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'is_active',
        'adopter_id',
        'pet_id',
        'status_id',
        'stage_tag_id',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function adopter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adopter_id');
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    public function provider(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Pet::class,
            'id',
            'id',
            'pet_id',
            'user_id'
        );
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function stageTag(): BelongsTo
    {
        return $this->belongsTo(AllTag::class, 'stage_tag_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function meetNGreets(): HasMany
    {
        return $this->hasMany(MeetNGreet::class, 'adoption_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class, 'adoption_id');
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(Handover::class, 'adoption_id');
    }

    public function latestMeetNGreet(): HasOne
    {
        return $this->hasOne(MeetNGreet::class, 'adoption_id')
            ->where('stage', 'default')
            ->whereIn('id', function ($query) {
                $query->selectRaw('id')
                    ->from('tr_adoption_meet_greet as mg_inner')
                    ->where('mg_inner.stage', 'default')
                    ->whereColumn('mg_inner.adoption_id', 'tr_adoption_meet_greet.adoption_id')
                    ->orderByDesc('created_at')
                    ->limit(1);
            });
    }

    public function latestHandover(): HasOne
    {
        return $this->hasOne(Handover::class, 'adoption_id')->latest('created_at');
    }
}
