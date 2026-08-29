<?php

namespace App\Enums;

enum IssueSort: string
{
    case LastSeen = 'last_seen';
    case FirstSeen = 'first_seen';
    case Events = 'events';
    case Users = 'users';

    /**
     * Get the issues table column to sort by.
     */
    public function column(): string
    {
        return match ($this) {
            self::LastSeen => 'last_seen',
            self::FirstSeen => 'first_seen',
            self::Events => 'event_count',
            self::Users => 'user_count',
        };
    }
}
