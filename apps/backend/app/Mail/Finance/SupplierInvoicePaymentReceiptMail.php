<?php

namespace App\Mail\Finance;

use App\Models\DigitalFileModel;
use App\Models\Finance\SupplierInvoiceModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupplierInvoicePaymentReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public SupplierInvoiceModel $invoice,
        public DigitalFileModel $paymentReceipt
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Comprovativo de Pagamento - Fatura "%s"', $this->invoice->number)
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.finance.supplier-invoice-payment-receipt'
        );
    }

    public function build(): static
    {
        $extension = pathinfo((string) $this->paymentReceipt->file_path, PATHINFO_EXTENSION);
        $filename = $this->paymentReceipt->name.($extension !== '' ? '.'.$extension : '');

        return $this->attachFromStorageDisk(
            'private',
            $this->paymentReceipt->file_path,
            $filename,
            ['mime' => $this->paymentReceipt->mime_type ?? 'application/octet-stream']
        );
    }
}
