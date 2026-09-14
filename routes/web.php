<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/session-check', function (Request $request) {
    $visits = (int) $request->session()->get('visits', 0);
    $visits++;
    $request->session()->put('visits', $visits);
    return response()->json([
        'visits' => $visits,
    ]);
});
