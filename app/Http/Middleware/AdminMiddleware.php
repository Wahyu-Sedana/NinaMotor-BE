<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            return redirect()->route('admin.login')
                ->with('error', 'Please login to access the admin panel.');
        }

        $user = Auth::user();

        // Check if user has admin role
        if ($user->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Forbidden. Admin access required.'
                ], 403);
            }

            // Log unauthorized access attempt
            \Log::warning('Unauthorized admin access attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            Auth::logout();

            return redirect()->route('admin.login')
                ->with('error', 'You do not have permission to access the admin panel.');
        }

        // Check if user account is active (if you have status field)
        if (isset($user->status) && $user->status !== 'active') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Account deactivated.'
                ], 403);
            }

            Auth::logout();

            return redirect()->route('admin.login')
                ->with('error', 'Your account has been deactivated. Please contact administrator.');
        }

        return $next($request);
    }
}
