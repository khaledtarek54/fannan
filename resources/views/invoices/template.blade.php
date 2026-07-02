<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .company-info h1 {
            font-size: 28px;
            color: #c91f6e;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .company-info p {
            font-size: 11px;
            color: #666;
            margin: 2px 0;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }

        .invoice-meta {
            display: flex;
            gap: 20px;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }

        .meta-item {
            flex: 1;
        }

        .meta-item strong {
            display: block;
            color: #333;
        }

        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            width: 50%;
        }

        .info-value {
            text-align: left;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table thead {
            background-color: #f5f5f5;
        }

        table th {
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            color: #333;
            border: 1px solid #ddd;
        }

        table td {
            padding: 10px;
            font-size: 12px;
            color: #666;
            border: 1px solid #ddd;
        }

        table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .service-details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .financial-summary {
            margin-left: auto;
            width: 300px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 8px;
            padding-bottom: 8px;
        }

        .summary-row.total {
            border-top: 2px solid #333;
            padding-top: 8px;
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            font-weight: 600;
            color: #333;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #999;
        }

        .bank-details {
            background-color: #f5f5f5;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .bank-details-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .bank-details-text {
            font-size: 11px;
            color: #666;
            margin: 2px 0;
        }

        .page-break {
            page-break-after: always;
        }

        .highlight {
            background-color: #fff3e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $company['name'] }}</h1>
                <p>{{ $company['address'] }}</p>
                <p>{{ $company['phone'] }}</p>
                <p>{{ $company['taxId'] }}</p>
                <p>{{ $company['website'] }}</p>
            </div>
            <div class="invoice-title">
                <h2>Invoice</h2>
                <p><strong>Invoice No.</strong> {{ $invoice_number }}</p>
                <p><strong>Invoice Date</strong> {{ $invoice_date }}</p>
            </div>
        </div>

        <!-- Invoice Meta -->
        <div class="invoice-meta">
            <div class="meta-item">
                <strong>Order ID:</strong>
                {{ $order_id }}
            </div>
            <div class="meta-item">
                <strong>Payment Status:</strong>
                {{ $payment['status'] }}
            </div>
            <div class="meta-item">
                <strong>Currency:</strong>
                {{ $financial['currency'] }}
            </div>
        </div>

        <!-- Client and Service Details -->
        <div class="content">
            <!-- Client Information -->
            <div>
                <div class="section-title">Client Information</div>
                <div class="info-group">
                    <div class="info-row">
                        <span class="info-label">Client Name:</span>
                        <span class="info-value">{{ $client['name'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Client ID:</span>
                        <span class="info-value">{{ $client['id'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $client['email'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">{{ $client['phone'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Service Information -->
            <div>
                <div class="section-title">Service Information</div>
                <div class="info-group">
                    <div class="info-row">
                        <span class="info-label">Artist Name:</span>
                        <span class="info-value">{{ $service['artist_name'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Category:</span>
                        <span class="info-value">{{ $service['category'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type:</span>
                        <span class="info-value">{{ $service['type'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value">{{ $service['address'] }}, {{ $service['address_city'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Details Table -->
        <table class="full-width">
            <thead>
                <tr>
                    <th>Header</th>
                    <th>Information</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Artist Name</td>
                    <td>{{ $service['artist_name'] }}</td>
                </tr>
                <tr>
                    <td>Category</td>
                    <td>{{ $service['category'] }}</td>
                </tr>
                <tr>
                    <td>Type</td>
                    <td>{{ $service['type'] }}</td>
                </tr>
                <tr>
                    <td>Address</td>
                    <td>{{ $service['address'] }}, {{ $service['address_city'] }} - Near King Khalid Branch Rd</td>
                </tr>
                <tr>
                    <td>Address Description</td>
                    <td>{{ $service['description'] }}</td>
                </tr>
                <tr>
                    <td>Start Date</td>
                    <td>{{ $dates['start_date'] }}</td>
                </tr>
                <tr>
                    <td>End Date</td>
                    <td>{{ $dates['end_date'] }}</td>
                </tr>
                <tr>
                    <td>Start Time</td>
                    <td>{{ $dates['start_time'] }}</td>
                </tr>
                <tr>
                    <td>End Time</td>
                    <td>{{ $dates['end_time'] }}</td>
                </tr>
                <tr>
                    <td>Time Period</td>
                    <td>{{ $dates['time_period'] }}</td>
                </tr>
                <tr>
                    <td>Description</td>
                    <td>{{ $service['category'] }}</td>
                </tr>
                <tr>
                    <td>Number of Days</td>
                    <td>{{ $dates['number_of_days'] }}</td>
                </tr>
                <tr>
                    <td>Daily Hours</td>
                    <td>{{ $dates['daily_hours'] }}</td>
                </tr>
                <tr>
                    <td>Total Hours</td>
                    <td>{{ $dates['total_hours'] }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Financial Summary -->
        <div class="financial-summary">
            <div class="summary-row">
                <span class="summary-label">Service Cost:</span>
                <span class="summary-value">{{ $financial['service_cost'] }} {{ $financial['currency'] }}</span>
            </div>
            @if ($financial['discount'] > 0)
                <div class="summary-row">
                    <span class="summary-label">Discount:</span>
                    <span class="summary-value highlight" style="color: #d32f2f;">-{{ $financial['discount'] }} {{ $financial['currency'] }}</span>
                </div>
            @endif
            <div class="summary-row">
                <span class="summary-label">Tax:</span>
                <span class="summary-value">{{ $financial['tax'] }} {{ $financial['currency'] }}</span>
            </div>
            @if ($financial['vat'] > 0)
                <div class="summary-row">
                    <span class="summary-label">VAT:</span>
                    <span class="summary-value">{{ $financial['vat'] }} {{ $financial['currency'] }}</span>
                </div>
            @endif
            <div class="summary-row total">
                <span class="summary-label">Total:</span>
                <span class="summary-value">{{ $financial['total'] }} {{ $financial['currency'] }}</span>
            </div>
        </div>

        <!-- Bank Details -->
        <div class="bank-details">
            <div class="bank-details-title">{{ $bank_details['bank_name'] }}</div>
            <div class="bank-details-text">IBAN: {{ $bank_details['iban'] }}</div>
            <div class="bank-details-text">{{ $bank_details['swift'] }}</div>
            <div class="bank-details-text">Contact: +20(2) 088 0000 - info@fannanonline.com</div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="text-align: center; margin-top: 20px;">
                Thank you for your business! This is an automatically generated invoice.
            </p>
        </div>
    </div>
</body>
</html>
