<?php

namespace App\Entity;

enum ComplaintCategory: string
{
    case TECHNICAL  = 'TECHNICAL';
    case ACCOUNT    = 'ACCOUNT';
    case TOURNAMENT = 'TOURNAMENT';
    case TEAM       = 'TEAM';
    case PAYMENT    = 'PAYMENT';
    case CONTENT    = 'CONTENT';
    case HARASSMENT = 'HARASSMENT';
    case BUG        = 'BUG';
    case OTHER      = 'OTHER';

    /**
     * Human-readable label.
     */
    public function getLabel(): string
    {
        return match($this) {
            self::TECHNICAL  => 'Technical Issue',
            self::ACCOUNT    => 'Account Problem',
            self::TOURNAMENT => 'Tournament Issue',
            self::TEAM       => 'Team Problem',
            self::PAYMENT    => 'Payment Issue',
            self::CONTENT    => 'Content Violation',
            self::HARASSMENT => 'Harassment',
            self::BUG        => 'Bug Report',
            self::OTHER      => 'Other',
        };
    }

    /**
     * Bootstrap-Icons class.
     */
    public function getIcon(): string
    {
        return match($this) {
            self::TECHNICAL  => 'bi bi-tools',
            self::ACCOUNT    => 'bi bi-person-circle',
            self::TOURNAMENT => 'bi bi-trophy',
            self::TEAM       => 'bi bi-people',
            self::PAYMENT    => 'bi bi-credit-card',
            self::CONTENT    => 'bi bi-file-earmark-text',
            self::HARASSMENT => 'bi bi-shield-exclamation',
            self::BUG        => 'bi bi-bug',
            self::OTHER      => 'bi bi-question-circle',
        };
    }

    /**
     * Short description shown in the complaint form.
     */
    public function getDescription(): string
    {
        return match($this) {
            self::TECHNICAL  => 'Technical problems with the platform',
            self::ACCOUNT    => 'Issues related to your account',
            self::TOURNAMENT => 'Problems with tournaments',
            self::TEAM       => 'Team-related issues',
            self::PAYMENT    => 'Payment and billing issues',
            self::CONTENT    => 'Inappropriate content',
            self::HARASSMENT => 'Report harassment or abuse',
            self::BUG        => 'Report a bug or error',
            self::OTHER      => 'Other issues not listed above',
        };
    }

    /**
     * Default priority suggestion when this category is selected.
     * Helps pre-fill the priority field in the admin panel.
     */
    public function getDefaultPriority(): ComplaintPriority
    {
        return match($this) {
            self::HARASSMENT => ComplaintPriority::URGENT,
            self::PAYMENT    => ComplaintPriority::HIGH,
            self::BUG        => ComplaintPriority::HIGH,
            self::TECHNICAL  => ComplaintPriority::MEDIUM,
            default          => ComplaintPriority::MEDIUM,
        };
    }

    /**
     * Resolve a category from an untrusted string. Returns null on failure.
     */
    public static function tryFromString(string $value): ?self
    {
        return self::tryFrom(strtoupper($value));
    }

    /**
     * Returns cases grouped for display (e.g. grouped <optgroup> in a select).
     *
     * @return array<string, self[]>
     */
    public static function grouped(): array
    {
        return [
            'Platform'  => [self::TECHNICAL, self::BUG],
            'Account'   => [self::ACCOUNT, self::PAYMENT],
            'Community' => [self::HARASSMENT, self::CONTENT],
            'Events'    => [self::TOURNAMENT, self::TEAM],
            'Other'     => [self::OTHER],
        ];
    }
}