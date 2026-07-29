<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;

class ReceiptController extends Controller
{
    public function __invoke(Transaction $transaction): View
    {
        $transaction->load(['items', 'user', 'customer']);

        return view('pos.receipt', ['transaction' => $transaction]);
    }
}
