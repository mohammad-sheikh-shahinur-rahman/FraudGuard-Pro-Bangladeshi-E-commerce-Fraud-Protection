<?php

namespace FraudChecker\Contracts;

/**
 * Interface CourierInterface
 * Every courier driver must implement this to ensure data consistency.
 */
interface CourierInterface
{
    /**
     * Get the display name of the courier.
     * @return string
     */
    public function getName(): string;

    /**
     * Fetch delivery statistics for a specific phone number.
     * @param string $phone
     * @return array
     */
    public function getStats(string $phone): array;
}
