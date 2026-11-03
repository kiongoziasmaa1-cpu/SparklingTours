<?php

namespace App\Http\Controllers;

use App\Services\InvoiceService;
use App\Services\PdfService;

class DocumentController extends Controller
{
    public function receipt(string $invoice, string $token, int $payment, InvoiceService $s, PdfService $pdf)
    {
        $i = $s->resolve($invoice, $token);
        $p = $i->payments()->findOrFail($payment);

        return $pdf->receipt($i, $p);
    }

    public function show(string $invoice, string $token, InvoiceService $s)
    {
        return view('documents.invoice', ['invoice' => $s->resolve($invoice, $token), 'token' => $token]);
    }

    public function pdf(string $invoice, string $token, InvoiceService $s, PdfService $pdf)
    {
        return $pdf->invoice($s->resolve($invoice,$token));
    }
}
