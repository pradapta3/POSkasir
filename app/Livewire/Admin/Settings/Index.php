<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Actions\Logout;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.pos')]
class Index extends Component
{
    public float $taxPercentage = 0;

    public function mount(): void
    {
        $this->taxPercentage = (float) Setting::get('tax_percentage', 0);
    }

    public function save(): void
    {
        $this->validate([
            'taxPercentage' => 'required|numeric|min:0|max:100',
        ]);

        Setting::set('tax_percentage', (string) $this->taxPercentage);

        $this->dispatch('settings-saved');
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.settings.index');
    }
}
