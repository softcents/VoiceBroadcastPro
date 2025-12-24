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
            $table->dropColumn([
                'database_host',
                'database_port',
                'database_name',
                'database_username',
                'database_password',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('database_host');
            $table->integer('database_port')->default(3306);
            $table->string('database_name')->default('asteriskcdrdb');
            $table->string('database_username');
            $table->string('database_password');
        });
    }
};
