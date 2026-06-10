<?php

namespace FraudChecker\Support;

class HistoryService {
    private $historyFile;
    private $pdo;

    public function __construct($config) {
        $this->historyFile = $config['paths']['storage'] . 'history.json';
        if (!file_exists($config['paths']['storage'])) {
            mkdir($config['paths']['storage'], 0777, true);
        }

        // Initialize PDO for database persistence
        try {
            $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['name']};charset=utf8mb4";
            $this->pdo = new \PDO($dsn, $config['database']['user'], $config['database']['pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            // Silently fail if DB is not ready, fallback to JSON
            $this->pdo = null;
        }
    }

    public function add($phone, $aggregate) {
        // 1. Save to Database
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("INSERT INTO fraud_checks (phone, success_rate, total_cancel, risk_level, recommendation) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $phone,
                    $aggregate['success_rate'],
                    $aggregate['total_cancel'],
                    $aggregate['risk_level'],
                    $aggregate['recommendation']
                ]);
            } catch (\PDOException $e) {
                // Log error if needed
            }
        }

        // 2. Save to JSON (for Dashboard quick access)
        $history = $this->getAll();
        
        // Remove existing if any to avoid duplicates and move to top
        foreach ($history as $key => $item) {
            if ($item['phone'] === $phone) {
                unset($history[$key]);
            }
        }

        array_unshift($history, [
            'phone' => $phone,
            'risk' => $aggregate['risk_level'],
            'rate' => $aggregate['success_rate'],
            'time' => date('Y-m-d H:i:s')
        ]);

        // Keep last 20
        $history = array_slice($history, 0, 20);
        file_put_contents($this->historyFile, json_encode(array_values($history), JSON_PRETTY_PRINT));
    }

    public function getAll() {
        if (!file_exists($this->historyFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->historyFile), true) ?: [];
    }

    public function getStats() {
        $history = $this->getAll();
        $total = count($history);
        $highRisk = 0;
        foreach ($history as $item) {
            if ($item['risk'] === 'High') $highRisk++;
        }

        return [
            'total_checks' => $total,
            'high_risk_count' => $highRisk,
            'fraud_rate' => $total > 0 ? round(($highRisk / $total) * 100, 1) : 0
        ];
    }

    public function getRiskDistribution() {
        $history = $this->getAll();
        $dist = ['Low' => 0, 'Medium' => 0, 'High' => 0];
        foreach ($history as $item) {
            $risk = $item['risk'] ?? 'Low';
            if (isset($dist[$risk])) {
                $dist[$risk]++;
            }
        }
        return $dist;
    }
}
