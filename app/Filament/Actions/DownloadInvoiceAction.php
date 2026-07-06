<?php

namespace App\Filament\Actions;

use App\Models\Order;
use App\Services\InvoiceService;
use Filament\Tables\Actions\Action;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reusable table row action that streams an order's invoice PDF. Usable from any resource whose
 * record is an Order (DirectOrder, BiddingOrder, Invoices). The Filament panel is already gated to
 * admins (User::canAccessPanel), so this is allowed to render any order's invoice — it deliberately
 * bypasses the API's client/artist participant guard, which stays in place for the public endpoint.
 */
class DownloadInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'invoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(trans('app.download_invoice'))
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->action(function (Order $record): StreamedResponse {
                $service = app(InvoiceService::class);
                $order = $record->loadMissing(InvoiceService::INVOICE_RELATIONS);

                return response()->streamDownload(
                    fn () => print($service->pdfForOrder($order)),
                    $service->invoiceNumber($order) . '.pdf',
                    ['Content-Type' => 'application/pdf'],
                );
            });
    }
}
