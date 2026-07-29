<?php

namespace App\Livewire\Admin\Users;

use App\Enums\RoleEnum;
use App\Livewire\Actions\Logout;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.pos')]
class Index extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?int $roleId = null;

    public bool $isActive = true;

    public string $password = '';

    public string $passwordConfirmation = '';

    #[Computed]
    public function users(): Collection
    {
        return User::query()->with('role')->orderBy('name')->get();
    }

    /**
     * A Manager can staff Cashier/Manager accounts but never grant or edit
     * the Superadmin role — that's reserved for a Superadmin to hand out.
     */
    #[Computed]
    public function assignableRoles(): Collection
    {
        $roles = Role::query()->orderBy('id')->get();

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
        $user = User::findOrFail($userId);

        if (! $this->canManage($user)) {
            $this->addError('users', 'Hanya Superadmin yang bisa mengubah akun Superadmin lain.');

            return;
        }

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->roleId = $user->role_id;
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
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($this->editingId)],
            'roleId' => ['required', Rule::in($assignableRoleIds)],
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
            'is_active' => $this->isActive,
        ];

        if ($isNewPassword) {
            // User::$casts hashes this automatically on save (Laravel's
            // 'hashed' cast) — no need to call Hash::make() here.
            $attributes['password'] = $this->password;
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);

            if (! $this->canManage($user)) {
                $this->addError('users', 'Hanya Superadmin yang bisa mengubah akun Superadmin lain.');

                return;
            }

            $user->update($attributes);
        } else {
            User::create($attributes);
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

        $user = User::findOrFail($userId);

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

    private function canManage(User $target): bool
    {
        return ! $target->isSuperadmin() || Auth::user()->isSuperadmin();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'roleId', 'password', 'passwordConfirmation']);
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.users.index');
    }
}
