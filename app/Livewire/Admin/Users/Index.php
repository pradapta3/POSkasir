<?php

namespace App\Livewire\Admin\Users;

use App\Enums\RoleEnum;
use App\Livewire\Actions\Logout;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * User has no automatic CompanyScope (see Concerns\BelongsToCompany's
 * docblock — Auth's retrieveById() can't already know the company_id of
 * the user it's loading). Every query here filters by company_id by hand
 * instead; skipping it on any one of them would let a Manager in one
 * company view or edit another company's staff by guessing an ID.
 */
#[Layout('layouts.pos')]
class Index extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?int $roleId = null;

    public ?int $outletId = null;

    public bool $isActive = true;

    public string $password = '';

    public string $passwordConfirmation = '';

    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->where('company_id', Auth::user()->company_id)
            // platform_admin accounts are anchored to a real company only
            // to satisfy the NOT NULL constraint (see DatabaseSeeder) —
            // they're not that company's staff and must stay invisible and
            // unmanageable here, or any Superadmin could edit/deactivate
            // the platform's own review account.
            ->whereRelation('role', 'slug', '!=', RoleEnum::PLATFORM_ADMIN->value)
            ->with(['role', 'outlet'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function outlets(): Collection
    {
        return Outlet::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
    }

    /**
     * A Manager can staff Cashier/Manager accounts but never grant or edit
     * the Superadmin role — that's reserved for a Superadmin to hand out.
     * platform_admin is never assignable here at all, by anyone — it's a
     * platform-wide role (approves/rejects every company's signup, see
     * EnsureCompanyIsApproved) with no relationship to any one company, and
     * this screen only ever operates within the actor's own company.
     */
    #[Computed]
    public function assignableRoles(): Collection
    {
        $roles = Role::query()
            ->where('slug', '!=', RoleEnum::PLATFORM_ADMIN->value)
            ->orderBy('id')
            ->get();

        if (! Auth::user()->isSuperadmin()) {
            $roles = $roles->reject(fn (Role $role) => $role->slug === RoleEnum::SUPERADMIN->value);
        }

        return $roles;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $userId): void
    {
        $user = $this->companyUsers()->findOrFail($userId);

        if (! $this->canManage($user)) {
            $this->addError('users', 'Hanya Superadmin yang bisa mengubah akun Superadmin lain.');

            return;
        }

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->roleId = $user->role_id;
        $this->outletId = $user->outlet_id;
        $this->isActive = $user->is_active;
        $this->password = '';
        $this->passwordConfirmation = '';

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $assignableRoleIds = $this->assignableRoles->pluck('id');

        $this->validate([
            'name' => 'required|string|max:255',
            // email/phone are intentionally globally unique (not per-company)
            // — see the users migration — so no company_id filter belongs here.
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($this->editingId)],
            'roleId' => ['required', Rule::in($assignableRoleIds)],
            'outletId' => ['nullable', Rule::exists('outlets', 'id')->where('company_id', Auth::user()->company_id)],
        ]);

        $isNewPassword = $this->password !== '';

        if (! $this->editingId || $isNewPassword) {
            if (strlen($this->password) < 8) {
                $this->addError('password', 'Kata sandi minimal 8 karakter.');

                return;
            }

            if ($this->password !== $this->passwordConfirmation) {
                $this->addError('password', 'Konfirmasi kata sandi tidak cocok.');

                return;
            }
        }

        $attributes = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'role_id' => $this->roleId,
            'outlet_id' => $this->outletId,
            'is_active' => $this->isActive,
        ];

        if ($isNewPassword) {
            // User::$casts hashes this automatically on save (Laravel's
            // 'hashed' cast) — no need to call Hash::make() here.
            $attributes['password'] = $this->password;
        }

        if ($this->editingId) {
            $user = $this->companyUsers()->findOrFail($this->editingId);

            if (! $this->canManage($user)) {
                $this->addError('users', 'Hanya Superadmin yang bisa mengubah akun Superadmin lain.');

                return;
            }

            $user->update($attributes);
        } else {
            User::create($attributes + ['company_id' => Auth::user()->company_id]);
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->users);
    }

    public function toggleActive(int $userId): void
    {
        if ($userId === Auth::id()) {
            $this->addError('users', 'Kamu tidak bisa menonaktifkan akunmu sendiri.');

            return;
        }

        $user = $this->companyUsers()->findOrFail($userId);

        if (! $this->canManage($user)) {
            $this->addError('users', 'Hanya Superadmin yang bisa menonaktifkan akun Superadmin lain.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        unset($this->users);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function companyUsers()
    {
        return User::where('company_id', Auth::user()->company_id)
            ->whereRelation('role', 'slug', '!=', RoleEnum::PLATFORM_ADMIN->value);
    }

    private function canManage(User $target): bool
    {
        return ! $target->isSuperadmin() || Auth::user()->isSuperadmin();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'roleId', 'outletId', 'password', 'passwordConfirmation']);
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.users.index');
    }
}
