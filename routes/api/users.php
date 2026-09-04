<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', static function (Request $request): ?Authenticatable {
    return $request->user();
});
