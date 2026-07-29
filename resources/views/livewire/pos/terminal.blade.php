@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
@endphp

<div class="flex h-screen flex-col bg-slate-50">
    {{-- Toast --}}
    <div
        x-data="{ show: false, message: '', receiptUrl: null }"
        x-on:transaction-completed.window="
            message = 'Transaksi ' + $event.detail.invoiceNumber + ' selesai.';
            receiptUrl = '/pos/receipt/' + $event.detail.transactionId;
            show = true;
            setTimeout(() => show = false, 8000)
        "
        x-on:order-held.window="message = 'Pesanan ditahan untuk nanti.'; receiptUrl = null; show = true; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition
        style="display: none;"
        class="fixed top-4 right-4 z-50 flex items-center gap-3 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg shadow-emerald-600/20"
    >
        <x-icon name="check-circle" class="h-5 w-5 shrink-0" />
        <span x-text="message"></span>
        <a
            x-show="receiptUrl"
            :href="receiptUrl"
            target="_blank"
            class="shrink-0 rounded-lg bg-white/20 px-3 py-1.5 text-xs font-semibold transition hover:bg-white/30"
        >
            Cetak Struk
        </a>
    </div>

    {{-- Header --}}
    <header class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 text-sm font-bold text-white">P</span>
                <span class="text-lg font-bold text-slate-900">POS Kasir</span>
            </div>
            @if ($this->activeShift)
                <span class="flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Shift dibuka &middot; {{ $this->activeShift->opened_at->diffForHumans() }}
                </span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if ($this->activeShift)
                <button
                    wire:click="$set('showCloseShiftModal', true)"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Tutup Shift
                </button>
            @endif
            @include('partials.admin-nav')
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        {{-- Product browser --}}
        <section class="flex w-2/3 flex-col overflow-hidden border-r border-slate-200 bg-slate-50">
            <div class="border-b border-slate-200 bg-white p-4">
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute inset-y-0 left-3 my-auto h-5 w-5 text-slate-400" />
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        wire:keydown.enter.prevent="scanBarcode"
                        placeholder="Pindai barcode atau cari nama produk / SKU..."
                        autofocus
                        class="w-full rounded-lg border-slate-300 py-2.5 pl-10 text-base shadow-sm focus:border-rose-500 focus:ring-rose-500"
                    >
                </div>
                @error('search')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        wire:click="filterByCategory(null)"
                        class="rounded-full px-3 py-1 text-sm font-medium transition {{ is_null($activeCategoryId) ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/30' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100' }}"
                    >
                        Semua
                    </button>
                    @foreach ($this->categories as $category)
                        <button
                            wire:click="filterByCategory({{ $category->id }})"
                            class="rounded-full px-3 py-1 text-sm font-medium transition {{ $activeCategoryId === $category->id ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/30' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100' }}"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid flex-1 grid-cols-4 gap-3 overflow-y-auto p-4 content-start">
                @forelse ($this->productList as $product)
                    <button
                        wire:key="product-{{ $product->id }}"
                        wire:click="addToCart({{ $product->id }})"
                        @if ($product->stock_quantity <= 0) disabled @endif
                        class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-rose-300 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0"
                    >
                        <div class="aspect-square w-full overflow-hidden bg-slate-100">
                            @if ($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <x-icon name="photo" class="h-8 w-8 text-slate-300" />
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col items-start p-3">
                            <span class="line-clamp-2 text-sm font-semibold text-slate-800 group-hover:text-rose-700">{{ $product->name }}</span>
                            <span class="mt-1 text-xs text-slate-400">{{ $product->sku }}</span>
                            <span class="mt-2 text-sm font-bold text-rose-600">{{ $rp($product->selling_price) }}</span>
                            <span class="mt-1 text-xs {{ $product->isLowStock() ? 'font-medium text-amber-600' : 'text-slate-400' }}">
                                Stok: {{ $product->stock_quantity }}
                            </span>
                        </div>
                    </button>
                @empty
                    <div class="col-span-4 flex flex-col items-center justify-center py-16 text-center">
                        <x-icon name="cube" class="h-10 w-10 text-slate-300" />
                        <p class="mt-2 text-sm text-slate-400">Produk tidak ditemukan.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Cart --}}
        <section class="flex w-1/3 flex-col bg-white">
            @if ($resumingTransactionId)
                <div class="flex items-center justify-between bg-amber-50 px-4 py-2 text-sm text-amber-800">
                    <span>Melanjutkan pesanan tertahan #{{ $resumingTransactionId }}</span>
                    <button wire:click="clearCart" class="font-medium underline">Batal</button>
                </div>
            @endif

            <div class="flex-1 overflow-y-auto p-4">
                @error('cart')
                    <p class="mb-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('checkout')
                    <p class="mb-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @forelse ($cart as $key => $line)
                    <div wire:key="cart-{{ $key }}" class="mb-3 flex items-start justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-800">{{ $line['name'] }}</p>
                            <p class="text-xs text-slate-400">{{ $rp($line['price']) }} &times; {{ $line['quantity'] }}</p>
                            <div class="mt-1.5 flex items-center gap-2">
                                <button wire:click="decrementQuantity({{ $line['product_id'] }})" class="flex h-6 w-6 items-center justify-center rounded bg-slate-100 text-slate-600 hover:bg-slate-200">&minus;</button>
                                <input
                                    type="number"
                                    value="{{ $line['quantity'] }}"
                                    wire:change="updateQuantity({{ $line['product_id'] }}, $event.target.value)"
                                    class="h-6 w-12 rounded border-slate-200 p-0 text-center text-sm"
                                >
                                <button
                                    wire:click="incrementQuantity({{ $line['product_id'] }})"
                                    @if ($line['quantity'] >= $line['max_quantity']) disabled @endif
                                    class="flex h-6 w-6 items-center justify-center rounded bg-slate-100 text-slate-600 hover:bg-slate-200 disabled:opacity-40"
                                >+</button>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-800">{{ $rp($line['price'] * $line['quantity']) }}</p>
                            <button wire:click="removeFromCart({{ $line['product_id'] }})" class="mt-1 text-xs text-red-500 hover:underline">Hapus</button>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <x-icon name="receipt" class="h-10 w-10 text-slate-300" />
                        <p class="mt-2 text-sm text-slate-400">Keranjang kosong.<br>Pindai atau ketuk produk untuk menambahkannya.</p>
                    </div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 bg-slate-50/60 p-4">
                {{-- Discount --}}
                <div class="mb-3 flex items-center justify-between text-sm">
                    <span class="text-slate-500">Diskon</span>
                    <div class="flex gap-1">
                        @foreach ([5, 10, 15] as $pct)
                            <button
                                wire:click="applyDiscount('percentage', {{ $pct }})"
                                class="rounded px-2 py-1 text-xs font-medium {{ $discountType === 'percentage' && (float) $discountValue === (float) $pct ? 'bg-rose-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100' }}"
                            >{{ $pct }}%</button>
                        @endforeach
                        <button wire:click="applyDiscount(null, 0)" class="rounded px-2 py-1 text-xs font-medium text-slate-400 hover:bg-slate-100">Bersihkan</button>
                    </div>
                </div>

                <div class="mb-3 flex items-center justify-between text-sm">
                    <label for="taxPercentage" class="text-slate-500">Pajak (%)</label>
                    <input
                        id="taxPercentage"
                        type="number"
                        step="0.01"
                        wire:model.live="taxPercentage"
                        class="w-20 rounded border-slate-300 text-right text-sm"
                    >
                </div>

                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <dt>Subtotal</dt>
                        <dd>{{ $rp($this->totals['subtotal']) }}</dd>
                    </div>
                    @if ($this->totals['discountAmount'] > 0)
                        <div class="flex justify-between text-rose-600">
                            <dt>Diskon</dt>
                            <dd>&minus;{{ $rp($this->totals['discountAmount']) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between text-slate-600">
                        <dt>Pajak</dt>
                        <dd>{{ $rp($this->totals['taxAmount']) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-slate-900">
                        <dt>Total</dt>
                        <dd>{{ $rp($this->totals['grandTotal']) }}</dd>
                    </div>
                </dl>

                <div class="mt-4 grid grid-cols-3 gap-2">
                    <button wire:click="clearCart" class="rounded-lg border border-slate-300 bg-white py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Kosongkan
                    </button>
                    <button wire:click="holdOrder" class="relative rounded-lg border border-slate-300 bg-white py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Tahan
                    </button>
                    <button wire:click="$set('showHeldOrders', true)" class="relative rounded-lg border border-slate-300 bg-white py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Tertahan ({{ $this->heldOrders->count() }})
                    </button>
                </div>

                <button
                    wire:click="openPaymentModal"
                    class="mt-2 w-full rounded-lg bg-rose-600 py-3 text-base font-bold text-white shadow-md shadow-rose-600/25 transition hover:bg-rose-700"
                >
                    Bayar {{ $rp($this->totals['grandTotal']) }}
                </button>
            </div>
        </section>
    </div>

    {{-- Payment modal --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">Pembayaran</h2>

                @error('checkout')
                    <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-4 grid grid-cols-3 gap-2">
                    @foreach (['cash' => 'Tunai', 'qris' => 'QRIS', 'gopay' => 'GoPay', 'card' => 'Kartu', 'other' => 'Lainnya'] as $value => $label)
                        <button
                            wire:click="$set('paymentMethod', '{{ $value }}')"
                            class="rounded-lg border py-2 text-sm font-medium transition {{ $paymentMethod === $value ? 'border-rose-600 bg-rose-50 text-rose-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>

                @if ($paymentMethod === 'cash')
                    <div class="mt-4">
                        <label class="text-sm font-medium text-slate-600">Uang Diterima</label>
                        <input type="number" step="0.01" wire:model.live="amountPaid" class="mt-1 w-full rounded-lg border-slate-300 text-lg focus:border-rose-500 focus:ring-rose-500">
                        @error('amountPaid')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="mt-2 grid grid-cols-4 gap-2">
                            @foreach ($this->cashSuggestions as $suggestion)
                                <button
                                    type="button"
                                    wire:click="setAmountPaid({{ $suggestion }})"
                                    class="rounded-lg border py-2 text-xs font-medium transition {{ (float) $amountPaid === (float) $suggestion ? 'border-rose-600 bg-rose-50 text-rose-700' : 'border-slate-300 text-slate-600 hover:border-rose-300 hover:bg-rose-50' }}"
                                >{{ $rp($suggestion) }}</button>
                            @endforeach
                        </div>

                        <p class="mt-3 text-sm text-slate-500">
                            Kembalian: <span class="font-semibold text-slate-800">{{ $rp(max(0, $amountPaid - $this->totals['grandTotal'])) }}</span>
                        </p>
                    </div>
                @else
                    <p class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500">
                        Kode {{ strtoupper($paymentMethod) }} dinamis senilai {{ $rp($this->totals['grandTotal']) }} akan dibuat setelah pembayaran dikonfirmasi.
                    </p>
                @endif

                <div class="mt-4">
                    <label class="text-sm font-medium text-slate-600">Nomor HP pelanggan (opsional — untuk invoice digital)</label>
                    <input type="text" wire:model="customerPhone" placeholder="08xxxxxxxxxx" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    <input type="text" wire:model="customerName" placeholder="Nama pelanggan (opsional)" class="mt-2 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                </div>

                <div class="mt-6 flex gap-2">
                    <button wire:click="$set('showPaymentModal', false)" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="checkout" wire:loading.attr="disabled" class="flex-1 rounded-lg bg-rose-600 py-2 font-bold text-white hover:bg-rose-700 disabled:opacity-60">
                        Konfirmasi Pembayaran
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Dynamic QRIS modal --}}
    @if ($showQrisModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl" wire:poll.3s="refreshQrisStatus">
                <h2 class="text-lg font-bold text-slate-900">Pindai untuk Bayar</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $qrisInvoiceNumber }}</p>

                @if ($qrisUrl)
                    <img src="{{ $qrisUrl }}" alt="Kode QRIS dinamis" class="mx-auto mt-4 h-64 w-64 rounded-lg border border-slate-200 object-contain">
                @endif

                <div class="mt-4 flex items-center justify-center gap-2 text-sm text-slate-500">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-amber-500"></span>
                    Menunggu konfirmasi pembayaran&hellip;
                </div>

                <button
                    wire:click="$set('showQrisModal', false)"
                    class="mt-6 w-full rounded-lg border border-slate-300 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Tutup
                </button>
                <p class="mt-2 text-xs text-slate-400">
                    Transaksi sudah tercatat sebagai tertunda — menutup ini hanya menghentikan pengecekan status di sini; webhook tetap akan menandainya lunas begitu Midtrans mengonfirmasi.
                </p>
            </div>
        </div>
    @endif

    {{-- Held orders modal --}}
    @if ($showHeldOrders)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900">Pesanan Tertahan</h2>
                    <button wire:click="$set('showHeldOrders', false)" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <div class="mt-4 max-h-96 space-y-2 overflow-y-auto">
                    @forelse ($this->heldOrders as $held)
                        <div wire:key="held-{{ $held->id }}" class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $held->invoice_number }}</p>
                                <p class="text-xs text-slate-400">{{ $held->items_count }} item &middot; {{ $held->held_at?->diffForHumans() }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="resumeOrder({{ $held->id }})" class="rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-700">
                                    Lanjutkan
                                </button>
                                <button wire:click="deleteHeldOrder({{ $held->id }})" wire:confirm="Hapus pesanan tertahan ini?" class="rounded-lg border border-red-300 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-400">Tidak ada pesanan tertahan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Open shift modal (blocking) --}}
    @if ($showOpenShiftModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100">
                    <x-icon name="wallet" class="h-6 w-6 text-rose-600" />
                </div>
                <h2 class="mt-3 text-center text-lg font-bold text-slate-900">Buka Shift</h2>
                <p class="mt-1 text-center text-sm text-slate-500">Masukkan modal awal di laci kasir untuk memulai.</p>

                <label class="mt-4 block text-sm font-medium text-slate-600">Modal Awal</label>
                <input type="number" step="0.01" wire:model="startingCash" class="mt-1 w-full rounded-lg border-slate-300 text-lg focus:border-rose-500 focus:ring-rose-500">
                @error('startingCash')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <button wire:click="openShift" class="mt-4 w-full rounded-lg bg-rose-600 py-2.5 font-bold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700">
                    Mulai Shift
                </button>
            </div>
        </div>
    @endif

    {{-- Close shift modal --}}
    @if ($showCloseShiftModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">Tutup Shift</h2>
                <p class="mt-1 text-sm text-slate-500">Hitung uang di laci dan masukkan jumlah kas aktual.</p>

                <label class="mt-4 block text-sm font-medium text-slate-600">Kas Aktual</label>
                <input type="number" step="0.01" wire:model="actualCash" class="mt-1 w-full rounded-lg border-slate-300 text-lg focus:border-rose-500 focus:ring-rose-500">
                @error('actualCash')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-4 flex gap-2">
                    <button wire:click="$set('showCloseShiftModal', false)" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="closeShift" class="flex-1 rounded-lg bg-slate-900 py-2 font-bold text-white hover:bg-slate-800">
                        Tutup Shift
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
