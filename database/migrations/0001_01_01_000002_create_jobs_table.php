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
        Schema::connection('mongodb')->create('jobs', function (Blueprint $table) {
            $table->index('queue');
            $table->index(['queue', 'reserved_at', 'available_at']);
        });

        Schema::connection('mongodb')->create('job_batches', function (Blueprint $table) {
            $table->unique('id');
        });

        Schema::connection('mongodb')->create('failed_jobs', function (Blueprint $table) {
            $table->unique('uuid');
            $table->index(['connection', 'queue', 'failed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('jobs');
        Schema::connection('mongodb')->dropIfExists('job_batches');
        Schema::connection('mongodb')->dropIfExists('failed_jobs');
    }
};
