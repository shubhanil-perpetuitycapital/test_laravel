<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets only active back-office users (admin / staff) through.
 * A deactivated account is signed out on its next request.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessAdmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Your account does not have access to the admin area.']);
        }

        if ($role === 'owner' && ! $user->isOwner()) {
            abort(403, 'Only the store owner can change this.');
        }

        return $next($request);
    }
}
