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
        Schema::table('servers', function (Blueprint $table) {
            $table->renameColumn('scheme', 'ari_scheme');
            $table->renameColumn('host', 'ari_host');
            $table->renameColumn('port', 'ari_port');
            $table->renameColumn('username', 'ari_username');
            $table->renameColumn('password', 'ari_password');

            $table->string('database_host')->after('ari_password');
            $table->unsignedInteger('database_port')->default(3306)->after('ari_password');
            $table->string('database_username')->nullable()->after('ari_password');
            $table->string('database_password')->nullable()->after('ari_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->renameColumn('ari_scheme', 'scheme');
            $table->renameColumn('ari_port', 'port');
            $table->renameColumn('ari_username', 'username');
            $table->renameColumn('ari_password', 'password');
            $table->dropColumn('database_port');
            $table->dropColumn('database_username');
            $table->dropColumn('database_password');
        });
    }
};
