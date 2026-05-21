<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\TTS\TTSManager;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TTSManager::class, fn ($app) => new TTSManager($app));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
        $this->configureSanctum();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('call', function (object $job) {});
    }

    private function configureSanctum(): void
    {
        Sanctum::getAccessTokenFromRequestUsing(function ($request) {
            return $request->query('token')
                || $request->input('token')
                || $request->bearerToken();
        });
    }
}
