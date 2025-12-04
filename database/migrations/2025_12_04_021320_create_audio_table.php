<?php

use App\Enums\AudioApproval;
use App\Enums\AudioArtist;
use App\Enums\AudioGender;
use App\Enums\AudioLanguage;
use App\Enums\AudioType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('type', array_column(AudioType::cases(), 'value'));
            $table->enum('approval', array_column(AudioApproval::cases(), 'value'))->default(AudioApproval::Pending->value);
            $table->text('message')->nullable();

            $table->enum('language', array_column(AudioLanguage::cases(), 'value'))->nullable();
            $table->enum('gender', array_column(AudioGender::cases(), 'value'))->nullable();
            $table->enum('artist', array_column(AudioArtist::cases(), 'value'))->nullable();

            // Paths to audio files
            $table->string('original_path')->nullable();
            $table->string('converted_path')->nullable();

            // Additional metadata
            $table->integer('duration')->nullable();
            $table->integer('size')->nullable();
            $table->string('mime_type')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio');
    }
};
