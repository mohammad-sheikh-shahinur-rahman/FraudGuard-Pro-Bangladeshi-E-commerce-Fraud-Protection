<?php

namespace FraudChecker;

use FraudChecker\Contracts\CourierInterface;

class FraudChecker
{
    private $couriers = [];

    /**
     * Add a courier driver to the checker.
     */
    public function addCourier(CourierInterface $courier)
    {
        $this->couriers[] = $courier;
    }

    /**
     * Check a phone number across all added couriers.
     */
    public function check(string $phone, bool $useCache = true): array
    {
        // 1. Check Cache first (for Core PHP)
        if ($useCache && class_exists('FraudChecker\Support\LocalCache')) {
            $cached = \FraudChecker\Support\LocalCache::get($phone);
            if ($cached) {
                $cached['from_cache'] = true;
                return $cached;
            }
        }

        $report = [
            'phone' => $phone,
            'timestamp' => date('Y-m-d H:i:s'),
            'from_cache' => false,
            'details' => [],
            'aggregate' => [
                'total_success' => 0,
                'total_cancel' => 0,
                'total_orders' => 0,
                'success_rate' => 0,
                'risk_level' => 'Low',
                'recommendation' => 'Accept Order'
            ]
        ];

        foreach ($this->couriers as $courier) {
            $stats = $courier->getStats($phone);
            $report['details'][$courier->getName()] = $stats;

            $report['aggregate']['total_success'] += $stats['success'] ?? 0;
            $report['aggregate']['total_cancel'] += $stats['cancel'] ?? 0;
            $report['aggregate']['total_orders'] += $stats['total'] ?? 0;
        }

        // Calculate Success Rate
        $total = $report['aggregate']['total_success'] + $report['aggregate']['total_cancel'];
        if ($total > 0) {
            $report['aggregate']['success_rate'] = round(($report['aggregate']['total_success'] / $total) * 100, 2);
        }

        // Advanced Risk Assessment
        $cancelCount = $report['aggregate']['total_cancel'];
        $successRate = $report['aggregate']['success_rate'];

        if ($cancelCount >= 5 || ($total > 3 && $successRate < 60)) {
            $report['aggregate']['risk_level'] = 'High';
            $report['aggregate']['recommendation'] = 'Call Customer / Advanced Payment Required';
        } elseif ($cancelCount >= 2 || ($total > 3 && $successRate < 80)) {
            $report['aggregate']['risk_level'] = 'Medium';
            $report['aggregate']['recommendation'] = 'Verify via Phone Call';
        }

        // Save to Cache
        if ($useCache && class_exists('FraudChecker\Support\LocalCache')) {
            \FraudChecker\Support\LocalCache::set($phone, $report, 3600); // Cache for 1 hour
        }

        return $report;
    }
}
