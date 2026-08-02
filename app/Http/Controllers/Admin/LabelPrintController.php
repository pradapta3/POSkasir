<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LabelPrintController extends Controller
{
    /**
     * Reads the selection Labels\Index staged in the session (see that
     * class's docblock for why this hands off via session rather than a
     * wire:navigate transition) and renders the printable sheet. Redirects
     * back to the builder if hit directly with nothing staged — e.g. the
     * tab was reopened, or the session expired.
     */
    public function __invoke(): View|RedirectResponse
    {
        $items = session('label_print_items');

        if (empty($items)) {
            return redirect()->route('admin.labels');
        }

        return view('admin.labels-print', ['items' => $items]);
    }
}
