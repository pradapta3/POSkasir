<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'phone', 'email', 'address'];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
