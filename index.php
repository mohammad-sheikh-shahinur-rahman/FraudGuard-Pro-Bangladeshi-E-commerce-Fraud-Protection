<?php
session_start();

require_once 'raw-php/BkashTokenized.php';
require_once 'raw-php/config.php'; // Ensure session defaults are loaded
require_once 'raw-php/db.php';

use Shahinur\Bkash\BkashTokenized;
use Shahinur\Bkash\BkashDb;

// Simple routing/tab helper
$activeTab = $_GET['tab'] ?? 'overview';

// AJAX Actions Handler (Returns JSON immediately)
if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');
    $ajaxAction = $_GET['ajax_action'];
    $bkash = new BkashTokenized($_SESSION['bkash_config']);
    
    if ($ajaxAction === 'query') {
        $paymentID = $_GET['payment_id'] ?? '';
        $result = $bkash->queryPayment($paymentID);
        
        if (isset($result['statusCode']) && $result['statusCode'] === '0000') {
            $status = $result['transactionStatus'] ?? '';
            if ($status === 'Completed') {
                BkashDb::completePaymentRecord($paymentID, $result['trxID'] ?? '');
            } elseif ($status === 'Initiated' || $status === 'Refunded' || $status === 'Failed') {
                BkashDb::updateStatus($paymentID, $status);
            }
        }
        echo json_encode($result);
        exit;
    }
    
    if ($ajaxAction === 'refund') {
        $paymentID = $_POST['payment_id'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $trxID = $_POST['trx_id'] ?? '';
        $reason = $_POST['reason'] ?? 'AJAX Refund';
        
        $result = $bkash->refundPayment($paymentID, $amount, $trxID, $reason);
        
        if (isset($result['statusCode']) && $result['statusCode'] === '0000') {
            BkashDb::refundPaymentRecord($paymentID, $result['refundTrxID'] ?? '');
        }
        echo json_encode($result);
        exit;
    }
}

// Handle Action Requests
$message = null;
$messageType = null;
$bkash = new BkashTokenized($_SESSION['bkash_config']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_config') {
        $_SESSION['bkash_config'] = [
            'sandbox' => isset($_POST['sandbox']),
            'app_key' => trim($_POST['app_key']),
            'app_secret' => trim($_POST['app_secret']),
            'username' => trim($_POST['username']),
            'password' => trim($_POST['password'])
        ];
        // Clear cached token when config changes
        unset($_SESSION['bkash_token']);
        $message = "Configuration updated successfully and session token cleared.";
        $messageType = "success";
        // Re-instantiate with new config
        $bkash = new BkashTokenized($_SESSION['bkash_config']);
    } 
    
    elseif ($action === 'clear_logs') {
        $_SESSION['bkash_logs'] = [];
        $message = "API Console logs cleared.";
        $messageType = "success";
    }
    
    elseif ($action === 'generate_token') {
        $result = $bkash->grantToken();
        if (isset($result['statusCode']) && $result['statusCode'] === '0000') {
            $message = "Token generated successfully! ID Token cached in session.";
            $messageType = "success";
        } else {
            $message = "Token generation failed: " . ($result['statusMessage'] ?? 'Unknown error');
            $messageType = "danger";
        }
    } 
    
    elseif ($action === 'create_payment') {
        $amount = trim($_POST['amount']);
        $invoice = trim($_POST['invoice']);
        $payer = trim($_POST['payer']);
        
        // Dynamic callback URL determination
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        $callbackUrl = $protocol . $domainName . "/raw-php/callback.php";

        $result = $bkash->createPayment($amount, $invoice, $callbackUrl, $payer);

        if (isset($result['statusCode']) && $result['statusCode'] === '0000' && !empty($result['bkashURL'])) {
            // Store paymentID in session to track checkout state
            $_SESSION['active_payment_id'] = $result['paymentID'];
            
            // Log payment session in SQLite DB
            BkashDb::createPaymentRecord($result['paymentID'], $amount, $invoice, $payer);
            
            // Redirect to bKash checkout URL
            header("Location: " . $result['bkashURL']);
            exit;
        } else {
            $message = "Failed to create payment: " . ($result['statusMessage'] ?? 'Unknown error');
            $messageType = "danger";
        }
    }
    
    elseif ($action === 'query_payment') {
        $paymentID = trim($_POST['payment_id']);
        $result = $bkash->queryPayment($paymentID);
        
        if (isset($result['statusCode']) && $result['statusCode'] === '0000') {
            $message = "Query Success! Transaction Status: " . ($result['transactionStatus'] ?? 'Unknown') . ". Amount: " . ($result['amount'] ?? 'N/A') . " BDT.";
            $messageType = "success";
            $_SESSION['query_result'] = $result;
            
            // Sync status to local DB
            $status = $result['transactionStatus'] ?? '';
            if ($status === 'Completed') {
                BkashDb::completePaymentRecord($paymentID, $result['trxID'] ?? '');
            } elseif ($status === 'Initiated' || $status === 'Refunded' || $status === 'Failed') {
                BkashDb::updateStatus($paymentID, $status);
            }
        } else {
            $message = "Query failed: " . ($result['statusMessage'] ?? 'Unknown error');
            $messageType = "danger";
        }
    }
    
    elseif ($action === 'refund_payment') {
        $paymentID = trim($_POST['payment_id']);
        $amount = trim($_POST['amount']);
        $trxID = trim($_POST['trx_id']);
        $reason = trim($_POST['reason']);
        
        $result = $bkash->refundPayment($paymentID, $amount, $trxID, $reason);
        
        if (isset($result['statusCode']) && $result['statusCode'] === '0000') {
            $message = "Refund successful! Refund TrxID: " . ($result['refundTrxID'] ?? 'N/A') . ". Status: " . ($result['transactionStatus'] ?? 'Unknown');
            $messageType = "success";
            
            // Update SQLite DB
            BkashDb::refundPaymentRecord($paymentID, $result['refundTrxID'] ?? '');
        } else {
            $message = "Refund failed: " . ($result['statusMessage'] ?? 'Unknown error');
            $messageType = "danger";
        }
    }
}

// Check for redirect status
$redirectStatus = $_GET['status'] ?? null;
if ($redirectStatus) {
    if ($redirectStatus === 'success') {
        $message = "Payment executed successfully! Transaction ID: " . htmlspecialchars($_GET['trxID'] ?? '') . " (Payment ID: " . htmlspecialchars($_GET['paymentID'] ?? '') . ")";
        $messageType = "success";
    } elseif ($redirectStatus === 'cancelled') {
        $message = "Payment cancelled by the user. (Payment ID: " . htmlspecialchars($_GET['paymentID'] ?? '') . ")";
        $messageType = "warning";
    } elseif ($redirectStatus === 'failed') {
        $message = "Payment checkout failed: " . htmlspecialchars($_GET['error'] ?? 'Unknown error') . " (Payment ID: " . htmlspecialchars($_GET['paymentID'] ?? '') . ")";
        $messageType = "danger";
    } elseif ($redirectStatus === 'error') {
        $message = "Integration Error: " . htmlspecialchars($_GET['message'] ?? 'Unknown error');
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Integrate bKash Tokenized Checkout using Laravel and Raw PHP. View interactive playground, settings and step-by-step guides.">
    <title>bKash Tokenized Checkout Playground</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="app-container">
    <!-- Sidebar Navigation -->
    <aside class="app-sidebar">
        <div class="logo-container">
            <div class="bkash-dot"></div>
            <span class="logo-text">bKash DevHub</span>
        </div>
        
        <nav class="nav-menu">
            <li class="nav-item">
                <a href="?tab=overview" class="nav-link <?= $activeTab === 'overview' ? 'active' : '' ?>">
                    <span>Overview (Analytics)</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="?tab=playground" class="nav-link <?= $activeTab === 'playground' ? 'active' : '' ?>">
                    <span>bKash Playground</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="?tab=laravel" class="nav-link <?= $activeTab === 'laravel' ? 'active' : '' ?>">
                    <span>Laravel Guide</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="?tab=reference" class="nav-link <?= $activeTab === 'reference' ? 'active' : '' ?>">
                    <span>API Reference</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="?tab=ipn" class="nav-link <?= $activeTab === 'ipn' ? 'active' : '' ?>">
                    <span>Webhook Logs (IPN)</span>
                </a>
            </li>
        </nav>
        
        <div class="sidebar-footer">
            <p>bKash Tokenized API</p>
            <p style="font-size: 0.75rem; margin-top: 4px;">v1.2.0-beta Checkout</p>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="app-main">
        <header class="app-header">
            <div>
                <h1>bKash Tokenized Checkout</h1>
                <p class="app-title-desc">Fully interactive playground & developer guide for Laravel & Raw PHP integration</p>
            </div>
            
            <div class="env-badge <?= $_SESSION['bkash_config']['sandbox'] ? 'sandbox' : '' ?>">
                <span class="dot"></span>
                <span><?= $_SESSION['bkash_config']['sandbox'] ? 'Sandbox Mode' : 'Production Mode' ?></span>
            </div>
        </header>

        <!-- Status Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>" id="status-alert">
                <span><?= $message ?></span>
            </div>
        <?php endif; ?>

        <!-- OVERVIEW TAB -->
        <div class="tab-content <?= $activeTab === 'overview' ? 'active' : '' ?>">
            <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <?php
                $txs = BkashDb::getAllTransactions();
                $totalVolume = array_sum(array_column($txs, 'amount'));
                $successCount = count(array_filter($txs, fn($t) => $t['status'] === 'Completed'));
                $refundCount = count(array_filter($txs, fn($t) => $t['status'] === 'Refunded'));
                ?>
                <div class="card" style="padding: 1.5rem; border-top-color: var(--bkash-pink);">
                    <div style="font-size: 0.9rem; color: var(--text-secondary);">Total Volume</div>
                    <div style="font-size: 1.8rem; font-weight: 800; margin-top: 5px;"><?= number_format($totalVolume, 2) ?> BDT</div>
                </div>
                <div class="card" style="padding: 1.5rem; border-top-color: var(--success);">
                    <div style="font-size: 0.9rem; color: var(--text-secondary);">Success Payments</div>
                    <div style="font-size: 1.8rem; font-weight: 800; margin-top: 5px;"><?= $successCount ?></div>
                </div>
                <div class="card" style="padding: 1.5rem; border-top-color: var(--accent-purple);">
                    <div style="font-size: 0.9rem; color: var(--text-secondary);">Refunds Issued</div>
                    <div style="font-size: 1.8rem; font-weight: 800; margin-top: 5px;"><?= $refundCount ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">Transaction Trends</div>
                <div style="height: 300px;">
                    <canvas id="txChart"></canvas>
                </div>
            </div>
        </div>

        <!-- PLAYGROUND TAB -->
        <div class="tab-content <?= $activeTab === 'playground' ? 'active' : '' ?>">
            
            <!-- Checkout Step Progress Indicator -->
            <div class="card">
                <div class="card-title">Checkout Integration Flow</div>
                <div class="steps-container">
                    <div class="step-node <?= isset($_SESSION['bkash_token']) ? 'completed' : 'active' ?>">
                        <div class="step-badge">1</div>
                        <span class="step-label">Grant Token</span>
                    </div>
                    <div class="step-node <?= isset($_SESSION['active_payment_id']) ? 'completed' : '' ?>">
                        <div class="step-badge">2</div>
                        <span class="step-label">Create Payment</span>
                    </div>
                    <div class="step-node <?= ($redirectStatus === 'success') ? 'completed' : (($redirectStatus === 'cancelled' || $redirectStatus === 'failed') ? 'active' : '') ?>">
                        <div class="step-badge">3</div>
                        <span class="step-label">Customer OTP/PIN</span>
                    </div>
                    <div class="step-node <?= ($redirectStatus === 'success') ? 'completed' : '' ?>">
                        <div class="step-badge">4</div>
                        <span class="step-label">Execute Payment</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                
                <!-- Main Action Column -->
                <div class="main-column">
                    
                    <!-- Create Payment Playground -->
                    <div class="card">
                        <div class="card-title">Create Payment (Initiate Checkout)</div>
                        <form action="?tab=playground" method="POST">
                            <input type="hidden" name="action" value="create_payment">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="amount">Amount (BDT)</label>
                                    <input type="number" id="amount" name="amount" value="10.00" min="1" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label for="invoice">Merchant Invoice No</label>
                                    <input type="text" id="invoice" name="invoice" value="INV-<?= rand(100000, 999999) ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="payer">Payer Reference (Wallet/ID)</label>
                                    <input type="text" id="payer" name="payer" value="01770618575" placeholder="e.g. 017XXXXXXXX" required>
                                    <div style="display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap;">
                                        <button type="button" onclick="selectTestWallet('01770618575', 'Success Scenario')" style="padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color); background: rgba(16,185,129,0.1); color: #34d399; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">✔️ Success</button>
                                        <button type="button" onclick="selectTestWallet('01823074817', 'Insufficient Balance')" style="padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color); background: rgba(245,158,11,0.1); color: #fbbf24; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">❌ Insufficient Bal</button>
                                        <button type="button" onclick="selectTestWallet('01823074818', 'Debit Block')" style="padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color); background: rgba(239,68,68,0.1); color: #f87171; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">❌ Debit Block</button>
                                    </div>
                                    <div id="wallet-tip" style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 8px; display: none;"></div>
                                </div>
                                <div class="form-group">
                                    <label>Callback URL (Auto-detected)</label>
                                    <input type="text" value="http://<?= $_SERVER['HTTP_HOST'] ?>/raw-php/callback.php" disabled>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                                Launch bKash Checkout
                            </button>
                        </form>
                    </div>

                    <!-- Query Transaction -->
                    <div class="card">
                        <div class="card-title">Query Payment Status</div>
                        <form action="?tab=playground" method="POST">
                            <input type="hidden" name="action" value="query_payment">
                            <div class="form-group">
                                <label for="query_payment_id">Payment ID</label>
                                <input type="text" id="query_payment_id" name="payment_id" value="<?= $_SESSION['active_payment_id'] ?? '' ?>" placeholder="Enter bKash Payment ID" required>
                            </div>
                            <button type="submit" class="btn btn-secondary">Query Status</button>
                        </form>

                        <?php if (isset($_SESSION['query_result'])): ?>
                            <div style="margin-top: 1.5rem;">
                                <label>Query Result Payload</label>
                                <pre style="max-height: 200px; font-size: 0.8rem;"><?= htmlspecialchars(json_encode($_SESSION['query_result'], JSON_PRETTY_PRINT)) ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Refund Transaction -->
                    <div class="card">
                        <div class="card-title">Refund Payment</div>
                        <form action="?tab=playground" method="POST">
                            <input type="hidden" name="action" value="refund_payment">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="refund_payment_id">Payment ID</label>
                                    <input type="text" id="refund_payment_id" name="payment_id" value="<?= $_SESSION['last_payment_response']['paymentID'] ?? '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="refund_trx_id">Transaction ID (TrxID)</label>
                                    <input type="text" id="refund_trx_id" name="trx_id" value="<?= $_SESSION['last_payment_response']['trxID'] ?? '' ?>" placeholder="Transaction TrxID" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="refund_amount">Refund Amount (BDT)</label>
                                    <input type="number" id="refund_amount" name="amount" value="<?= $_SESSION['last_payment_response']['amount'] ?? '' ?>" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label for="refund_reason">Reason</label>
                                    <input type="text" id="refund_reason" name="reason" value="Customer Return" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline" style="border-color: var(--warning); color: var(--warning);">Process Refund</button>
                        </form>
                    </div>

                    <!-- Local Payment History (SQLite) -->
                    <div class="card">
                        <div class="card-title">
                            <span>Local Payment History (SQLite Database)</span>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="txSearch" placeholder="Search Invoice/ID..." style="width: 200px; padding: 6px 12px; font-size: 0.8rem;">
                                <button onclick="exportToCSV()" class="btn btn-secondary" style="width: auto; padding: 6px 12px; font-size: 0.8rem;">Export CSV</button>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table id="txTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary);">
                                        <th style="padding: 12px 8px;">Invoice</th>
                                        <th style="padding: 12px 8px;">Payer Ref</th>
                                        <th style="padding: 12px 8px;">Amount</th>
                                        <th style="padding: 12px 8px;">Payment ID</th>
                                        <th style="padding: 12px 8px;">Transaction ID</th>
                                        <th style="padding: 12px 8px;">Status</th>
                                        <th style="padding: 12px 8px; text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $txs = BkashDb::getAllTransactions();
                                    if (empty($txs)): 
                                    ?>
                                        <tr>
                                            <td colspan="7" style="padding: 20px 8px; text-align: center; color: var(--text-secondary); font-style: italic;">
                                                No local transactions found. Launch a checkout flow to log your first payment.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($txs as $tx): ?>
                                            <tr id="tx-row-<?= htmlspecialchars($tx['payment_id']) ?>" style="border-bottom: 1px solid var(--border-color); transition: background 0.5s ease;">
                                                <td style="padding: 12px 8px; font-weight: 600;"><?= htmlspecialchars($tx['invoice_no']) ?></td>
                                                <td style="padding: 12px 8px; color: var(--text-secondary);"><?= htmlspecialchars($tx['payer_reference']) ?></td>
                                                <td style="padding: 12px 8px; font-weight: 600; color: var(--bkash-pink);"><?= number_format($tx['amount'], 2) ?> BDT</td>
                                                <td style="padding: 12px 8px; font-family: 'Fira Code', monospace; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars(substr($tx['payment_id'], 0, 12)) ?>...</td>
                                                <td class="trx-id-cell" style="padding: 12px 8px; font-family: 'Fira Code', monospace; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($tx['trx_id'] ?: 'N/A') ?></td>
                                                <td class="status-cell" style="padding: 12px 8px;">
                                                    <?php
                                                    $statusClass = 'warning';
                                                    if ($tx['status'] === 'Completed') $statusClass = 'success';
                                                    elseif ($tx['status'] === 'Refunded') $statusClass = 'purple';
                                                    elseif ($tx['status'] === 'Failed' || $tx['status'] === 'Cancelled') $statusClass = 'danger';
                                                    ?>
                                                    <span class="status-badge <?= $statusClass ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                                        <?= htmlspecialchars($tx['status']) ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 12px 8px; text-align: right;">
                                                    <div class="actions-cell-container" style="display: inline-flex; gap: 8px; align-items: center;">
                                                        <button class="btn-action query-btn" onclick="directQuery(this, '<?= htmlspecialchars($tx['payment_id']) ?>')" style="padding: 4px 8px; font-size: 0.75rem; border-radius: 4px; background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); color: var(--accent-blue); cursor: pointer; transition: all 0.2s;">⚡ Query</button>
                                                        <?php if ($tx['status'] === 'Completed' || $tx['status'] === 'Refunded'): ?>
                                                            <a href="raw-php/invoice.php?payment_id=<?= urlencode($tx['payment_id']) ?>" target="_blank" class="invoice-btn-link" style="padding: 4px 8px; font-size: 0.75rem; border-radius: 4px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #34d399; text-decoration: none; display: inline-block; transition: all 0.2s;">📄 Receipt</a>
                                                        <?php endif; ?>
                                                        <?php if ($tx['status'] === 'Completed'): ?>
                                                            <button class="btn-action refund-btn" onclick="directRefund(this, '<?= htmlspecialchars($tx['payment_id']) ?>', '<?= htmlspecialchars($tx['trx_id']) ?>', '<?= htmlspecialchars($tx['amount']) ?>')" style="padding: 4px 8px; font-size: 0.75rem; border-radius: 4px; background: rgba(226,18,93,0.1); border: 1px solid rgba(226,18,93,0.3); color: var(--bkash-pink); cursor: pointer; transition: all 0.2s;">⚡ Refund</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Right Configuration & Console Column -->
                <div class="sidebar-column">
                    
                    <!-- Merchant Credentials Configuration -->
                    <div class="card">
                        <div class="card-title">Gateway Credentials</div>
                        <form action="?tab=playground" method="POST">
                            <input type="hidden" name="action" value="update_config">
                            
                            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
                                <label for="sandbox_toggle" style="margin-bottom: 0;">Sandbox Mode</label>
                                <input type="checkbox" id="sandbox_toggle" name="sandbox" <?= $_SESSION['bkash_config']['sandbox'] ? 'checked' : '' ?> style="width: auto; transform: scale(1.2);">
                            </div>

                            <div class="form-group">
                                <label for="cfg_app_key">App Key</label>
                                <input type="text" id="cfg_app_key" name="app_key" value="<?= htmlspecialchars($_SESSION['bkash_config']['app_key']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="cfg_app_secret">App Secret</label>
                                <input type="text" id="cfg_app_secret" name="app_secret" value="<?= htmlspecialchars($_SESSION['bkash_config']['app_secret']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="cfg_username">API Username</label>
                                <input type="text" id="cfg_username" name="username" value="<?= htmlspecialchars($_SESSION['bkash_config']['username']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="cfg_password">API Password</label>
                                <input type="text" id="cfg_password" name="password" value="<?= htmlspecialchars($_SESSION['bkash_config']['password']) ?>" required>
                            </div>

                            <div class="form-row" style="margin-top: 1rem;">
                                <button type="submit" class="btn btn-secondary">Save Config</button>
                            </div>
                        </form>
                        
                        <form action="?tab=playground" method="POST" style="margin-top: 0.8rem;">
                            <input type="hidden" name="action" value="generate_token">
                            <button type="submit" class="btn btn-outline">Test & Cache Token</button>
                        </form>
                    </div>

                    <!-- API Console Logs -->
                    <div class="card" style="padding: 1.5rem;">
                        <div class="card-title" style="margin-bottom: 1rem;">
                            <span>API Console Logs</span>
                            <form action="?tab=playground" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="clear_logs">
                                <button type="submit" style="background: none; border: none; color: var(--text-secondary); font-size: 0.8rem; cursor: pointer; text-decoration: underline;">Clear</button>
                            </form>
                        </div>
                        <div class="console-panel">
                            <div class="console-header">
                                <div class="console-dots">
                                    <span class="console-dot red"></span>
                                    <span class="console-dot yellow"></span>
                                    <span class="console-dot green"></span>
                                </div>
                                <span class="console-title">bKash-curl-logger</span>
                            </div>
                            <div class="console-body">
                                <?php if (empty($_SESSION['bkash_logs'])): ?>
                                    <span style="color: var(--text-secondary); font-style: italic;">No API calls logged yet. Initiate checkout or generate token to see curl traces.</span>
                                <?php else: ?>
                                    <?php foreach (array_reverse($_SESSION['bkash_logs']) as $log): 
                                        $httpSuccess = ($log['response_code'] >= 200 && $log['response_code'] < 300);
                                        $respBody = $log['response_body'];
                                        $bKashError = isset($respBody['errorCode']);
                                    ?>
                                        <div class="log-item">
                                            <div class="log-meta">
                                                <span class="log-method <?= $log['method'] ?>"><?= $log['method'] ?></span>
                                                <span class="log-code <?= ($httpSuccess && !$bKashError) ? 'success' : 'failed' ?>"><?= $log['response_code'] ?> <?= ($bKashError) ? '(API Err)' : '' ?></span>
                                            </div>
                                            <div class="log-url"><?= htmlspecialchars(parse_url($log['url'], PHP_URL_PATH)) ?></div>
                                            
                                            <?php if (!empty($log['request_body'])): ?>
                                                <div class="log-data-header">Request Payload:</div>
                                                <pre class="request"><?= htmlspecialchars(json_encode($log['request_body'], JSON_PRETTY_PRINT)) ?></pre>
                                            <?php endif; ?>

                                            <div class="log-data-header">Response Payload: (<?= $log['execution_time_ms'] ?>ms)</div>
                                            <pre class="<?= ($bKashError) ? 'response-error' : '' ?>"><?= htmlspecialchars(json_encode($respBody, JSON_PRETTY_PRINT)) ?></pre>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- LARAVEL GUIDE TAB -->
        <div class="tab-content <?= $activeTab === 'laravel' ? 'active' : '' ?>">
            <div class="card docs-section">
                <h2>Laravel Tokenized Checkout Integration Guide</h2>
                <p>Follow this clean, production-ready implementation workflow to integrate bKash Tokenized Checkout in your Laravel project. This setup handles automatic token management (caching and refreshing) and encapsulates the API calls cleanly.</p>
                
                <h3>Step 1: Configuration File</h3>
                <p>Create the config file `config/bkash.php` to map environment values securely.</p>
                <div class="code-block">
                    <div class="code-block-header">
                        <span>config/bkash.php</span>
                    </div>
                    <pre><code class="language-php"><?= htmlspecialchars('<?php

return [
    \'sandbox\' => env(\'BKASH_SANDBOX\', true),
    \'app_key\' => env(\'BKASH_APP_KEY\'),
    \'app_secret\' => env(\'BKASH_APP_SECRET\'),
    \'username\' => env(\'BKASH_USERNAME\'),
    \'password\' => env(\'BKASH_PASSWORD\'),
    
    // Auto-detect routes or custom callback
    \'callback_url\' => env(\'BKASH_CALLBACK_URL\', \'/bkash/callback\'),
];') ?></code></pre>
                </div>

                <h3>Step 2: Service Class</h3>
                <p>Create `app/Services/BkashService.php` to handle token management, sessions, and core API curls.</p>
                <div class="code-block">
                    <div class="code-block-header">
                        <span>app/Services/BkashService.php</span>
                    </div>
                    <pre><code class="language-php"><?= htmlspecialchars('<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BkashService
{
    protected $baseUrl;
    protected $appKey;
    protected $appSecret;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->appKey = config(\'bkash.app_key\');
        $this->appSecret = config(\'bkash.app_secret\');
        $this->username = config(\'bkash.username\');
        $this->password = config(\'bkash.password\');
        
        $this->baseUrl = config(\'bkash.sandbox\')
            ? \'https://tokenized.sandbox.bka.sh/v1.2.0-beta\'
            : \'https://tokenized.pay.bka.sh/v1.2.0-beta\';
    }

    private function request($endpoint, $body, $headers = [])
    {
        $defaultHeaders = [
            \'Content-Type\' => \'application/json\',
            \'Accept\' => \'application/json\',
        ];

        try {
            $response = Http::withHeaders(array_merge($defaultHeaders, $headers))
                ->post($this->baseUrl . $endpoint, $body);

            if ($response->failed()) {
                Log::error("bKash API Error: " . $response->body());
                return [\'statusCode\' => \'9999\', \'statusMessage\' => \'HTTP Request Failed\'];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("bKash Connection Exception: " . $e->getMessage());
            return [\'statusCode\' => \'9999\', \'statusMessage\' => $e->getMessage()];
        }
    }

    public function grantToken()
    {
        $headers = [
            \'username\' => $this->username,
            \'password\' => $this->password
        ];
        
        $body = [
            \'app_key\' => $this->appKey,
            \'app_secret\' => $this->appSecret
        ];

        $response = $this->request(\'/tokenized/checkout/token/grant\', $body, $headers);

        if (isset($response[\'id_token\'])) {
            Cache::put(\'bkash_id_token\', $response[\'id_token\'], now()->addSeconds($response[\'expires_in\'] - 300));
            Cache::put(\'bkash_refresh_token\', $response[\'refresh_token\'], now()->addDays(28));
            return $response[\'id_token\'];
        }

        return false;
    }

    public function refreshToken($refreshToken)
    {
        $headers = [
            \'username\' => $this->username,
            \'password\' => $this->password
        ];
        
        $body = [
            \'app_key\' => $this->appKey,
            \'app_secret\' => $this->appSecret,
            \'refresh_token\' => $refreshToken
        ];

        $response = $this->request(\'/tokenized/checkout/token/refresh\', $body, $headers);

        if (isset($response[\'id_token\'])) {
            Cache::put(\'bkash_id_token\', $response[\'id_token\'], now()->addSeconds($response[\'expires_in\'] - 300));
            Cache::put(\'bkash_refresh_token\', $response[\'refresh_token\'], now()->addDays(28));
            return $response[\'id_token\'];
        }

        return false;
    }

    public function getAccessToken()
    {
        if (Cache::has(\'bkash_id_token\')) {
            return Cache::get(\'bkash_id_token\');
        }

        if (Cache::has(\'bkash_refresh_token\')) {
            $token = $this->refreshToken(Cache::get(\'bkash_refresh_token\'));
            if ($token) return $token;
        }

        return $this->grantToken();
    }

    public function createPayment($amount, $invoiceNumber, $payerReference = \'01770618575\')
    {
        $token = $this->getAccessToken();
        if (!$token) return [\'statusCode\' => \'9997\', \'statusMessage\' => \'Authentication Failed\'];

        $headers = [
            \'Authorization\' => $token,
            \'X-App-Key\' => $this->appKey
        ];

        $body = [
            \'mode\' => \'0011\',
            \'payerReference\' => $payerReference,
            \'callbackURL\' => url(config(\'bkash.callback_url\')),
            \'amount\' => number_format((float)$amount, 2, \'.\', \'\'),
            \'currency\' => \'BDT\',
            \'intent\' => \'sale\',
            \'merchantInvoiceNumber\' => $invoiceNumber
        ];

        return $this->request(\'/tokenized/checkout/create\', $body, $headers);
    }

    public function executePayment($paymentID)
    {
        $token = $this->getAccessToken();
        if (!$token) return [\'statusCode\' => \'9997\', \'statusMessage\' => \'Authentication Failed\'];

        $headers = [
            \'Authorization\' => $token,
            \'X-App-Key\' => $this->appKey
        ];

        $body = [
            \'paymentID\' => $paymentID
        ];

        return $this->request(\'/tokenized/checkout/execute\', $body, $headers);
    }

    public function queryPayment($paymentID)
    {
        $token = $this->getAccessToken();
        if (!$token) return [\'statusCode\' => \'9997\', \'statusMessage\' => \'Authentication Failed\'];

        $headers = [
            \'Authorization\' => $token,
            \'X-App-Key\' => $this->appKey
        ];

        $body = [
            \'paymentID\' => $paymentID
        ];

        return $this->request(\'/tokenized/checkout/payment/status\', $body, $headers);
    }

    public function refundPayment($paymentID, $amount, $trxID, $reason = \'Customer Request\')
    {
        $token = $this->getAccessToken();
        if (!$token) return [\'statusCode\' => \'9997\', \'statusMessage\' => \'Authentication Failed\'];

        $headers = [
            \'Authorization\' => $token,
            \'X-App-Key\' => $this->appKey
        ];

        $body = [
            \'paymentID\' => $paymentID,
            \'amount\' => number_format((float)$amount, 2, \'.\', \'\'),
            \'trxID\' => $trxID,
            \'sku\' => \'default\',
            \'reason\' => $reason
        ];

        return $this->request(\'/tokenized/checkout/payment/refund\', $body, $headers);
    }
}') ?></code></pre>
                </div>

                <h3>Step 3: Controller</h3>
                <p>Create `app/Http/Controllers/BkashController.php` to handle checkout initialization and checkout callback processing.</p>
                <div class="code-block">
                    <div class="code-block-header">
                        <span>app/Http/Controllers/BkashController.php</span>
                    </div>
                    <pre><code class="language-php"><?= htmlspecialchars('<?php

namespace App\Http\Controllers;

use App\Services\BkashService;
use Illuminate\Http\Request;

class BkashController extends Controller
{
    protected $bkashService;

    public function __construct(BkashService $bkashService)
    {
        $this->bkashService = $bkashService;
    }

    public function pay(Request $request)
    {
        $amount = $request->amount ?? 100;
        $invoice = \'INV-\' . uniqid();
        
        $response = $this->bkashService->createPayment($amount, $invoice);

        if (isset($response[\'statusCode\']) && $response[\'statusCode\'] === \'0000\' && !empty($response[\'bkashURL\'])) {
            return redirect()->away($response[\'bkashURL\']);
        }

        return back()->with(\'error\', $response[\'statusMessage\'] ?? \'Failed to create payment\');
    }

    public function callback(Request $request)
    {
        $paymentID = $request->get(\'paymentID\');
        $status = $request->get(\'status\');

        if ($status === \'success\') {
            $response = $this->bkashService->executePayment($paymentID);

            if (isset($response[\'statusCode\']) && $response[\'statusCode\'] === \'0000\') {
                // Payment was successful, update database status
                $trxID = $response[\'trxID\'];
                return redirect()->route(\'payment.success\')->with([
                    \'trxID\' => $trxID,
                    \'message\' => \'Payment Completed Successfully\'
                ]);
            }

            return redirect()->route(\'payment.failed\')->with(\'error\', $response[\'statusMessage\'] ?? \'Payment execution failed\');
        }

        if ($status === \'cancel\') {
            return redirect()->route(\'payment.failed\')->with(\'error\', \'Payment cancelled by user\');
        }

        return redirect()->route(\'payment.failed\')->with(\'error\', \'Payment transaction failed\');
    }
}') ?></code></pre>
                </div>

                <h3>Step 4: Routes</h3>
                <p>Register these web routes in your `routes/web.php` file.</p>
                <div class="code-block">
                    <div class="code-block-header">
                        <span>routes/web.php</span>
                    </div>
                    <pre><code class="language-php"><?= htmlspecialchars('<?php

use App\Http\Controllers\BkashController;
use Illuminate\Support\Facades\Route;

Route::post(\'/bkash/pay\', [BkashController::class, \'pay\'])->name(\'bkash.pay\');
Route::get(\'/bkash/callback\', [BkashController::class, \'callback\'])->name(\'bkash.callback\');

// Success / failure views
Route::view(\'/payment/success\', \'payment-success\')->name(\'payment.success\');
Route::view(\'/payment/failed\', \'payment-failed\')->name(\'payment.failed\');') ?></code></pre>
                </div>
            </div>
        </div>

        <!-- API REFERENCE TAB -->
        <div class="tab-content <?= $activeTab === 'reference' ? 'active' : '' ?>">
            <div class="card docs-section">
                <h2>bKash Tokenized API Reference</h2>
                <p>A quick summary of the headers, bodies, and URLs used in the checkout process.</p>

                <h3>1. Grant Token</h3>
                <p>Retrieve a temporary ID Token (1 hour validity) and Refresh Token (28 days validity).</p>
                <ul>
                    <li><strong>URL:</strong> <code>{baseUrl}/tokenized/checkout/token/grant</code></li>
                    <li><strong>Method:</strong> <code>POST</code></li>
                    <li><strong>Headers:</strong>
                        <pre>Content-Type: application/json
Accept: application/json
username: {merchantUsername}
password: {merchantPassword}</pre>
                    </li>
                    <li><strong>Request Body:</strong>
                        <pre>{
  "app_key": "YOUR_APP_KEY",
  "app_secret": "YOUR_APP_SECRET"
}</pre>
                    </li>
                </ul>

                <h3>2. Refresh Token</h3>
                <p>Obtain a new ID Token when the current one expires, using the Refresh Token.</p>
                <ul>
                    <li><strong>URL:</strong> <code>{baseUrl}/tokenized/checkout/token/refresh</code></li>
                    <li><strong>Method:</strong> <code>POST</code></li>
                    <li><strong>Headers:</strong> Same as Grant Token.</li>
                    <li><strong>Request Body:</strong>
                        <pre>{
  "app_key": "YOUR_APP_KEY",
  "app_secret": "YOUR_APP_SECRET",
  "refresh_token": "YOUR_REFRESH_TOKEN"
}</pre>
                    </li>
                </ul>

                <h3>3. Create Payment</h3>
                <p>Initiate checkout and receive a redirect URL (`bkashURL`) for the customer.</p>
                <ul>
                    <li><strong>URL:</strong> <code>{baseUrl}/tokenized/checkout/create</code></li>
                    <li><strong>Method:</strong> <code>POST</code></li>
                    <li><strong>Headers:</strong>
                        <pre>Content-Type: application/json
Accept: application/json
Authorization: {idToken}
X-App-Key: {appKey}</pre>
                    </li>
                    <li><strong>Request Body:</strong>
                        <pre>{
  "mode": "0011",
  "payerReference": "017XXXXXXXX",
  "callbackURL": "https://example.com/callback",
  "amount": "100.00",
  "currency": "BDT",
  "intent": "sale",
  "merchantInvoiceNumber": "INV-12345"
}</pre>
                    </li>
                </ul>

                <h3>4. Execute Payment</h3>
                <p>Finalize the transaction after successful customer OTP/PIN input callback.</p>
                <ul>
                    <li><strong>URL:</strong> <code>{baseUrl}/tokenized/checkout/execute</code></li>
                    <li><strong>Method:</strong> <code>POST</code></li>
                    <li><strong>Headers:</strong> Same as Create Payment.</li>
                    <li><strong>Request Body:</strong>
                        <pre>{
  "paymentID": "YOUR_PAYMENT_ID"
}</pre>
                    </li>
                </ul>
            </div>
        </div>

        <!-- WEBHOOK LOGS (IPN) TAB -->
        <div class="tab-content <?= $activeTab === 'ipn' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-title">Instant Payment Notification (IPN) Webhook Simulator</div>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.6;">
                    bKash triggers an IPN (Instant Payment Notification) server-to-server POST call to notify the merchant when transactions change state (e.g. Completed, Refunded) asynchronously. This is crucial if a customer closes their browser window before redirecting.
                </p>
                <div class="alert alert-warning" style="background: rgba(139, 92, 246, 0.1); border-left-color: var(--accent-purple); color: #c084fc;">
                    <span><strong>Webhook URL:</strong> <code>http://<?= $_SERVER['HTTP_HOST'] ?>/raw-php/ipn.php</code></span>
                </div>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: #fff;">Simulate Webhook Trigger</h3>
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                    You can simulate an incoming bKash webhook by submitting this form. It sends a local POST request to your IPN listener.
                </p>
                <form id="webhook-simulator-form" onsubmit="triggerSimulatedWebhook(event)" style="background: rgba(0,0,0,0.2); padding: 2rem; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 2.5rem;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Payment ID</label>
                            <input type="text" name="paymentID" value="<?= $_SESSION['active_payment_id'] ?? 'PAYID-' . rand(100000, 999999) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Transaction ID (TrxID)</label>
                            <input type="text" name="trxID" value="TRX<?= rand(100000,999999) ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="transactionStatus">
                                <option value="Completed">Completed</option>
                                <option value="Refunded">Refunded</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="text" name="amount" value="10.00" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto; margin-top: 1rem; padding-left: 2rem; padding-right: 2rem;">Trigger Simulated Webhook</button>
                </form>

                <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: #fff;">Incoming Webhook Request Log</h3>
                <div class="console-panel">
                    <div class="console-header">
                        <div class="console-dots">
                            <span class="console-dot red"></span>
                            <span class="console-dot yellow"></span>
                            <span class="console-dot green"></span>
                        </div>
                        <span class="console-title">ipn-listener-logs</span>
                    </div>
                    <div class="console-body" style="max-height: 500px;">
                        <?php
                        $ipnLogFile = 'raw-php/ipn_log.json';
                        $ipnLogs = file_exists($ipnLogFile) ? json_encode(json_decode(file_get_contents($ipnLogFile)), JSON_PRETTY_PRINT) : null;
                        if (!$ipnLogs || $ipnLogs === '[]'):
                        ?>
                            <span style="color: var(--text-secondary); font-style: italic;">No webhook calls logged yet. Trigger the simulation form above to see logs.</span>
                        <?php else: ?>
                            <pre style="color: #6ee7b7;"><?= htmlspecialchars($ipnLogs) ?></pre>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
// Auto-dismiss alert after 8 seconds
const alert = document.getElementById('status-alert');
if (alert) {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.6s ease';
        setTimeout(() => alert.remove(), 600);
    }, 8000);
}

function populateQuery(paymentId) {
    const input = document.getElementById('query_payment_id');
    if (input) {
        input.value = paymentId;
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        input.focus();
        // Visual feedback highlight
        input.style.borderColor = 'var(--bkash-pink)';
        setTimeout(() => { input.style.borderColor = 'var(--border-color)'; }, 1500);
    }
}

function populateRefund(paymentId, trxId, amount) {
    const pInput = document.getElementById('refund_payment_id');
    const tInput = document.getElementById('refund_trx_id');
    const aInput = document.getElementById('refund_amount');
    
    if (pInput && tInput && aInput) {
        pInput.value = paymentId;
        tInput.value = trxId;
        aInput.value = amount;
        
        pInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        pInput.focus();
        
        // Highlight inputs
        const inputs = [pInput, tInput, aInput];
        inputs.forEach(el => el.style.borderColor = 'var(--bkash-pink)');
        setTimeout(() => {
            inputs.forEach(el => el.style.borderColor = 'var(--border-color)');
        }, 1500);
    }
}

// Direct AJAX actions mapping
function directQuery(btn, paymentId) {
    const originalText = btn.innerHTML;
    btn.innerHTML = '⌛ ...';
    btn.disabled = true;
    
    fetch('index.php?ajax_action=query&payment_id=' + encodeURIComponent(paymentId))
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            const row = document.getElementById('tx-row-' + paymentId);
            if (row && data.statusCode === '0000') {
                // Flash animation
                row.style.background = 'rgba(16, 185, 129, 0.1)';
                setTimeout(() => { row.style.background = 'transparent'; }, 1500);
                
                // Update trxID cell
                const trxCell = row.querySelector('.trx-id-cell');
                if (trxCell) trxCell.innerText = data.trxID || 'N/A';
                
                // Update Status Badge
                const statusCell = row.querySelector('.status-cell');
                if (statusCell && data.transactionStatus) {
                    let badgeClass = 'warning';
                    if (data.transactionStatus === 'Completed') badgeClass = 'success';
                    else if (data.transactionStatus === 'Refunded') badgeClass = 'purple';
                    else if (data.transactionStatus === 'Failed') badgeClass = 'danger';
                    
                    statusCell.innerHTML = `<span class="status-badge ${badgeClass}" style="padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">${data.transactionStatus}</span>`;
                }
                
                const actionsContainer = row.querySelector('.actions-cell-container');
                if (actionsContainer && data.transactionStatus === 'Completed') {
                    // Add refund button dynamically if it doesn't exist
                    if (!actionsContainer.querySelector('.refund-btn')) {
                        const refundBtn = document.createElement('button');
                        refundBtn.className = 'btn-action refund-btn';
                        refundBtn.style = 'padding: 4px 8px; font-size: 0.75rem; border-radius: 4px; background: rgba(226,18,93,0.1); border: 1px solid rgba(226,18,93,0.3); color: var(--bkash-pink); cursor: pointer; transition: all 0.2s;';
                        refundBtn.innerHTML = '⚡ Refund';
                        refundBtn.onclick = function() { directRefund(refundBtn, paymentId, data.trxID, data.amount); };
                        actionsContainer.appendChild(refundBtn);
                    }
                    // Add receipt link dynamically if it doesn't exist
                    if (!actionsContainer.querySelector('.invoice-btn-link')) {
                        const invoiceLink = document.createElement('a');
                        invoiceLink.className = 'invoice-btn-link';
                        invoiceLink.href = 'raw-php/invoice.php?payment_id=' + encodeURIComponent(paymentId);
                        invoiceLink.target = '_blank';
                        invoiceLink.style = 'padding: 4px 8px; font-size: 0.75rem; border-radius: 4px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #34d399; text-decoration: none; display: inline-block; transition: all 0.2s;';
                        invoiceLink.innerHTML = '📄 Receipt';
                        const refBtn = actionsContainer.querySelector('.refund-btn');
                        if (refBtn) {
                            actionsContainer.insertBefore(invoiceLink, refBtn);
                        } else {
                            actionsContainer.appendChild(invoiceLink);
                        }
                    }
                }
            } else {
                alert('Query failed: ' + (data.statusMessage || 'Unknown error'));
            }
        })
        .catch(err => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            console.error(err);
            alert('Connection error');
        });
}

function directRefund(btn, paymentId, trxId, amount) {
    if (!confirm(`Are you sure you want to refund BDT ${amount} (TrxID: ${trxId})?`)) {
        return;
    }
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '⌛ ...';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('payment_id', paymentId);
    formData.append('trx_id', trxId);
    formData.append('amount', amount);
    formData.append('reason', 'Playground AJAX Refund');
    
    fetch('index.php?ajax_action=refund', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            const row = document.getElementById('tx-row-' + paymentId);
            if (row && data.statusCode === '0000') {
                row.style.background = 'rgba(139, 92, 246, 0.1)';
                setTimeout(() => { row.style.background = 'transparent'; }, 1500);
                
                // Update Status Badge
                const statusCell = row.querySelector('.status-cell');
                if (statusCell) {
                    statusCell.innerHTML = `<span class="status-badge purple" style="padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Refunded</span>`;
                }
                
                // Remove Refund button since it is refunded
                btn.remove();
            } else {
                alert('Refund failed: ' + (data.statusMessage || 'Unknown error'));
            }
        })
        .catch(err => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            console.error(err);
            alert('Connection error');
        });
}

function selectTestWallet(number, scenario) {
    const input = document.getElementById('payer');
    const tip = document.getElementById('wallet-tip');
    if (input && tip) {
        input.value = number;
        tip.style.display = 'block';
        tip.innerHTML = `Scenario: <strong>${scenario}</strong> | Test PIN: <code>12121</code> | OTP: <code>123456</code>`;
        
        // Highlight input
        input.style.borderColor = 'var(--bkash-pink)';
        input.style.boxShadow = '0 0 10px rgba(226, 18, 93, 0.2)';
        setTimeout(() => { 
            input.style.borderColor = 'var(--border-color)'; 
            input.style.boxShadow = 'none';
        }, 1000);
    }
}

function triggerSimulatedWebhook(e) {
    e.preventDefault();
    const form = e.target;
    const data = {
        paymentID: form.querySelector('[name=paymentID]').value,
        trxID: form.querySelector('[name=trxID]').value,
        transactionStatus: form.querySelector('[name=transactionStatus]').value,
        amount: form.querySelector('[name=amount]').value
    };
    
    fetch('raw-php/ipn.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(resData => {
        alert('Simulated webhook logged successfully! Refreshing dashboard.');
        location.reload();
    })
    .catch(err => {
        console.error(err);
        alert('Webhook trigger failed');
    });
}

function exportToCSV() {
    let csv = [];
    const rows = document.querySelectorAll("#txTable tr");
    
    for (const row of rows) {
        const cols = row.querySelectorAll("td, th");
        const data = Array.from(cols).map(col => {
            let text = col.innerText.replace(/,/g, "");
            // Clean up multiline text for CSV
            return text.replace(/\n/g, " ").trim();
        });
        // Remove the 'Actions' column from export
        data.pop(); 
        csv.push(data.join(","));
    }
    
    const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "transaction_history.csv");
    document.body.appendChild(link);
    link.click();
}

document.getElementById('txSearch')?.addEventListener('keyup', function(e) {
    const term = e.target.value.toLowerCase();
    const rows = document.querySelectorAll("#txTable tbody tr");
    
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? "" : "none";
    });
});

// Chart.js Initialization
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('txChart');
    if (!ctx) return;

    <?php
    $txs = BkashDb::getAllTransactions();
    $chartData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayName = date('D', strtotime($date));
        $count = 0;
        foreach ($txs as $tx) {
            if (date('Y-m-d', strtotime($tx['updated_at'])) === $date && $tx['status'] === 'Completed') {
                $count++;
            }
        }
        $chartData[$dayName] = $count;
    }
    ?>

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($chartData)) ?>,
            datasets: [{
                label: 'Completed Payments',
                data: <?= json_encode(array_values($chartData)) ?>,
                borderColor: '#e2125d',
                backgroundColor: 'rgba(226, 18, 93, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#e2125d',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#9ca3af' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#9ca3af' }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

</body>
</html>
