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
        Schema::table('api_keys', function (Blueprint $table) {
            $table->text('permissions');
        });

        // TODO: convert existing api keys to new format

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn([
                'r_servers',
                'r_nodes',
                'r_allocations',
                'r_users',
                'r_eggs',
                'r_database_hosts',
                'r_server_databases',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });

        // TODO: convert existing api keys back to old format

        Schema::table('api_keys', function (Blueprint $table) {
            $table->unsignedTinyInteger('r_servers')->default(0);
            $table->unsignedTinyInteger('r_nodes')->default(0);
            $table->unsignedTinyInteger('r_allocations')->default(0);
            $table->unsignedTinyInteger('r_users')->default(0);
            $table->unsignedTinyInteger('r_eggs')->default(0);
            $table->unsignedTinyInteger('r_database_hosts')->default(0);
            $table->unsignedTinyInteger('r_server_databases')->default(0);
        });
    }
};
