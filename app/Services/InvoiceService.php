<?php

namespace App\Services;

use App\Enums\OrderType;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Builds and renders order invoices. Extracted from InvoiceController so the same code path
 * serves the (participant-guarded) API download, the local preview, and the admin panel's
 * "Download invoice" action — without loosening the API's ownership check.
 */
class InvoiceService
{
    /**
     * The eager-load graph the invoice needs. Kept in one place so every caller loads
     * identical data.
     */
    public const INVOICE_RELATIONS = [
        'client', 'artist', 'address', 'address.city',
        'categories.subcategory', 'dates', 'userTransaction',
    ];

    /**
     * Render the invoice for an order to PDF bytes (convenience wrapper).
     */
    public function pdfForOrder(Order $order): string
    {
        return $this->renderPdf($this->prepareInvoiceData($order));
    }

    /**
     * Build all data the invoice template needs from an Order.
     */
    public function prepareInvoiceData(Order $order): array
    {
        $primaryDate = $order->dates->first();

        // [B4] Totals come from the single source of truth so the invoice can never disagree
        // with what the customer was quoted / charged (tax + VAT rates from the settings table).
        $cost = (float) ($order->total_cost ?? $order->cost ?? 0);
        $discount = (float) ($order->coupon_amount ?? 0);
        $breakdown = app(OrderPricingService::class)->breakdown($cost, $discount);

        return [
            'invoice_number' => $this->invoiceNumber($order),
            'invoice_date' => now()->format('D, n/j/Y'),
            'payment_status' => $order->is_paid ? 'PAID' : 'PENDING',
            'event_name' => $order->name ?: 'Order #' . $order->id,

            'company' => $this->companyDetails(),
            'billed_by' => $this->billedBy(),

            'billed_to' => [
                'client_id' => $order->client->id ?? 'N/A',
                'name' => $order->client->name ?? 'N/A',
                'email' => $order->client->email ?? 'N/A',
                'phone' => $order->client->phone ?? 'N/A',
            ],

            'items' => $this->buildItems($order, $primaryDate),

            'totals' => [
                'subtotal' => $this->money($breakdown['cost']),
                'discount' => $this->money($breakdown['discount']),
                'tax' => $this->money($breakdown['tax']),
                'vat_fees' => $this->money($breakdown['vat']),
                'total_paid' => $this->money($breakdown['total_cost']),
                'currency' => currency_code(), // config/fannan.php (APP_CURRENCY) — EGP/SAR switch
            ],
        ];
    }

    /**
     * One row per performing artist. Direct orders have a single artist; bidding orders may
     * have several accepted artists — all share the order's address, description and dates.
     */
    private function buildItems(Order $order, $primaryDate): array
    {
        $address = $order->address?->name ?? $order->address?->description ?? 'N/A';
        $description = $order->description ?: 'N/A';
        $start = $this->formatDateTime($primaryDate?->start_date, $primaryDate?->start_time);
        $end = $this->formatDateTime($primaryDate?->end_date, $primaryDate?->end_time);

        $artists = collect();
        if ($order->type === OrderType::BIDDING->value) {
            $artists = $order->acceptedBiddingOrderArtists()->with('artist')->get()
                ->pluck('artist')->filter()->values();
        }
        if ($artists->isEmpty() && $order->artist) {
            $artists = collect([$order->artist]);
        }

        return $artists->map(fn ($artist) => [
            'artist_name' => $artist->name ?? 'N/A',
            'artist_id' => $artist->id ?? 'N/A',
            'address' => $address,
            'description' => $description,
            'start' => $start,
            'end' => $end,
        ])->all();
    }

    /**
     * Stable invoice reference: INV-{4-digit hash of the order id}-{order id}. The same order
     * always yields the same number; swap for a real invoice sequence when finance needs one.
     */
    public function invoiceNumber(Order $order): string
    {
        $ref = str_pad((string) (crc32('fannan-invoice-' . $order->id) % 10000), 4, '0', STR_PAD_LEFT);

        return "INV-{$ref}-{$order->id}";
    }

    /**
     * Render the invoice Blade view to an HTML string.
     *
     * @param bool $browser true for the on-screen /invoice/preview (loads ReadexPro over HTTP and
     *                      draws the page frame / watermark / footer bar in CSS); false for mPDF,
     *                      which registers the font and draws watermark + footer natively.
     */
    public function renderHtml(array $data, bool $browser = false): string
    {
        return View::make('invoices.template', $data + $this->invoiceAssets() + ['browser' => $browser])->render();
    }

    /**
     * Render prepared invoice data to PDF bytes with mPDF.
     *
     * mPDF (not DomPDF) because it renders the ReadexPro brand font — so the PDF matches the
     * browser preview exactly — and it shapes Arabic (names/addresses) correctly, which DomPDF
     * cannot. The watermark and dark footer bar are drawn with mPDF's native features rather
     * than CSS `position: fixed`, which mPDF does not support the way DomPDF did.
     */
    public function renderPdf(array $data): string
    {
        $defaultFontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $defaultFontData = (new FontVariables())->getDefaults()['fontdata'];

        // Keep mPDF's font cache / scratch files inside storage (writable on shared hosting),
        // not vendor/ which is often read-only in production.
        $tempDir = storage_path('framework/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 10,
            'margin_bottom' => 18,
            'fontDir' => array_merge($defaultFontDirs, [public_path('front/dist/fonts/ReadexPro')]),
            'fontdata' => $defaultFontData + [
                'readexpro' => ['R' => 'ReadexPro-Regular.ttf', 'B' => 'ReadexPro-Bold.ttf'],
            ],
            'default_font' => 'readexpro',
            'autoScriptToLang' => true,   // detect script (e.g. Arabic) per run
            'autoLangToFont' => true,     // and substitute a font that can render it
            'useSubstitutions' => true,   // per-glyph fallback for anything ReadexPro lacks
        ]);

        // Faded gold Fannan mark, centered on every page. The size MUST carry an explicit height
        // ([w, h] in mm) — mPDF renders nothing if the height is 0/auto. 130x72 keeps the logo's
        // ~1954:1082 aspect ratio.
        $watermark = public_path('images/logo-gold.png');
        if (is_file($watermark)) {
            $mpdf->SetWatermarkImage($watermark, 0.13, [130, 72], 'P');
            $mpdf->showWatermarkImage = true;
        }

        // Dark hotline/registration bar repeated at the bottom of every page.
        $mpdf->SetHTMLFooter($this->footerBarHtml($data));

        $mpdf->WriteHTML($this->renderHtml($data, false));

        return $mpdf->Output('', 'S');
    }

    /**
     * The dark bottom bar, as an mPDF page footer (full-bleed, repeated each page).
     */
    private function footerBarHtml(array $data): string
    {
        $company = $data['company'];
        $cr = str_replace(' ', '', $data['billed_by']['cr']);
        $tax = $data['billed_by']['tax'];
        $k = 'color:#8f8f8f;';

        return '<div style="background:#2b2b2b; color:#cfcfcf; font-size:7px; text-align:center; padding:6px 8px; margin:0 -12px;">'
            . '<span style="' . $k . '">Hotline</span> ' . e($company['hotline'])
            . ' &nbsp; <span style="' . $k . '">CR</span> ' . e($cr)
            . ' &nbsp; <span style="' . $k . '">Tax</span> ' . e($tax)
            . ' &nbsp; <span style="' . $k . '">HQ</span> ' . e($company['hq_short'])
            . ' &nbsp; ' . e($company['website'])
            . '</div>';
    }

    /**
     * Logos as base64 data URIs so they embed directly in the HTML (header mark + browser watermark).
     */
    private function invoiceAssets(): array
    {
        return [
            // Full-colour brand logo used in the invoice header (falls back to the constructed
            // pink-box + white mark in the template when this file is absent).
            'logo_invoice_src' => $this->asDataUri(public_path('images/logo-invoice.png')),
            'logo_white_src' => $this->asDataUri(public_path('images/logo-white.png')),
            'logo_gold_src' => $this->asDataUri(public_path('images/logo-gold.png')),
        ];
    }

    private function asDataUri(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode((string) file_get_contents($path));
    }

    /**
     * Company / issuer details shown on every invoice. Matches the Fannan branding on the
     * invoice mock-up; swap to the `settings` table later if these need to be editable.
     */
    private function companyDetails(): array
    {
        return [
            'name' => 'Fannan',
            'name_short' => 'Fannan',
            'legal_name' => 'Fannan LLC',
            'hq' => 'HQ Office - 85 Street 9, Maadi, Cairo, 11511',
            'hq_short' => '85 Street 9 Maadi Cairo Egypt',
            'phone' => '+2010 2888 0909',
            'hotline' => '0102 888 0909',
            'website' => 'Fannan.ai',
            'terms_url' => 'https://fannan.ai/terms-and-conditions',
        ];
    }

    /**
     * Fixed issuer tax identity (Billed By block).
     */
    private function billedBy(): array
    {
        return [
            'company' => 'Fannan LLC',
            'cr' => '10530 0000 271325',
            'tax' => '4220216263694642',
            'email' => 'info@fannan.ai',
        ];
    }

    /**
     * "Tue, 9/1/2026 15:00:00" when a time is present, otherwise "Tue, 9/1/2026".
     */
    private function formatDateTime($date, $time = null): string
    {
        if (!$date) {
            return 'N/A';
        }

        try {
            $dt = $this->toCarbon($date);

            if ($time) {
                $t = $this->toCarbon(is_string($time) ? '2000-01-01 ' . $time : $time);
                $dt->setTime((int) $t->format('H'), (int) $t->format('i'), (int) $t->format('s'));

                return $dt->format('D, n/j/Y H:i:s');
            }

            return $dt->format('D, n/j/Y');
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    private function toCarbon($date): Carbon
    {
        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    /**
     * Whole numbers show without decimals ("0", "1500"); fractional amounts show two ("1500.50").
     */
    private function money(float $amount): string
    {
        return fmod($amount, 1.0) === 0.0
            ? number_format($amount, 0)
            : number_format($amount, 2);
    }

    /**
     * Built-in sample used by the local preview when the database has no orders, so the
     * design can be viewed on a fresh/empty local DB. Mirrors the invoice mock-up.
     */
    public function sampleInvoiceData(): array
    {
        return [
            'invoice_number' => 'INV-7436-272',
            'invoice_date' => 'Mon, 7/6/2026',
            'payment_status' => 'PAID',
            'event_name' => 'Test Event',

            'company' => $this->companyDetails(),
            'billed_by' => $this->billedBy(),

            'billed_to' => [
                'client_id' => 203,
                'name' => 'Khaled',
                'email' => 'khaled-hossam@outlook.com',
                'phone' => '1020700343',
            ],

            'items' => [[
                'artist_name' => 'Alberto',
                'artist_id' => 201,
                'address' => 'Giza Al Hosary Mosque, Block 21, 6th Of October',
                'description' => 'Test Description',
                'start' => 'Tue, 9/1/2026 15:00:00',
                'end' => 'Wed, 9/16/2026 15:00:00',
            ]],

            'totals' => [
                'subtotal' => '0', 'discount' => '0', 'tax' => '0',
                'vat_fees' => '0', 'total_paid' => '0', 'currency' => currency_code(),
            ],
        ];
    }
}
