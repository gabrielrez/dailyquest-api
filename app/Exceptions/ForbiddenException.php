<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ForbiddenException  extends Exception
{
    public function __construct(string $message = "Forbidden")
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'forbidden',
            'message' => $this->getMessage(),
        ], 403);
    }
}
