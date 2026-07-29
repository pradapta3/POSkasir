<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Enums\ShiftStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
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

    public function activeShift(): ?Shift
    {
        return $this->shifts()
            ->where('status', ShiftStatus::OPEN->value)
            ->latest('opened_at')
            ->first();
    }
}
