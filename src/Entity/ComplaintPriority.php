<?php

namespace App\Entity;

enum ComplaintPriority: string
{
    case LOW    = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH   = 'HIGH';
    case URGENT = 'URGENT';

    /**
     * Human-readable label.
     */
    public function getLabel(): string
    {
        return match($this) {
            self::LOW    => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH   => 'High',
            self::URGENT => 'Urgent',
        };
    }

    /**
     * Bootstrap badge colour class.
     */
    public function getBadgeClass(): string
    {
        return match($this) {
            self::LOW    => 'secondary',
            self::MEDIUM => 'info',
            self::HIGH   => 'warning',
            self::URGENT => 'danger',
        };
    }

    /**
     * Bootstrap-Icons class.
     */
    public function getIcon(): string
    {
        return match($this) {
            self::LOW    => 'bi bi-arrow-down',
            self::MEDIUM => 'bi bi-dash',
            self::HIGH   => 'bi bi-arrow-up',
            self::URGENT => 'bi bi-exclamation-circle-fill',
        };
    }

    /**
     * Numeric weight — useful for custom sorting outside Doctrine (higher = more urgent).
     */
    public function getWeight(): int
    {
        return match($this) {
            self::LOW    => 1,
            self::MEDIUM => 2,
            self::HIGH   => 3,
            self::URGENT => 4,
        };
    }

    /**
     * Returns all cases ordered from most to least urgent.
     *
     * @return self[]
     */
    public static function orderedByUrgency(): array
    {
        return [self::URGENT, self::HIGH, self::MEDIUM, self::LOW];
    }

    /**
     * Resolve a priority from an untrusted string (form / API input).
     * Returns MEDIUM as a safe default when the value is unknown.
     */
    public static function fromStringOrDefault(string $value): self
    {
        return self::tryFrom(strtoupper($value)) ?? self::MEDIUM;
    }
}