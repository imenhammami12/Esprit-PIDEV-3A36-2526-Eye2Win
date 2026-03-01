<?php

namespace App\Entity;

enum ComplaintStatus: string
{
    case PENDING     = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED    = 'RESOLVED';
    case CLOSED      = 'CLOSED';
    case REJECTED    = 'REJECTED';

    /**
     * Human-readable label.
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING     => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::RESOLVED    => 'Resolved',
            self::CLOSED      => 'Closed',
            self::REJECTED    => 'Rejected',
        };
    }

    /**
     * Bootstrap badge colour class.
     */
    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING     => 'warning',
            self::IN_PROGRESS => 'info',
            self::RESOLVED    => 'success',
            self::CLOSED      => 'secondary',
            self::REJECTED    => 'danger',
        };
    }

    /**
     * Bootstrap-Icons class (without the "bi " prefix so callers can compose freely).
     */
    public function getIcon(): string
    {
        return match($this) {
            self::PENDING     => 'bi bi-clock-history',
            self::IN_PROGRESS => 'bi bi-arrow-repeat',
            self::RESOLVED    => 'bi bi-check-circle',
            self::CLOSED      => 'bi bi-x-circle',
            self::REJECTED    => 'bi bi-exclamation-triangle',
        };
    }

    /**
     * Returns true when the complaint is considered "done" (no more actions needed).
     */
    public function isFinal(): bool
    {
        return match($this) {
            self::RESOLVED, self::CLOSED, self::REJECTED => true,
            default => false,
        };
    }

    /**
     * Allowed transitions FROM this status.
     * Prevents illegal status jumps at the domain level.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::PENDING     => [self::IN_PROGRESS, self::REJECTED],
            self::IN_PROGRESS => [self::RESOLVED, self::REJECTED],
            self::RESOLVED    => [self::CLOSED],
            self::CLOSED      => [],
            self::REJECTED    => [],
        };
    }

    /**
     * Quick factory — returns null instead of throwing on unknown values.
     * Useful when reading raw DB / form strings.
     */
    public static function tryFromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if (strtolower($case->getLabel()) === strtolower($label)) {
                return $case;
            }
        }
        return null;
    }
}