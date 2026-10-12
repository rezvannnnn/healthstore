<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('admin.dashboard');
        }

        if (
            $request->route()?->getName() === 'admin.dashboard'
            && $user->isAdminPanelUser()
            && ! $user->isAdmin()
            && $user->admin_active
        ) {
            $landing = $user->adminLandingPath();

            if ($landing !== null) {
                return redirect()->to($landing);
            }
        }

        if ($user->two_factor_confirmed_at && $request->session()->get('admin_mfa_verified') !== $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('admin.login');
        }
        if (str_starts_with((string) $request->route()?->getName(), 'admin.security.')) {
            abort_unless($user->isAdminPanelUser() && $user->admin_active, 403);

            return $next($request);
        }
        abort_unless(
            $user->canAccessAdminRoute($request->route()?->getName()),
            403
        );

        return $next($request);
    }
}
