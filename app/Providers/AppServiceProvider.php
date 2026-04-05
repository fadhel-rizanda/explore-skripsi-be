<?php

namespace App\Providers;

use App\Enums\ModelReferenceEnum;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
// PENTING: Gunakan Facade agar bisa dipanggil secara statis RateLimiter::for
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            ModelReferenceEnum::USER->value => \App\Models\User::class,
            ModelReferenceEnum::PET->value => \App\Models\Pet::class,
            ModelReferenceEnum::COMMUNITY->value => \App\Models\Community::class,
            ModelReferenceEnum::POST->value => \App\Models\Post::class,
            ModelReferenceEnum::ADOPTION->value => \App\Models\Adoption::class,
        ]);

        RateLimiter::for('read', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'error' => true,
                        'status' => 'error',
                        'message' => 'Too many requests. Please try again later.',
                        'data' => [],
                    ], 429, $headers);
                });
        });

        RateLimiter::for('write', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'error' => true,
                        'status' => 'error',
                        'message' => 'Too many requests. Please try again later.',
                        'data' => [],
                    ], 429, $headers);
                });
        });

        RateLimiter::for('register-login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'error' => true,
                        'status' => 'error',
                        'message' => 'Too many attempts. Please try again in a few minutes.',
                        'data' => [],
                    ], 429, $headers);
                });
        });
    }
}
