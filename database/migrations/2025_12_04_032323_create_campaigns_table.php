<?php

use App\Enums\CampaignSource;
use App\Enums\CampaignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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
            $table->enum('source', array_column(CampaignSource::cases(), 'value'));
            $table->enum('status', array_column(CampaignStatus::cases(), 'value'));
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
