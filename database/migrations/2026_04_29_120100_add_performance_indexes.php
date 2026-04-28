<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->index(['caller_id', 'status'], 'calls_caller_id_status_index');
            $table->index(['campaign_id', 'status'], 'calls_campaign_id_status_index');
            $table->index(['user_id', 'created_at'], 'calls_user_id_created_at_index');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'campaigns_user_id_status_index');
            $table->index(['status', 'scheduled_at'], 'campaigns_status_scheduled_at_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'transactions_user_id_created_at_index');
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'deposits_user_id_status_index');
        });

        Schema::table('call_events', function (Blueprint $table) {
            $table->index(['call_id', 'created_at'], 'call_events_call_id_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('calls_caller_id_status_index');
            $table->dropIndex('calls_campaign_id_status_index');
            $table->dropIndex('calls_user_id_created_at_index');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_user_id_status_index');
            $table->dropIndex('campaigns_status_scheduled_at_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_id_created_at_index');
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex('deposits_user_id_status_index');
        });

        Schema::table('call_events', function (Blueprint $table) {
            $table->dropIndex('call_events_call_id_created_at_index');
        });
    }
};
