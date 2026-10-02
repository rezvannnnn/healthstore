<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        abort_unless(
            $user->canAccessAdminRoute($request->route()?->getName()),
            403
        );

        return $next($request);
    }
}
