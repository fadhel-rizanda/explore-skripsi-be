<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    use HasUuids;

    public const TABLE = 'mt_refresh_token';

    protected $table = self::TABLE;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function createToken(string $userId, string $ipAddress, string $userAgent, int $expiresInDays = 30): string
    {
        self::where('user_id', $userId)->delete();

        $plainToken = Str::random(80);

        self::create([
            'user_id' => $userId,
            'token' => hash('sha256', $plainToken),
            'expires_at' => Carbon::now()->addDays($expiresInDays),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return $plainToken;
    }

    public static function findByToken(string $token)
    {
        return self::where('token', hash('sha256', $token))
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    public function isExpired()
    {
        return $this->expires_at < Carbon::now();
    }
}
