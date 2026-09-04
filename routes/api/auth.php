<?php

declare(strict_types=1);

use App\Http\Api\Controllers\Auth;
use Illuminate\Support\Facades\Route;

Route::post('login', Auth\LoginController::class);
