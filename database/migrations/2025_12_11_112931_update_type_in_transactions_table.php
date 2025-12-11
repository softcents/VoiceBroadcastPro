<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Changing to string removes the enum constraint in most drivers or updates it
            // For SQLite, we might need to be careful, but Schema::table()->change() usually handles table recreation if needed.
            // Using 'string' is safer than 'enum' to avoid future constraint issues if new types are added.
            $table->string('type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // We can't easily revert to the exact previous enum without redefining it.
            // We'll leave it as string or try to revert if strictly needed.
            $table->string('type')->change();
        });
    }
};
