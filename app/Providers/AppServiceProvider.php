<?php

namespace App\Providers;

use App\Support\PolymorphicRelations;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
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
        PolymorphicRelations::enforceMorphMap();
    }
}
