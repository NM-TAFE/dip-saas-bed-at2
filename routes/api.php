<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(static function (): void {
    Route::as('auth:')
        ->prefix('auth')
        ->group(base_path(path: 'routes/api/auth.php'));

    Route::middleware(['auth:sanctum'])->group(static function (): void {
        Route::prefix('users')->group(base_path(path: 'routes/api/users.php'));
        Route::prefix('tasks')->group(base_path(path: 'routes/api/tasks.php'));
        Route::prefix('attachments')->group(base_path(path: 'routes/api/attachments.php'));
    });
});

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::post(
    '/webhooks/task-created',
    function (Request $request) {
        Log::info(
            'Task webhook received',
            $request->all(),
        );

        return response()->json([
            'received' => true,
        ]);
    }
);
