<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Actions\Logout;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.pos')]
class Index extends Component
{
    use WithFileUploads;

    public string $storeName = '';

    public ?string $storeAddress = null;

    public ?string $storePhone = null;

    public ?string $receiptFooter = null;

    public float $taxPercentage = 0;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $storeLogo = null;

    public ?string $currentLogoUrl = null;

    public function mount(): void
    {
        $this->storeName = (string) Setting::get('store_name', '');
        $this->storeAddress = Setting::get('store_address');
        $this->storePhone = Setting::get('store_phone');
        $this->receiptFooter = Setting::get('receipt_footer');
        $this->taxPercentage = (float) Setting::get('tax_percentage', 0);
        $this->currentLogoUrl = $this->logoUrl();
    }

    public function save(): void
    {
        $this->validate([
            'storeName' => 'required|string|max:255',
            'storeAddress' => 'nullable|string|max:500',
            'storePhone' => 'nullable|string|max:20',
            'receiptFooter' => 'nullable|string|max:255',
            'taxPercentage' => 'required|numeric|min:0|max:100',
            'storeLogo' => 'nullable|image|max:1024',
        ]);

        Setting::set('store_name', $this->storeName);
        Setting::set('store_address', (string) $this->storeAddress);
        Setting::set('store_phone', (string) $this->storePhone);
        Setting::set('receipt_footer', (string) $this->receiptFooter);
        Setting::set('tax_percentage', (string) $this->taxPercentage);

        if ($this->storeLogo) {
            Setting::set('store_logo_path', $this->storeLogo->store('settings', 'public'));
            $this->storeLogo = null;
        }

        $this->currentLogoUrl = $this->logoUrl();

        $this->dispatch('settings-saved');
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function logoUrl(): ?string
    {
        $path = Setting::get('store_logo_path');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function render()
    {
        return view('livewire.admin.settings.index');
    }
}
