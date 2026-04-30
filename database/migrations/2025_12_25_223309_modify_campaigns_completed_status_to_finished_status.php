<?php

declare(strict_types=1);

use App\Models\Campaign;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Campaign::where('status', 'completed')
            ->update(['status' => 'finished']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Campaign::where('status', 'finished')
            ->update(['status' => 'completed']);
    }
};
