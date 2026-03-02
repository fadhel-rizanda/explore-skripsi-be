<?php

namespace App\Providers;

use App\Enums\ModelReferenceEnum;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        ]);
    }
}
