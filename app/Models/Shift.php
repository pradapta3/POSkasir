<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'outlet_id',
        'user_id',
        'starting_cash',
        'expected_cash',
        'actual_cash',
        'difference',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShiftStatus::class,
            'starting_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', ShiftStatus::OPEN);
    }

    public function totalSales(): float
    {
        return (float) $this->transactions()
            ->where('payment_status', 'paid')
            ->sum('grand_total');
    }
}
