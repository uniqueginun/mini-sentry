<?php

namespace App\Enums;

enum AlertRuleType: string
{
    case NewIssue = 'new_issue';
    case Regression = 'regression';
    case EventFrequency = 'event_frequency';
    case AffectedUsers = 'affected_users';
    case Environment = 'environment';
}
