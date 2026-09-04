<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * For API requests, prevent redirecting to a nonexistent login route.
     */
    protected function redirectTo($request)
    {
        // Returning null stops Laravel from calling route('login')
        return null;
    }
}
