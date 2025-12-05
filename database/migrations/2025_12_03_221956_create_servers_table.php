<?php

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

            $table->string('ari_domain');
            $table->string('ari_username');
            $table->string('ari_password');

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
