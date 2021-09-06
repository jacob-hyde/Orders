<?php

namespace JacobHyde\Orders\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $isAdmin = config('orders.api_client')::isAdmin();
        if (!$isAdmin) {
            return response()->json([
                'data' => ['success' => false, 'error' => 'INVALID_PERMISSION'],
            ], 401);
        }

        return $next($request);
    }
}
