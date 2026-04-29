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
        Schema::table('users', static function (Blueprint $table) {
            $table->boolean('auto_approve_audio')->default(false)->after('audio_type');
            $table->boolean('auto_approve_campaigns')->default(false)->after('auto_approve_audio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('auto_approve_audio');
            $table->dropColumn('auto_approve_campaigns');
        });
    }
};
