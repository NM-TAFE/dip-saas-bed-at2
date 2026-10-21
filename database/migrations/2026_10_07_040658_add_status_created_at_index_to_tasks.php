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
        Schema::connection('mongodb')
            ->table('tasks', function (Blueprint $collection): void {
                $collection->index([
                    'status' => 1,
                    'created_at' => -1,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')
            ->table('tasks', function (Blueprint $collection): void {
                $collection->dropIndex([
                    'status' => 1,
                    'created_at' => -1,
                ]);
            });
    }
};
