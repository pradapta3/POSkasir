<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'address', 'phone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * The outlet a Manager/Superadmin picked via OutletSwitcher — null if
     * they haven't picked one (or picked "Semua Outlet"), never anything
     * for a Cashier (who's pinned to users.outlet_id, not this session
     * value — see Terminal::currentOutlet()). Re-validated against the
     * company and active status on every read so a stale session entry
     * (outlet since deactivated, or leftover from a different company on
     * a shared browser) can never leak through.
     */
    public static function currentSessionOutlet(User $user): ?self
    {
        $selectedId = session('current_outlet_id');

        if (! $selectedId) {
            return null;
        }

        return static::where('company_id', $user->company_id)
            ->where('id', $selectedId)
            ->where('is_active', true)
            ->first();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }
}
