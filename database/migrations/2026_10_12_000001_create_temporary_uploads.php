<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('mongodb')->create('temporary_uploads', function (Blueprint $collection): void {
            $collection->index(['uploaded_by' => 1, 'created_at' => -1]);
            $collection->index(['status' => 1]);
            $collection->index(['expires_at' => 1], options: ['expireAfterSeconds' => 0]);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('temporary_uploads');
    }
};
