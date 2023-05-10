<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */

    // protected function unauthenticated($request, $guard)
    // {
    //     return $request->expectsJson()
    //         ? response()->json(['message' => "unauthor"], 401)
    //         : redirect()->guest(route('login'));
    // }

}
