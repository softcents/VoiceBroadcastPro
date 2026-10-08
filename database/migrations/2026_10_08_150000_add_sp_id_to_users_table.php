<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('sp_id')
                ->nullable()
                ->after('type')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['sp_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['sp_id']);
            $table->dropIndex(['sp_id', 'type']);
            $table->dropColumn('sp_id');
        });
    }
};
