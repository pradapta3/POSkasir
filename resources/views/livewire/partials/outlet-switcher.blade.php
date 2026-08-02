{{--
    Livewire needs a single static root element to attach wire:id to — a
    top-level @if here (instead of inside the div) leaves the real content
    outside Livewire's tracked DOM, silently breaking wire:change.
--}}
<div>
    @if (Auth::user()->outlet_id === null && $this->outlets->count() > 1)
        <div class="border-b border-slate-200 px-3 py-2">
            <select
                wire:change="switchOutlet($event.target.value || null)"
                class="w-full rounded-lg border-slate-300 py-1.5 text-xs font-medium text-slate-600 focus:border-rose-500 focus:ring-rose-500"
            >
                <option value="" @selected(! $this->selectedOutletId)>Semua Outlet</option>
                @foreach ($this->outlets as $outlet)
                    <option value="{{ $outlet->id }}" @selected($this->selectedOutletId === $outlet->id)>{{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
</div>
