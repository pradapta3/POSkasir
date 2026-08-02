<?php

namespace App\Models;

use App\Enums\LoyaltyMovementType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyPointMovement extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'customer_id',
        'user_id',
        'type',
        'points',
        'points_before',
        'points_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => LoyaltyMovementType::class,
            'points' => 'integer',
            'points_before' => 'integer',
            'points_after' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
