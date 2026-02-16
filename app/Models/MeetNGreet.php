<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetNGreet extends Model
{
    use HasUuids;

    public const TABLE = 'tr_adoption_meet_greet';

    protected $table = self::TABLE;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'adoption_id',
        'schedule_id',
        'status_id',
        'adopter_confirmed',
        'provider_confirmed',

        'adopter_confirmed_at',
        'provider_confirmed_at',

        'created_by',
        'updated_by',
        'is_active',
    ];

    protected $casts = [
        'adopter_confirmed' => 'boolean',
        'provider_confirmed' => 'boolean',
        'adopter_confirmed_at' => 'datetime',
        'provider_confirmed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function adoption(): BelongsTo
    {
        return $this->belongsTo(Adoption::class, 'adoption_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function handOvers(): HasMany
    {
        return $this->hasMany(Handover::class, 'meet_n_greet_id');
    }
}
