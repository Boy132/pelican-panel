<?php

use App\Enums\QueryType;
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
        Schema::table('eggs', function (Blueprint $table) {
            $table->string('query_type')->default(QueryType::None);
            $table->unsignedSmallInteger('query_port_diff')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('query_type');
        });
    }
};
