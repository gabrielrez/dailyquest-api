<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ConflictException extends Exception
{
    public function __construct(string $message = "Conflict")
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'conflict',
            'message' => $this->getMessage(),
        ], 409);
    }
}
