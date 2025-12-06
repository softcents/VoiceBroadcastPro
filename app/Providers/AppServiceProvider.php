<?php

namespace App\Providers;

use App\Services\TTS\TTSManager;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TTSManager::class, function ($app) {
            return new TTSManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::configureUsing(function (Schema $schema) {
            $schema->defaultDateDisplayFormat('M j, Y'); // Example: Dec 6, 2025
            $schema->defaultDateTimeDisplayFormat('M j, Y - H:i A'); // Example: Dec 6, 2025 14:30 PM
        });

        Table::configureUsing(function (Table $table) {
            $table->defaultDateDisplayFormat('M j, Y'); // Example: Dec 6, 2025
            $table->defaultDateTimeDisplayFormat('M j, Y - H:i A'); // Example: Dec 6, 2025 14:30 PM
        });
    }
}
