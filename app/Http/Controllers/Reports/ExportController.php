<?php

namespace App\Http\Controllers\Reports;

use App\Exports\TransactionsExport;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function transactions(Request $request): BinaryFileResponse
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        // Re-validated against the company here rather than trusted from
        // the query string — a manipulated outlet_id must never export
        // another company's data.
        $outletId = Outlet::where('company_id', Auth::user()->company_id)
            ->where('id', $request->integer('outlet_id'))
            ->value('id');

        $filename = 'transactions_'.$from->format('Ymd').'_'.$to->format('Ymd').'.xlsx';

        return Excel::download(new TransactionsExport($from, $to, $outletId), $filename);
    }
}
