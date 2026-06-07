<?php

class BkashDb {
    private static $pdo = null;

    /**
     * Get or initialize SQLite PDO connection
     */
    public static function connect() {
        if (self::$pdo === null) {
            $dbPath = __DIR__ . '/database.sqlite';
            try {
                self::$pdo = new PDO("sqlite:" . $dbPath);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::initSchema();
            } catch (PDOException $e) {
                die("SQLite Connection Error: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    /**
     * Create database schema if it doesn't exist
     */
    private static function initSchema() {
        $sql = "CREATE TABLE IF NOT EXISTS bkash_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            payment_id TEXT UNIQUE,
            trx_id TEXT,
            amount REAL,
            invoice_no TEXT,
            payer_reference TEXT,
            status TEXT,
            refund_trx_id TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        self::$pdo->exec($sql);
    }

    /**
     * Save newly created payment session
     */
    public static function createPaymentRecord($paymentId, $amount, $invoiceNo, $payerRef) {
        $db = self::connect();
        $sql = "INSERT INTO bkash_transactions (payment_id, amount, invoice_no, payer_reference, status) 
                VALUES (:payment_id, :amount, :invoice_no, :payer_ref, 'Initiated')";
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':payment_id' => $paymentId,
                ':amount' => $amount,
                ':invoice_no' => $invoiceNo,
                ':payer_ref' => $payerRef
            ]);
        } catch (PDOException $e) {
            // Ignore duplicate insert errors if any
        }
    }

    /**
     * Update transaction after execute success
     */
    public static function completePaymentRecord($paymentId, $trxId) {
        $db = self::connect();
        $sql = "UPDATE bkash_transactions 
                SET trx_id = :trx_id, status = 'Completed', updated_at = CURRENT_TIMESTAMP 
                WHERE payment_id = :payment_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':trx_id' => $trxId,
            ':payment_id' => $paymentId
        ]);
    }

    /**
     * Update transaction after refund success
     */
    public static function refundPaymentRecord($paymentId, $refundTrxId) {
        $db = self::connect();
        $sql = "UPDATE bkash_transactions 
                SET refund_trx_id = :refund_trx_id, status = 'Refunded', updated_at = CURRENT_TIMESTAMP 
                WHERE payment_id = :payment_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':refund_trx_id' => $refundTrxId,
            ':payment_id' => $paymentId
        ]);
    }

    /**
     * Update transaction status (failed, cancelled)
     */
    public static function updateStatus($paymentId, $status) {
        $db = self::connect();
        $sql = "UPDATE bkash_transactions 
                SET status = :status, updated_at = CURRENT_TIMESTAMP 
                WHERE payment_id = :payment_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':status' => $status,
            ':payment_id' => $paymentId
        ]);
    }

    /**
     * Fetch all transactions ordered by latest
     */
    public static function getAllTransactions() {
        $db = self::connect();
        $stmt = $db->query("SELECT * FROM bkash_transactions ORDER BY id DESC");
        return $stmt->fetchAll();
    }
}
