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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phonebook_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->string('description')->nullable();
            $table->string('status')->default('pending');
            $table->string('source')->default('manual');
            $table->string('file_path')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
