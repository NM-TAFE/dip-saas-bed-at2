<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->create('cache', function (Blueprint $table) {
            $table->unique('key');
            $table->index('expiration');
        });

        Schema::connection('mongodb')->create('cache_locks', function (Blueprint $table) {
            $table->unique('key');
            $table->index('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('cache');
        Schema::connection('mongodb')->dropIfExists('cache_locks');
    }
};
