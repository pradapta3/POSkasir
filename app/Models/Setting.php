<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Lightweight key-value store for per-company configuration (tax rate,
 * store name/address, receipt footer, etc.) — add more keys here as the
 * settings screen grows rather than adding columns to unrelated tables.
 */
class Setting extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'key', 'value'];

    /**
     * $companyId is only needed for callers with no authenticated user in
     * scope — queued jobs (SendWhatsAppInvoiceJob) and similar — where it
     * must be the specific company the surrounding record belongs to, not
     * whichever user happens to be logged in (there may be none). Every
     * other caller can omit it and rely on the ambient auth scope.
     */
    public static function get(string $key, mixed $default = null, ?int $companyId = null): mixed
    {
        $query = $companyId
            ? static::withoutGlobalScope(CompanyScope::class)->where('company_id', $companyId)
            : static::query();

        return $query->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value, ?int $companyId = null): void
    {
        $companyId ??= Auth::user()?->company_id;

        static::withoutGlobalScope(CompanyScope::class)->updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => $value]
        );
    }
}
