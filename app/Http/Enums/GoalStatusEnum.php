<?php

namespace App\Http\Enums;

enum GoalStatusEnum: string
{
    case TODO = 'to_do';
    case DOING = 'doing';
    case DONE = 'done';

    /**
     * Returns all cases except DONE.
     * 
     * @return array<self>
     */
    public static function notCompleted(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::DONE);
    }
}
