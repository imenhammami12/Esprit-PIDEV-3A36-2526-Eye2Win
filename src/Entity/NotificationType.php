<?php

namespace App\Entity;

enum NotificationType: string
{
    case TEAM_INVITATION = 'TEAM_INVITATION';
    case TEAM_ACCEPTED = 'TEAM_ACCEPTED';
    case COACH_APPLICATION = 'COACH_APPLICATION';
    case COACH_APPROVED = 'COACH_APPROVED';
    case COACH_REJECTED = 'COACH_REJECTED';
    case CHANNEL_APPROVED = 'CHANNEL_APPROVED';
    case CHANNEL_REJECTED = 'CHANNEL_REJECTED';
    case ACCOUNT_WARNING = 'ACCOUNT_WARNING';
    case SYSTEM = 'SYSTEM';
    case CHANNEL_ACCESS_REQUEST = 'CHANNEL_ACCESS_REQUEST';
    case CHANNEL_ACCESS_APPROVED = 'CHANNEL_ACCESS_APPROVED';
    case CHANNEL_ACCESS_REJECTED = 'CHANNEL_ACCESS_REJECTED';

    public function getLabel(): string
    {
        return match($this) {
            self::TEAM_INVITATION => 'Invitation d\'équipe',
            self::TEAM_ACCEPTED => 'Invitation acceptée',
            self::COACH_APPLICATION => 'Demande de coach',
            self::COACH_APPROVED => 'Coach approuvé',
            self::COACH_REJECTED => 'Coach rejeté',
            self::CHANNEL_APPROVED => 'Channel approuvé',
            self::CHANNEL_REJECTED => 'Channel rejeté',
            self::ACCOUNT_WARNING => 'Avertissement',
            self::SYSTEM => 'Système',
            self::CHANNEL_ACCESS_REQUEST => 'Channel access request',
            self::CHANNEL_ACCESS_APPROVED => 'Access to approved channel',
            self::CHANNEL_ACCESS_REJECTED => 'Access to rejected channel',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::TEAM_INVITATION => '📨',
            self::TEAM_ACCEPTED => '✅',
            self::COACH_APPLICATION => '📋',
            self::COACH_APPROVED => '🎓',
            self::COACH_REJECTED => '❌',
            self::CHANNEL_APPROVED => '✅',
            self::CHANNEL_REJECTED => '❌',
            self::ACCOUNT_WARNING => '⚠️',
            self::SYSTEM => 'ℹ️',
            self::CHANNEL_ACCESS_REQUEST => '🔒',
            self::CHANNEL_ACCESS_APPROVED => '✅',
            self::CHANNEL_ACCESS_REJECTED => '❌',
        };
    }
}