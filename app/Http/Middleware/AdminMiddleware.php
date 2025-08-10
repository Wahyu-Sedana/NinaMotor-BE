<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Explicitly use web guard
        $user = Auth::guard('web')->user();

        if (!$user) {
            return $this->unauthenticatedResponse($request);
        }

        if ($user->role !== 'admin') {
            return $this->forbiddenResponse($request, $user);
        }

        if (isset($user->status) && $user->status !== 'active') {
            return $this->deactivatedResponse($request);
        }

        // Set user ke default guard untuk compatibility
        Auth::setUser($user);

        return $next($request);
    }

    protected function unauthenticatedResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        return redirect()->route('admin.login')
            ->with('error', 'Please login to access the admin panel.');
    }

    protected function forbiddenResponse(Request $request, $user)
    {
        \Log::warning('Unauthorized admin access attempt', [
            'user_id' => $user->id ?? null,
            'email'   => $user->email ?? null,
            'role'    => $user->role ?? null,
            'ip'      => $request->ip(),
            'url'     => $request->fullUrl(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        return redirect()->route('admin.login')
            ->with('error', 'You do not have permission to access the admin panel.');
    }

    protected function deactivatedResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Account deactivated.'], 403);
        }

        return redirect()->route('admin.login')
            ->with('error', 'Your account has been deactivated. Please contact administrator.');
    }
}
