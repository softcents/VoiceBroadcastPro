<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Trigger\Manager;
use App\Support\TTS\TTSManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TTSManager::class, function ($app) {
            return new TTSManager($app);
        });

        $this->app->bind('trigger.manager', fn ($app) => new Manager($app->make('config')->get('trigger')));

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::configureUsing(function (Schema $schema) {
            $schema->defaultDateDisplayFormat('M j, Y'); // Example: Dec 6, 2025
            $schema->defaultDateTimeDisplayFormat('M j, Y \a\t h:i A'); // Example: Dec 6, 2025 at 2:30 PM
        });

        Table::configureUsing(function (Table $table) {
            $table->defaultDateDisplayFormat('M j, Y'); // Example: Dec 6, 2025
            $table->defaultDateTimeDisplayFormat('M j, Y \a\t h:i A'); // Example: Dec 6, 2025 at 2:30 PM
        });
    }
}
