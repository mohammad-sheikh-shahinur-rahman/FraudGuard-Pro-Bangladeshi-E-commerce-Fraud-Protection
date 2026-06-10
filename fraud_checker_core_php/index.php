<?php
require_once 'FraudChecker.php';

// Replace with your actual API key from FraudBD
$apiKey = "YOUR_FRAUDBD_API_KEY"; 
$checker = new FraudChecker($apiKey);

$report = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
    $phone = trim($_POST['phone']);
    
    // Basic BD Phone validation
    if (preg_match("/^(?:\+88|88)?(01[3-9]\d{8})$/", $phone)) {
        $response = $checker->checkNumber($phone);

        if (isset($response['status']) && $response['status']) {
            $report = $response['data'];
        } else {
            $error = isset($response['message']) ? $response['message'] : "Unknown error occurred.";
        }
    } else {
        $error = "Please enter a valid Bangladeshi phone number (e.g., 01712345678).";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fraud Checker BD - Core PHP</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #3498db; border: none; color: white; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #2980b9; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        .result-card { border: 1px solid #eee; padding: 20px; border-radius: 8px; background: #fafafa; }
        .summary-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .summary-item:last-child { border-bottom: none; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #dff0d8; color: #3c763d; }
        .badge-danger { background: #f2dede; color: #a94442; }
    </style>
</head>
<body>

<div class="container">
    <h2>Fraud Checker BD (Core PHP)</h2>
    
    <form method="POST">
        <div class="form-group">
            <label for="phone">Customer Phone Number:</label>
            <input type="text" name="phone" id="phone" placeholder="017XXXXXXXX" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
        </div>
        <button type="submit">Check Fraud Risk</button>
    </form>

    <div style="margin-top: 20px;">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($report): ?>
            <div class="result-card">
                <h3>Overall Summary</h3>
                <div class="summary-item">
                    <span>Total Orders:</span>
                    <strong><?php echo $report['totalSummary']['total']; ?></strong>
                </div>
                <div class="summary-item">
                    <span>Successful Deliveries:</span>
                    <strong class="badge badge-success"><?php echo $report['totalSummary']['success']; ?></strong>
                </div>
                <div class="summary-item">
                    <span>Cancelled Orders:</span>
                    <strong class="badge badge-danger"><?php echo $report['totalSummary']['cancel']; ?></strong>
                </div>
                <div class="summary-item">
                    <span>Success Rate:</span>
                    <strong><?php echo $report['totalSummary']['successRate']; ?>%</strong>
                </div>
                
                <h4 style="margin-top: 20px;">Courier Breakdown:</h4>
                <?php foreach ($report['Summaries'] as $courier => $details): ?>
                    <div class="summary-item">
                        <span><?php echo $courier; ?>:</span>
                        <span>
                            <?php if (isset($details['data_type']) && $details['data_type'] === 'rating'): ?>
                                <span class="badge <?php echo $details['risk_level'] === 'low' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $details['customer_rating'])); ?>
                                </span>
                            <?php else: ?>
                                Success: <?php echo $details['success']; ?> / Cancel: <?php echo $details['cancel']; ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
