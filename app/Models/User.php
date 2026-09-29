<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    public const ROLE_STORAGEKEEPER = 'storagekeeper';

    public const ROLE_STAFF = 'staff';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_verified_at',
        'password',
        'is_admin',
        'role',
        'admin_username',
        'admin_title',
        'admin_active',
        'admin_permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'admin_active' => 'boolean',
        'admin_permissions' => 'array',
    ];

    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->is_admin === true;
    }

    public function isAdminPanelUser(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_STAFF,
            self::ROLE_STORAGEKEEPER,
        ], true) || $this->is_admin === true;
    }

    public function hasAdminPermission(string $permission): bool
    {
        if (! $this->isAdminPanelUser() || ! $this->admin_active) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return in_array(
            $permission,
            array_values($this->admin_permissions ?? []),
            true
        );
    }

    public function canAccessAdminRoute(?string $routeName): bool
    {
        if (! $this->isAdminPanelUser() || ! $this->admin_active) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        if ($routeName === 'admin.logout') {
            return true;
        }

        if ($routeName === 'admin.dashboard') {
            return false;
        }

        if (in_array($routeName, config('admin.admin_only_routes', []), true)) {
            return false;
        }

        $routePermissions = config('admin.route_permissions', []);
        $permission = is_string($routeName)
            ? ($routePermissions[$routeName] ?? null)
            : null;

        return is_string($permission) && $this->hasAdminPermission($permission);
    }

    public function adminLandingPath(): ?string
    {
        foreach (config('admin.landing_routes', []) as $routeName) {
            if ($routeName === 'admin.dashboard' && ! $this->isAdmin()) {
                continue;
            }

            if ($this->canAccessAdminRoute($routeName)) {
                return route($routeName);
            }
        }

        return null;
    }

    public function adminPermissionCount(): int
    {
        if ($this->isAdmin()) {
            return count(config('admin.permissions', []));
        }

        return count(array_intersect(
            array_keys(config('admin.permissions', [])),
            array_values($this->admin_permissions ?? [])
        ));
    }
}
