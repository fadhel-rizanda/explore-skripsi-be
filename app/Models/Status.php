<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Status extends Model
{
    use HasUuids;

    protected $table = 'mt_all_status';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'type',
        'color_code',
    ];

    /**
     * Get the pets with this status.
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'status_id');
    }

    public static function report(string $name): self
    {
        return Cache::remember(
            "status:report:{$name}",
            now()->addHours(6),
            fn () => static::where([
                'name' => $name,
                'type' => 'report',
            ])->firstOrFail()
        );
    }
}
