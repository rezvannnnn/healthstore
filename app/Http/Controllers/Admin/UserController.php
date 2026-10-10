<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->whereIn('role', [
                User::ROLE_ADMIN,
                User::ROLE_STAFF,
                User::ROLE_STORAGEKEEPER,
            ])
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'admin_username',
                'admin_title',
                'role',
                'admin_active',
                'admin_permissions',
                'created_at',
            ])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->admin_username,
                'title' => $user->admin_title,
                'role' => $user->isAdmin() ? 'admin' : 'staff',
                'active' => $user->admin_active,
                'permission_count' => $user->adminPermissionCount(),
                'permissions' => array_values($user->admin_permissions ?? []),
                'created_at' => $user->created_at?->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'permissionLabels' => config('admin.permissions'),
            'permissionGroups' => config('admin.permission_groups'),
            'currentUserId' => $request->user()?->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request, true);
        $isAdmin = (bool) ($data['is_admin'] ?? false);

        User::create([
            'name' => trim($data['name']),
            'admin_username' => Str::lower(trim($data['username'])),
            'admin_title' => trim((string) ($data['title'] ?? '')) ?: null,
            'password' => $data['password'],
            'role' => $isAdmin ? User::ROLE_ADMIN : User::ROLE_STAFF,
            'is_admin' => $isAdmin,
            'admin_active' => true,
            'admin_permissions' => $isAdmin
                ? []
                : $this->normalizePermissions($data['permissions'] ?? []),
        ]);

        return to_route('admin.users.index')->with('success', 'کاربر پنل با موفقیت ایجاد شد.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdminPanelUser(), 404);

        $data = $this->validatedData($request, false, $user);
        $isAdmin = (bool) ($data['is_admin'] ?? false);
        $isActive = (bool) ($data['is_active'] ?? true);

        if ($user->id === $request->user()?->id && (! $isAdmin || ! $isActive)) {
            return back()->withErrors([
                'is_active' => 'مدیر فعلی نمی‌تواند حساب خودش را به کاربر محدود یا غیرفعال کند.',
            ]);
        }

        $user->fill([
            'name' => trim($data['name']),
            'admin_username' => Str::lower(trim($data['username'])),
            'admin_title' => trim((string) ($data['title'] ?? '')) ?: null,
            'role' => $isAdmin ? User::ROLE_ADMIN : User::ROLE_STAFF,
            'is_admin' => $isAdmin,
            'admin_active' => $isActive,
            'admin_permissions' => $isAdmin
                ? []
                : $this->normalizePermissions($data['permissions'] ?? []),
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
            $user->remember_token = Str::random(60);
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)->delete();
            }
        }

        $user->save();

        return to_route('admin.users.index')->with('success', 'کاربر پنل با موفقیت به‌روزرسانی شد.');
    }

    protected function validatedData(Request $request, bool $creating, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'admin_username')->ignore($user?->id),
            ],
            'title' => ['nullable', 'string', 'max:100'],
            'is_admin' => ['boolean'],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(array_keys(config('admin.permissions', [])))],
        ];

        $rules['password'] = $creating
            ? ['required', 'string', Password::min(12)]
            : ['nullable', 'string', Password::min(12)];

        return $request->validate($rules);
    }

    protected function normalizePermissions(array $permissions): array
    {
        $permissions = array_values(array_unique($permissions));

        foreach ([
            'products.manage' => 'products.view',
            'inventory.manage' => 'inventory.view',
            'orders.manage' => 'orders.view',
        ] as $manage => $view) {
            if (in_array($manage, $permissions, true) && ! in_array($view, $permissions, true)) {
                $permissions[] = $view;
            }
        }

        return $permissions;
    }
}
