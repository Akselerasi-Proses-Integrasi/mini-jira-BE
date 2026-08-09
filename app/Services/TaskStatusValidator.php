<?php

namespace App\Services;

class TaskStatusValidator
{
    private const TRANSITIONS = [
        'to do'             => ['in progress', 'blocked'],
        'in progress'       => ['to do', 'blocked', 'waiting approval'],
        'blocked'           => ['to do', 'in progress'],
        'waiting approval'  => ['done', 'in progress'],
        'done'              => ['to do', 'in progress', 'blocked', 'waiting approval'],
    ];

    public function isValidTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function getAllowedTransitions(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }
}