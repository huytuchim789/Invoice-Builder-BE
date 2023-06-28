<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->subcription('default')->canceled() && !$user->hasExpiredTrial('default') ) {
            return $next($request);
        }

        // Subscription has expired or user is not subscribed
        if ($user) {
            $user->role = 'guest';
            $user->save();
        }

        return response()->json([
            'message' => 'Access denied. Please subscribe to access this resource.',
        ], 403);
    }
}
