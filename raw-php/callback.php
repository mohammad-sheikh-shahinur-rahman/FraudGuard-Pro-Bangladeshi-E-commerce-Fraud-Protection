<?php
session_start();

require_once 'BkashTokenized.php';
require_once 'config.php';
require_once 'db.php';

$paymentID = $_GET['paymentID'] ?? null;
$status = $_GET['status'] ?? null;

if (!$paymentID) {
    header("Location: ../index.php?status=error&message=Missing+Payment+ID");
    exit;
}

// Check bKash status redirect param
if ($status === 'cancel') {
    BkashDb::updateStatus($paymentID, 'Cancelled');
    header("Location: ../index.php?status=cancelled&paymentID=" . urlencode($paymentID));
    exit;
} elseif ($status === 'failure') {
    BkashDb::updateStatus($paymentID, 'Failed');
    header("Location: ../index.php?status=failed&paymentID=" . urlencode($paymentID));
    exit;
} elseif ($status !== 'success') {
    BkashDb::updateStatus($paymentID, 'Failed');
    header("Location: ../index.php?status=error&message=Invalid+status+returned&paymentID=" . urlencode($paymentID));
    exit;
}

// Status is success, execute the payment
$bkash = new BkashTokenized($_SESSION['bkash_config']);
$response = $bkash->executePayment($paymentID);

if (isset($response['statusCode']) && $response['statusCode'] === '0000') {
    $_SESSION['last_payment_success'] = true;
    $_SESSION['last_payment_response'] = $response;
    
    // Save to DB
    BkashDb::completePaymentRecord($paymentID, $response['trxID'] ?? '');
    
    header("Location: ../index.php?status=success&paymentID=" . urlencode($paymentID) . "&trxID=" . urlencode($response['trxID'] ?? ''));
    exit;
} else {
    $_SESSION['last_payment_success'] = false;
    $_SESSION['last_payment_response'] = $response;
    
    BkashDb::updateStatus($paymentID, 'Failed');
    
    $errorMessage = $response['statusMessage'] ?? 'Execution failed';
    header("Location: ../index.php?status=failed&paymentID=" . urlencode($paymentID) . "&error=" . urlencode($errorMessage));
    exit;
}

