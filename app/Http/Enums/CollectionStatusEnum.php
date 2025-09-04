<?php

namespace App\Http\Enums;

enum CollectionStatusEnum: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
}
