<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RedirectOldSlugs
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && preg_match('#^(products|categories|brands|blog)/.+#', $request->path())) {
            $new = DB::table('slug_redirects')->where('old_path', '/'.$request->path())->value('new_path');
            if (is_string($new) && $new !== '/'.$request->path()) {
                return redirect($new, 301);
            }
        }

        return $next($request);
    }
}
