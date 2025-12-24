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
        Schema::table('callers', function (Blueprint $table) {
            $table->unsignedInteger('max_concurrency')->default(0)->after('trunk_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('callers', function (Blueprint $table) {
            $table->dropColumn('max_concurrency');
        });
    }
};
