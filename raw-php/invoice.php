<?php
require_once 'db.php';

use Shahinur\Bkash\BkashDb;

$paymentId = $_GET['payment_id'] ?? '';
$db = BkashDb::connect();

$stmt = $db->prepare("SELECT * FROM bkash_transactions WHERE payment_id = :payment_id LIMIT 1");
$stmt->execute([':payment_id' => $paymentId]);
$tx = $stmt->fetch();

if (!$tx) {
    die("<h3>Error: Transaction not found in local SQLite database.</h3>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Invoice <?= htmlspecialchars($tx['invoice_no']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .invoice-card {
            background: #fff;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            max-width: 500px;
            width: 100%;
            position: relative;
            border-top: 8px solid #e2125d;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .bkash-logo {
            width: 130px;
            margin-bottom: 1rem;
        }

        .title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .success-checkmark {
            width: 56px;
            height: 56px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1.5rem auto;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .amount {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 800;
            color: #e2125d;
            margin-bottom: 2rem;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2.5rem;
            font-size: 0.95rem;
        }

        .details-table tr {
            border-bottom: 1px solid #f3f4f6;
        }

        .details-table tr:last-child {
            border-bottom: none;
        }

        .details-table td {
            padding: 12px 0;
        }

        .label {
            color: #6b7280;
            font-weight: 500;
        }

        .value {
            text-align: right;
            font-weight: 600;
            color: #111827;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .value.code {
            font-family: monospace;
            font-size: 0.85rem;
        }

        .actions-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
        }

        .btn-print {
            background-color: #e2125d;
            color: #fff;
        }

        .btn-print:hover {
            background-color: #c90e50;
        }

        .btn-back {
            background-color: #e5e7eb;
            color: #4b5563;
        }

        .btn-back:hover {
            background-color: #d1d5db;
        }

        /* Print Specific Styles */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .invoice-card {
                box-shadow: none;
                padding: 0;
                border: none;
            }
            .actions-group {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="invoice-card">
    <div class="header">
        <img src="https://www.logo.wine/a/logo/BKash/BKash-Logo.wine.svg" alt="bKash Logo" class="bkash-logo">
        <h2 class="title">Payment Receipt</h2>
        <div class="subtitle">Transaction successfully verified by Merchant</div>
    </div>

    <div class="success-checkmark">✓</div>
    <div class="amount"><?= number_format($tx['amount'], 2) ?> BDT</div>

    <table class="details-table">
        <tr>
            <td class="label">Invoice No:</td>
            <td class="value"><?= htmlspecialchars($tx['invoice_no']) ?></td>
        </tr>
        <tr>
            <td class="label">Payer Wallet:</td>
            <td class="value"><?= htmlspecialchars($tx['payer_reference']) ?></td>
        </tr>
        <tr>
            <td class="label">Payment ID:</td>
            <td class="value code"><?= htmlspecialchars($tx['payment_id']) ?></td>
        </tr>
        <tr>
            <td class="label">Transaction ID (TrxID):</td>
            <td class="value code"><?= htmlspecialchars($tx['trx_id'] ?: 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">Gateway Mode:</td>
            <td class="value">Tokenized Checkout</td>
        </tr>
        <tr>
            <td class="label">Status:</td>
            <td class="value" style="color: #10b981; font-weight: 700;"><?= htmlspecialchars($tx['status']) ?></td>
        </tr>
        <tr>
            <td class="label">Payment Date:</td>
            <td class="value"><?= htmlspecialchars($tx['updated_at']) ?></td>
        </tr>
    </table>

    <div class="actions-group">
        <button class="btn btn-print" onclick="window.print()">Print Receipt</button>
        <a href="../index.php" class="btn btn-back">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
