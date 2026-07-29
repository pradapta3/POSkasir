<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once a sale is finalized at the terminal. Phase 3 (QRIS) listens to
 * this to generate a dynamic payment code for non-cash methods, and Phase 4
 * (WhatsApp) listens to this to queue the digital invoice — keeping the POS
 * terminal itself free of payment-gateway and messaging concerns.
 */
class TransactionCheckedOut
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Transaction $transaction)
    {
    }
}
