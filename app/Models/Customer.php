<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Not every row here is a loyalty-program "member" — a phone captured at
 * checkout just for a WhatsApp invoice stays a plain Customer with
 * is_member = false. See LoyaltyService for how points move, and
 * Livewire\Admin\Members\Index for enrollment.
 */
class Customer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'phone', 'email', 'address',
        'is_member', 'member_code', 'loyalty_points', 'member_since',
    ];

    protected function casts(): array
    {
        return [
            'is_member' => 'boolean',
            'loyalty_points' => 'integer',
            'member_since' => 'datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function loyaltyPointMovements(): HasMany
    {
        return $this->hasMany(LoyaltyPointMovement::class);
    }
}
