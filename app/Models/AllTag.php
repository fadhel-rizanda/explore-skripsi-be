<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AllTag extends Model
{
    use HasUuids;

    public const TABLE = 'mt_all_tag';

    protected $table = self::TABLE;

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
        'name',
        'type',
        'color_code',
    ];

    public static function getCache(string $type, string $name): self
    {
        return Cache::remember(
            "tag:{$type}:{$name}",
            now()->addHours(6),
            fn () => static::where([
                'name' => $name,
                'type' => $type,
            ])->firstOrFail()
        );
    }
}
