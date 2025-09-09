<?php

namespace App\Http\Enums;

enum CollectionStatusEnum: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';

    /**
     * Returns all cases except COMPLETED.
     * 
     * @return array<self>
     */
    public static function notCompleted(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::COMPLETED);
    }
}
