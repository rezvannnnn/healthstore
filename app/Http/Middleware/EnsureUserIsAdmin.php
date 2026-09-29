<?php

namespace AppHttpMiddleware;

use Closure;
use IlluminateHttpRequest;
use SymfonyComponentHttpFoundationResponse;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user?->canAccessAdminRoute($request->route()?->getName()),
            403
        );

        return $next($request);
    }
}
