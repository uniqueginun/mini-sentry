<?php

namespace App\Enums;

enum IssueStatus: string
{
    case Unresolved = 'unresolved';
    case Resolved = 'resolved';
    case Regressed = 'regressed';
}
