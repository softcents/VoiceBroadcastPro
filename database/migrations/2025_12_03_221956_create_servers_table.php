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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->enum('scheme', ['http', 'https'])->default('http');
            $table->string('host');
            $table->string('port')->nullable();
            $table->string('username');
            $table->string('password');

            $table->string('database_host');
            $table->integer('database_port')->default(3306);
            $table->string('database_name')->default('asteriskcdrdb');
            $table->string('database_username');
            $table->string('database_password');

            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
