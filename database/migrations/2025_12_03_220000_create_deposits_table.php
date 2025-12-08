<?php

declare(strict_types=1);

use App\Enums\DepositStatus;
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
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 4)->default(0);
            $table->string('currency')->default('BDT');
            $table->string('gateway');
            $table->string('transaction_id')->nullable();
            $table->enum('status', array_column(DepositStatus::cases(), 'value'))->default(DepositStatus::Pending);
            $table->json('meta_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
