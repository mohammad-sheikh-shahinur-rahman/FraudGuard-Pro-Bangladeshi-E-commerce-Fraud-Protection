<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Pay with bKash</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .checkout-card {
            background: #fff;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .bkash-logo {
            width: 120px;
            margin-bottom: 1.5rem;
        }
        h2 {
            margin: 0 0 0.5rem;
            color: #1f2937;
        }
        p {
            color: #6b7280;
            margin: 0 0 2rem;
            font-size: 0.95rem;
        }
        .amount-display {
            font-size: 2rem;
            font-weight: 800;
            color: #e2125d;
            margin-bottom: 1.5rem;
        }
        .btn-pay {
            background-color: #e2125d;
            color: #fff;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-pay:hover {
            background-color: #c90e50;
        }
    </style>
</head>
<body>

<div class="checkout-card">
    <img src="https://www.logo.wine/a/logo/BKash/BKash-Logo.wine.svg" alt="bKash Logo" class="bkash-logo">
    <h2>Order Payment</h2>
    <p>Please review and proceed to payment using bKash Tokenized Checkout.</p>
    
    <div class="amount-display">150.00 BDT</div>

    <form action="{{ route('bkash.pay') }}" method="POST">
        @csrf
        <input type="hidden" name="amount" value="150.00">
        <input type="hidden" name="payer_reference" value="01770618575">
        <button type="submit" class="btn-pay">Pay with bKash</button>
    </form>
</div>

</body>
</html>
