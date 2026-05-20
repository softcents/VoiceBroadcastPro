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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('caller_id')->nullable()->constrained('callers')->nullOnDelete();
            $table->foreignId('audio_id')->nullable()->constrained('audio');

            $table->string('phone_number');
            $table->text('content')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('type')->default('marketing'); // otp, marketing.
            $table->string('interface')->default('web');
            $table->integer('otp')->nullable();

            // Asterisk
            $table->string('unique_id')->nullable()->index();

            $table->decimal('duration')->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->unsignedInteger('retries')->default(0);
            $table->string('hangup_cause')->nullable();

            $table->timestamp('scheduled_at')->nullable();
            $table->index(['caller_id', 'status']);
            $table->index(['campaign_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
