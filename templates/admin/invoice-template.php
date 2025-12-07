<?php
/**
 * Invoice PDF Template
 * @version 2.2.57
 */
if (!defined('ABSPATH')) exit;
// $invoice, $customer, $branding variables available here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo esc_html($invoice->invoice_number); ?></title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 40px;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }
        .business-info {
            float: left;
            width: 50%;
        }
        .business-info h2 {
            margin: 0 0 10px 0;
            font-size: 20px;
            font-weight: bold;
        }
        .business-info p {
            margin: 0;
            color: #555;
        }
        .logo-container {
            float: right;
            width: 40%;
            text-align: right;
        }
        .studiofy-logo-svg {
            width: 180px;
            height: auto;
        }
        .invoice-title {
            clear: both;
            text-align: right;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #333;
            margin-top: 20px;
            margin-bottom: 40px;
            text-transform: uppercase;
        }
        .invoice-details-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .bill-to {
            float: left;
            width: 50%;
        }
        .bill-to h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: bold;
            color: #555;
        }
        .bill-to p {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .bill-to .address {
            font-weight: normal;
            color: #555;
            white-space: pre-wrap; 
        }
        .invoice-meta {
            float: right;
            width: 40%;
            text-align: right;
        }
        .invoice-meta table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-meta th {
            text-align: right;
            padding: 5px 15px 5px 0;
            font-weight: bold;
            color: #555;
        }
        .invoice-meta td {
            text-align: right;
            padding: 5px 0;
            font-weight: bold;
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            text-align: left;
            padding: 15px 10px;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            font-size: 12px;
        }
        .items-table th.col-qty, .items-table td.col-qty { text-align: center; width: 10%; }
        .items-table th.col-desc, .items-table td.col-desc { text-align: left; width: 50%; }
        .items-table th.col-price, .items-table td.col-price { text-align: right; width: 20%; }
        .items-table th.col-amount, .items-table td.col-amount { text-align: right; width: 20%; }
        .items-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        .totals-container {
            float: right;
            width: 40%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table th {
            text-align: right;
            padding: 10px 15px 10px 0;
            font-weight: bold;
            color: #555;
        }
        .totals-table td {
            text-align: right;
            padding: 10px 0;
            font-weight: bold;
            color: #333;
        }
        .totals-table tr.total-row th, .totals-table tr.total-row td {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            font-size: 16px;
            padding: 15px 0;
        }
        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .footer h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: bold;
            color: #555;
        }
        .footer p {
            margin: 0 0 5px 0;
            color: #777;
            font-size: 12px;
        }
        .payable-to {
            margin-top: 20px;
            font-weight: bold;
            color: #333;
        }
        .business-email {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <div class="header-container clearfix">
        <div class="business-info">
            <h2><?php echo esc_html($business_name); ?></h2>
            <p>1234 Photography Ln.<br>City, ST 12345</p>
        </div>
        <div class="logo-container">
            <?php if (!empty($business_logo_url)): ?>
                <img src="<?php echo esc_url($business_logo_url); ?>" style="max-height: 80px; width: auto;">
            <?php else: ?>
                <svg class="studiofy-logo-svg" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs><linearGradient id="lg" x1="200" y1="130" x2="300" y2="230" gradientUnits="userSpaceOnUse"><stop stop-color="#4f94d4"/><stop offset="1" stop-color="#2271b1"/></linearGradient></defs>
                    <g><rect x="100" y="80" width="300" height="200" rx="20" fill="black"/><path d="M180 80 L210 40 H290 L320 80 H180 Z" fill="black"/><rect x="120" y="70" width="40" height="10" rx="2" fill="black"/><circle cx="250" cy="180" r="85" fill="white"/><circle cx="250" cy="180" r="75" fill="black"/><circle cx="250" cy="180" r="60" fill="url(#lg)"/><ellipse cx="270" cy="160" rx="20" ry="12" transform="rotate(-45 270 160)" fill="white" fill-opacity="0.4"/><circle cx="230" cy="200" r="5" fill="white" fill-opacity="0.2"/><rect x="115" y="100" width="15" height="160" rx="5" fill="#333333"/></g>
                    <g><text x="250" y="340" font-family="Helvetica" font-size="60" text-anchor="middle" fill="black"><tspan font-weight="900" letter-spacing="2">STUDIOFY</tspan></text></g>
                </svg>
            <?php endif; ?>
            <p class="business-email"><?php echo esc_html($admin_email); ?></p>
        </div>
    </div>

    <h1 class="invoice-title">PHOTOGRAPHY INVOICE</h1>

    <div class="invoice-details-container clearfix">
        <div class="bill-to">
            <h3>Bill To</h3>
            <p><?php echo $customer->name; ?></p>
            <?php if($customer->company): ?><p><?php echo $customer->company; ?></p><?php endif; ?>
            <p class="address"><?php echo $customer->address; ?></p>
        </div>
        <div class="invoice-meta">
            <table>
                <tr><th>Invoice #</th><td><?php echo esc_html($invoice->invoice_number); ?></td></tr>
                <tr><th>Date</th><td><?php echo esc_html($invoice->issue_date_formatted); ?></td></tr>
                <tr><th>Due</th><td><?php echo esc_html($invoice->due_date_formatted); ?></td></tr>
                <?php if(!empty($invoice->payment_method)): ?>
                <tr><th>Method</th><td><?php echo esc_html($invoice->payment_method); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-qty" style="text-align:center;">QTY</th>
                <th class="col-desc">Description</th>
                <th class="col-price" style="text-align:right;">Unit Price</th>
                <th class="col-amount" style="text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoice->line_items_data as $item): $line_total = (float)$item['qty'] * (float)$item['rate']; ?>
            <tr>
                <td style="text-align:center;"><?php echo esc_html($item['qty']); ?></td>
                <td><?php echo esc_html($item['desc']); ?></td>
                <td style="text-align:right;"><?php echo number_format((float)$item['rate'], 2); ?></td>
                <td style="text-align:right;"><?php echo number_format($line_total, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-container clearfix">
        <table class="totals-table">
            <tr>
                <th>Subtotal</th>
                <td><?php echo number_format($invoice->subtotal, 2); ?></td>
            </tr>
            
            <?php if((float)$invoice->tax_amount > 0): ?>
                <tr><th>Tax</th><td><?php echo number_format((float)$invoice->tax_amount, 2); ?></td></tr>
            <?php else: ?>
                <tr><th>Tax</th><td>Tax Exemption</td></tr>
            <?php endif; ?>

            <?php if((float)$invoice->service_fee > 0): ?>
                <tr><th>Service Fee (3%)</th><td><?php echo number_format((float)$invoice->service_fee, 2); ?></td></tr>
            <?php endif; ?>

            <tr class="total-row">
                <th>Total (<?php echo esc_html($invoice->currency); ?>)</th>
                <td><?php echo number_format((float)$invoice->amount, 2); ?></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <h4>Terms and Conditions</h4>
        <p>Payment is due within 30 days.</p>
        <p class="payable-to"><?php echo esc_html($invoice->payable_to); ?></p>
    </div>
</body>
</html>
