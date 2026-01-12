<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    protected $table = 'mt_refresh_token';

    protected $fillable = ['user_id', 'token', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function createToken($userId, $expiresInDays = 30): string
    {
        self::where('user_id', $userId)->delete();

        $plainToken = Str::random(80);

        self::create([
            'user_id' => $userId,
            'token' => hash('sha256', $plainToken),
            'expires_at' => Carbon::now()->addDays($expiresInDays),
        ]);

        return $plainToken;
    }

    public static function findByToken($token)
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
