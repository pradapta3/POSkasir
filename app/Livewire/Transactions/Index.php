<?php

namespace App\Livewire\Transactions;

use App\Livewire\Actions\Logout;
use App\Models\Outlet;
use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Open to every authenticated role (see routes/web.php) — a Cashier needs
 * to find and reprint a receipt for a sale they rang up without asking a
 * manager, same as they can already print one right after checkout.
 */
#[Layout('layouts.pos')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $range = 'today';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRange(): void
    {
        $this->resetPage();
    }

    /**
     * A Cashier only ever sees their own outlet's history (they're pinned
     * via users.outlet_id, same as the Terminal). A Manager/Superadmin sees
     * whatever OutletSwitcher currently has selected, or every outlet in
     * the company if they haven't picked one.
     */
    #[Computed]
    public function outletId(): ?int
    {
        $user = Auth::user();

        return $user->outlet_id ?? Outlet::currentSessionOutlet($user)?->id;
    }

    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        [$from, $to] = match ($this->range) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'all' => [null, null],
            default => [now()->startOfDay(), now()->endOfDay()],
        };

        return Transaction::query()
            ->with('user')
            ->when($this->outletId, fn ($q) => $q->where('outlet_id', $this->outletId))
            // Excludes still-held carts, which never get a payment_method
            // and can't produce a meaningful receipt.
            ->whereNotNull('payment_method')
            ->when($from, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->when($this->search, fn ($q) => $q->where('invoice_number', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.transactions.index');
    }
}
