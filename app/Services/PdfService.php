<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    public function receipt(Invoice $invoice, Payment $payment)
    {
        abort_unless($payment->invoice_id === $invoice->id, 404);
        $options = new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'chroot' => public_path()]);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('documents.receipt', ['invoice' => $invoice, 'payment' => $payment])->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="RECEIPT-'.$payment->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }

    public function invoice(Invoice $invoice)
    {
        $invoice->load('items', 'payments', 'booking');
        $options = new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'chroot' => public_path(), 'tempDir' => storage_path('app/private/pdf-temp')]);
        if (! is_dir(storage_path('app/private/pdf-temp'))) {
            mkdir(storage_path('app/private/pdf-temp'), 0750, true);
        }
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('documents.pdf', ['invoice' => $invoice])->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.($invoice->number ?? 'DRAFT-'.$invoice->id).'.pdf"', 'Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
