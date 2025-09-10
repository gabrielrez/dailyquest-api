<?php

namespace App\Http\Enums;

enum InvitationStatusEnum: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
}
