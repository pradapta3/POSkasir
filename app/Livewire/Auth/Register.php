<?php

namespace App\Livewire\Auth;

use App\Enums\CompanyStatus;
use App\Enums\RoleEnum;
use App\Models\Company;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Self-service signup: one form creates a Company, its first Outlet, and
 * a Superadmin user in a single transaction, then logs the owner straight
 * in. This is the only place outside the Fase 1 backfill migration and
 * the Users admin screen that a Company/Outlet/User trio gets created.
 *
 * New companies start life 'pending' — a Platform Admin has to approve
 * them (Livewire\Platform\Companies\Index) before EnsureCompanyIsApproved
 * lets the owner past the operational app; see Auth\PendingApproval for
 * what they see in the meantime.
 */
#[Layout('layouts.guest')]
class Register extends Component
{
    public string $storeName = '';

    public string $ownerName = '';

    public string $email = '';

    public ?string $phone = null;

    public string $password = '';

    public string $passwordConfirmation = '';

    public function register(): void
    {
        $this->validate([
            'storeName' => 'required|string|max:255',
            'ownerName' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')],
            'password' => 'required|string|min:8',
        ]);

        if ($this->password !== $this->passwordConfirmation) {
            $this->addError('passwordConfirmation', 'Konfirmasi kata sandi tidak cocok.');

            return;
        }

        $user = DB::transaction(function (): User {
            $company = Company::create([
                'name' => $this->storeName,
                'slug' => $this->uniqueSlug(Str::slug($this->storeName)),
                'owner_email' => $this->email,
                // No billing yet — trial_ends_at is just recorded for when
                // that lands later, nothing currently reads or enforces it.
                'trial_ends_at' => now()->addDays(14),
                'is_active' => false,
                'status' => CompanyStatus::PENDING,
            ]);

            Outlet::create([
                'company_id' => $company->id,
                'name' => 'Outlet Utama',
                'is_active' => true,
            ]);

            return User::create([
                'company_id' => $company->id,
                'outlet_id' => null,
                'role_id' => Role::where('slug', RoleEnum::SUPERADMIN->value)->value('id'),
                'name' => $this->ownerName,
                'email' => $this->email,
                'phone' => $this->phone ?: null,
                'password' => $this->password,
                'is_active' => true,
            ]);
        });

        $user->sendEmailVerificationNotification();

        Auth::login($user);
        request()->session()->regenerate();

        $this->redirectRoute('company.pending', navigate: true);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base ?: 'toko';
        $suffix = 1;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.auth.register')->layoutData(['title' => 'Daftar Toko Baru']);
    }
}
