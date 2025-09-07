<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class UnauthorizedException extends Exception
{
    public function __construct(string $message = "Unauthorized")
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'unauthorized',
            'message' => $this->getMessage(),
        ], 401);
    }
}
