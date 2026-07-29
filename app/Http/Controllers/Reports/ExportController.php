<?php

namespace App\Http\Controllers\Reports;

use App\Exports\TransactionsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function transactions(Request $request): BinaryFileResponse
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $filename = 'transactions_'.$from->format('Ymd').'_'.$to->format('Ymd').'.xlsx';

        return Excel::download(new TransactionsExport($from, $to), $filename);
    }
}
