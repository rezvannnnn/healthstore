<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function __invoke(): Response
    {
        $logs = DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.actor_id')
            ->select('audit_logs.*', 'users.name as actor_name')->orderByDesc('audit_logs.id')->paginate(50);

        return Inertia::render('Admin/Audit', ['logs' => $logs]);
    }
}
