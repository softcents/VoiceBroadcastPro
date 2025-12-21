<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->timestamp('connected_at')->nullable()->after('enabled');
            $table->timestamp('disconnected_at')->nullable()->after('connected_at');
            $table->string('connection_status')->nullable()->after('disconnected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['connected_at', 'disconnected_at', 'connection_status']);
        });
    }
};
