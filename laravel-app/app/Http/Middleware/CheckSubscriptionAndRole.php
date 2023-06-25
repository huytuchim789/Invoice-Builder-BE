<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckSubscriptionAndRole
{
    public function handle($request, Closure $next, $role, $permission)
    {
        $user = Auth::user();

        if ($user && $user->subscribed('default') && $user->hasRole($role) && $user->can($permission)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Access denied. Please subscribe and have the necessary role and permission to access this resource.',
        ], 403);
    }
}
