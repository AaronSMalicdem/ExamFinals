<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Admin
{
    public function handle(Request $request, Closure $next)
    {

        if (!Auth::user()->isAdmin()){
            return response()->json(['error' => 'Unauthorized: Admin access required'], 403);
        }
        return $next($request);
    }
}
