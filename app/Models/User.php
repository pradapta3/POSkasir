<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Enums\ShiftStatus;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Deliberately does NOT use the BelongsToCompany trait (no automatic
 * CompanyScope) — Auth's internal retrieveById() must be able to load a
 * user without already knowing their company_id, or login would be
 * circular. Screens that list users (Admin\Users\Index) filter by
 * company_id explicitly instead; see that component for the pattern.
 *
 * Implements MustVerifyEmail so self-registered owners get a verification
 * email, but no route applies the 'verified' middleware — staff accounts
 * created by an admin (Admin\Users\Index) never see a verification email
 * and shouldn't be locked out for it. Unverified users just see a
 * dismissible reminder banner (Auth\VerifyEmailBanner).
 */
class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory, MustVerifyEmail, Notifiable;

    protected $fillable = [
        'company_id',
        'outlet_id',
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Null means "every outlet in the company" (Superadmin/Manager) — a
     * Cashier is always pinned to exactly one.
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function hasRole(RoleEnum $role): bool
    {
        return $this->role?->slug === $role->value;
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole(RoleEnum::SUPERADMIN);
    }

    public function isManager(): bool
    {
        return $this->hasRole(RoleEnum::MANAGER);
    }

    public function isCashier(): bool
    {
        return $this->hasRole(RoleEnum::CASHIER);
    }

    /**
     * The SaaS operator, not a store's own admin — their company_id row is
     * just an FK anchor (users.company_id is NOT NULL), unrelated to their
     * actual duties. See EnsureCompanyIsApproved.
     */
    public function isPlatformAdmin(): bool
    {
        return $this->hasRole(RoleEnum::PLATFORM_ADMIN);
    }

    public function canAccessOutlet(Outlet|int $outlet): bool
    {
        if ($this->outlet_id === null) {
            return true;
        }

        return $this->outlet_id === ($outlet instanceof Outlet ? $outlet->id : $outlet);
    }

    public function activeShift(): ?Shift
    {
        return $this->shifts()
            ->where('status', ShiftStatus::OPEN->value)
            ->latest('opened_at')
            ->first();
    }
}
