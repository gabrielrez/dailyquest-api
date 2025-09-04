<?php

namespace App\Http\Enums;

enum GoalStatusEnum: string
{
    case TODO = 'to_do';
    case DONE = 'done';
}