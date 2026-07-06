<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice_number }}</title>
    {{--
        Rendered by mPDF (pure-PHP, Arabic-capable). The SAME HTML is also served at
        /invoice/preview in the browser. To keep the on-screen preview and the downloaded PDF
        looking identical, both use the ReadexPro brand font (Fannan's font, Latin + Arabic).

        $browser is true only for the browser preview. When true the template adds an @font-face
        (loading ReadexPro over HTTP), the centered "paper" framing, the CSS watermark, and the
        dark footer bar. In the PDF those last two are drawn by mPDF natively (SetWatermarkImage /
        SetHTMLFooter) — see InvoiceController::renderPdf — so they are NOT emitted here.
    --}}
    <style>
        @page { margin: 24px 28px 40px 28px; }

        @if (!empty($browser))
        @font-face { font-family: 'ReadexPro'; font-weight: 400; font-style: normal;
            src: url('/front/dist/fonts/ReadexPro/ReadexPro-Regular.ttf') format('truetype'); }
        @font-face { font-family: 'ReadexPro'; font-weight: 700; font-style: normal;
            src: url('/front/dist/fonts/ReadexPro/ReadexPro-Bold.ttf') format('truetype'); }
        @endif

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'ReadexPro', 'DejaVu Sans', sans-serif;
            color: #333;
            font-size: 11px;
            line-height: 1.5;
        }

        .pink { color: #c1157c; }

        /* ---- Header ---- */
        .header-table { width: 100%; margin-bottom: 18px; }
        .logo-box {
            width: 66px; height: 66px;
            background: #c1157c;
            border-radius: 14px;
            text-align: center;
        }
        .doc-title { font-size: 26px; font-weight: bold; color: #c1157c; letter-spacing: 1px; }
        .doc-sub { font-size: 15px; color: #444; }

        /* ---- Invoice meta ---- */
        .meta { margin-bottom: 16px; }
        .meta p { margin: 1px 0; font-size: 11px; }
        .meta strong { color: #222; }
        .status-paid { color: #3a9d3a; font-weight: bold; }
        .status-pending { color: #d38b00; font-weight: bold; }
        .status-failed { color: #d32f2f; font-weight: bold; }

        /* ---- Billed By / To ---- */
        .billed-wrap {
            width: 100%;
            background: #f5f5f7;
            border-radius: 8px;
            margin-bottom: 22px;
        }
        .billed-wrap td { padding: 14px 16px; vertical-align: top; width: 50%; }
        .billed-wrap td.div { border-left: 1px solid #e2e2e6; }
        .billed-heading { color: #c1157c; font-weight: bold; font-size: 12px; margin-bottom: 6px; }
        .billed-line { font-size: 11px; color: #444; margin: 2px 0; }

        /* ---- Event title ---- */
        .event-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #2a2a2a;
            margin-bottom: 16px;
        }

        /* ---- Items table ---- */
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.items th {
            background: #7a1150;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #7a1150;
        }
        table.items td {
            font-size: 10px;
            color: #444;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #e2e2e6;
        }
        table.items tr:nth-child(even) td { background: #faf7f9; }

        /* ---- Totals (right-aligned via a wrapper table — robust in mPDF and browsers) ---- */
        .totals-wrap { width: 100%; margin-bottom: 10px; }
        .totals-wrap > tbody > tr > td { padding: 0; border: none; vertical-align: top; }
        table.totals { width: 100%; }
        table.totals td { padding: 5px 4px; font-size: 12px; }
        table.totals td.label { color: #555; }
        table.totals td.val { text-align: right; color: #333; }
        table.totals tr.total td {
            border-top: 1px solid #cfcfcf;
            padding-top: 9px;
            font-weight: bold;
            font-size: 13px;
        }
        table.totals tr.total td.label,
        table.totals tr.total td.val { color: #3a9d3a; }

        /* ---- Terms + footer ---- */
        .terms { padding-top: 6px; font-size: 10px; }
        .terms a { color: #2456c1; text-decoration: underline; }

        .footer-block { margin-top: 24px; font-size: 10px; color: #7a7a7a; line-height: 1.5; }
        .footer-block .fn { font-weight: bold; color: #555; }
        .system-note { margin-top: 8px; font-size: 10px; color: #9a9a9a; }
        .confidential { margin-top: 10px; text-align: center; font-size: 9px; color: #b3b3b3; }

        @if (!empty($browser))
        /* ---- Browser-preview-only chrome (mPDF draws these natively for the PDF) ---- */
        .watermark { position: absolute; top: 40%; left: 12%; width: 76%; text-align: center; opacity: 0.10; }
        .watermark img { width: 360px; }
        .bottom-bar {
            position: absolute; bottom: 0; left: -34px; width: calc(100% + 68px);
            background: #2b2b2b; color: #cfcfcf; font-size: 8px; text-align: center; padding: 7px 8px;
        }
        .bottom-bar .k { color: #8f8f8f; }

        body { background: #e9edf2; padding: 28px 0; }
        .page {
            position: relative;
            width: 794px; min-height: 1123px;
            margin: 0 auto;
            padding: 34px 34px 60px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 4px 22px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        @endif
    </style>
</head>
<body>
<div class="page">

    @if (!empty($browser) && !empty($logo_gold_src))
        <div class="watermark"><img src="{{ $logo_gold_src }}" alt=""></div>
    @endif

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td width="90" style="vertical-align:top;">
                <div class="logo-box">
                    @if (!empty($logo_white_src))
                        <img src="{{ $logo_white_src }}" width="48" style="margin-top:20px;" alt="Fannan">
                    @endif
                </div>
            </td>
            <td align="center" style="vertical-align:middle;">
                <div class="doc-title">INVOICE</div>
                <div class="doc-sub">{{ $company['name_short'] }}</div>
            </td>
            <td width="90"></td>
        </tr>
    </table>

    {{-- Invoice meta --}}
    <div class="meta">
        <p><strong>Invoice ID:</strong> {{ $invoice_number }}</p>
        <p><strong>Invoice Date:</strong> {{ $invoice_date }}</p>
        <p>
            <strong>Payment Status:</strong>
            <span class="status-{{ strtolower($payment_status) === 'paid' ? 'paid' : (strtolower($payment_status) === 'failed' ? 'failed' : 'pending') }}">{{ $payment_status }}</span>
        </p>
    </div>

    {{-- Billed By / Billed To --}}
    <table class="billed-wrap">
        <tr>
            <td>
                <div class="billed-heading">Billed By</div>
                <div class="billed-line">{{ $billed_by['company'] }}</div>
                <div class="billed-line">CR {{ $billed_by['cr'] }}</div>
                <div class="billed-line">TAX {{ $billed_by['tax'] }}</div>
                <div class="billed-line">{{ $billed_by['email'] }}</div>
            </td>
            <td class="div">
                <div class="billed-heading">Billed To</div>
                <div class="billed-line">Client ID: {{ $billed_to['client_id'] }}</div>
                <div class="billed-line">Name: {{ $billed_to['name'] }}</div>
                <div class="billed-line">Email: {{ $billed_to['email'] }}</div>
                <div class="billed-line">Phone: {{ $billed_to['phone'] }}</div>
            </td>
        </tr>
    </table>

    {{-- Event title --}}
    <div class="event-title">{{ $event_name }}</div>

    {{-- Items --}}
    <table class="items">
        <thead>
            <tr>
                <th width="13%">Artist-Name</th>
                <th width="9%">Artist-ID</th>
                <th width="30%">Address</th>
                <th width="18%">Description</th>
                <th width="15%">Start</th>
                <th width="15%">End</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item['artist_name'] }}</td>
                    <td>{{ $item['artist_id'] }}</td>
                    <td>{{ $item['address'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td>{{ $item['start'] }}</td>
                    <td>{{ $item['end'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No items</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-wrap">
        <tr>
            <td></td>
            <td width="290">
                <table class="totals">
                    <tr>
                        <td class="label">SubTotal</td>
                        <td class="val">{{ $totals['subtotal'] }} {{ $totals['currency'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Discount</td>
                        <td class="val">{{ $totals['discount'] }} {{ $totals['currency'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tax</td>
                        <td class="val">{{ $totals['tax'] }} {{ $totals['currency'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">VAT &amp; Payment Fees</td>
                        <td class="val">{{ $totals['vat_fees'] }} {{ $totals['currency'] }}</td>
                    </tr>
                    <tr class="total">
                        <td class="label">Total Paid</td>
                        <td class="val">{{ $totals['total_paid'] }} {{ $totals['currency'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Terms --}}
    <div class="terms">
        <span class="pink">Terms &amp; Conditions:</span>
        <a href="{{ $company['terms_url'] }}">{{ $company['terms_url'] }}</a>
    </div>

    {{-- Footer --}}
    <div class="footer-block">
        <div class="fn">{{ $company['name_short'] }}</div>
        <div>{{ $company['hq'] }}</div>
        <div>{{ $company['phone'] }} | {{ $billed_by['email'] }}</div>
        <div>CR: {{ $billed_by['cr'] }} | TAX: {{ $billed_by['tax'] }}</div>
        <div class="system-note">This is a system-generated invoice. No signature required.</div>
    </div>

    <div class="confidential">
        The proposed strategies, material, information &amp; ideas submitted by {{ $company['name'] }} for consideration are of a confidential nature
    </div>

    @if (!empty($browser))
        <div class="bottom-bar">
            <span class="k">Hotline</span> {{ $company['hotline'] }}
            &nbsp;&nbsp;<span class="k">CR</span> {{ str_replace(' ', '', $billed_by['cr']) }}
            &nbsp;&nbsp;<span class="k">Tax</span> {{ $billed_by['tax'] }}
            &nbsp;&nbsp;<span class="k">HQ</span> {{ $company['hq_short'] }}
            &nbsp;&nbsp;{{ $company['website'] }}
        </div>
    @endif

</div>
</body>
</html>
