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

    public string $receiptPaperWidth = '80';

    public float $taxPercentage = 0;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $storeLogo = null;

    public ?string $currentLogoUrl = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $qrisImage = null;

    public ?string $currentQrisImageUrl = null;

    public bool $loyaltyEnabled = false;

    public float $loyaltyEarnPerRupiah = 0;

    public float $loyaltyRedeemValue = 0;

    public int $loyaltyMinRedeemPoints = 0;

    public function mount(): void
    {
        $this->storeName = (string) Setting::get('store_name', '');
        $this->storeAddress = Setting::get('store_address');
        $this->storePhone = Setting::get('store_phone');
        $this->receiptFooter = Setting::get('receipt_footer');
        $this->receiptPaperWidth = (string) Setting::get('receipt_paper_width', '80');
        $this->taxPercentage = (float) Setting::get('tax_percentage', 0);
        $this->currentLogoUrl = $this->imageUrl('store_logo_path');
        $this->currentQrisImageUrl = $this->imageUrl('qris_image_path');
        $this->loyaltyEnabled = (bool) Setting::get('loyalty_enabled', false);
        $this->loyaltyEarnPerRupiah = (float) Setting::get('loyalty_earn_per_rupiah', 0);
        $this->loyaltyRedeemValue = (float) Setting::get('loyalty_redeem_value', 0);
        $this->loyaltyMinRedeemPoints = (int) Setting::get('loyalty_min_redeem_points', 0);
    }

    public function save(): void
    {
        $this->validate([
            'storeName' => 'required|string|max:255',
            'storeAddress' => 'nullable|string|max:500',
            'storePhone' => 'nullable|string|max:20',
            'receiptFooter' => 'nullable|string|max:255',
            'receiptPaperWidth' => 'required|in:58,80',
            'taxPercentage' => 'required|numeric|min:0|max:100',
            'storeLogo' => 'nullable|image|max:1024',
            'qrisImage' => 'nullable|image|max:1024',
            'loyaltyEarnPerRupiah' => 'required_if:loyaltyEnabled,true|numeric|min:0',
            'loyaltyRedeemValue' => 'required_if:loyaltyEnabled,true|numeric|min:0',
            'loyaltyMinRedeemPoints' => 'required|integer|min:0',
        ]);

        Setting::set('store_name', $this->storeName);
        Setting::set('store_address', (string) $this->storeAddress);
        Setting::set('store_phone', (string) $this->storePhone);
        Setting::set('receipt_footer', (string) $this->receiptFooter);
        Setting::set('receipt_paper_width', $this->receiptPaperWidth);
        Setting::set('tax_percentage', (string) $this->taxPercentage);
        Setting::set('loyalty_enabled', $this->loyaltyEnabled ? '1' : '0');
        Setting::set('loyalty_earn_per_rupiah', (string) $this->loyaltyEarnPerRupiah);
        Setting::set('loyalty_redeem_value', (string) $this->loyaltyRedeemValue);
        Setting::set('loyalty_min_redeem_points', (string) $this->loyaltyMinRedeemPoints);

        if ($this->storeLogo) {
            Setting::set('store_logo_path', $this->storeLogo->store('settings', 'public'));
            $this->storeLogo = null;
        }

        if ($this->qrisImage) {
            Setting::set('qris_image_path', $this->qrisImage->store('settings', 'public'));
            $this->qrisImage = null;
        }

        $this->currentLogoUrl = $this->imageUrl('store_logo_path');
        $this->currentQrisImageUrl = $this->imageUrl('qris_image_path');

        $this->dispatch('settings-saved');
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function imageUrl(string $settingKey): ?string
    {
        $path = Setting::get($settingKey);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function render()
    {
        return view('livewire.admin.settings.index');
    }
}
