<?php

namespace App\Http\Services;

use App\LogActionEnum;
use App\Models\Log;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LogService
{
    public static function log(LogActionEnum $action, ?User $user = null): void
    {
        Log::create([
            'user_id' => $user?->id ?? Auth::id(),
            'action' => $action->value,
        ]);
    }
}
