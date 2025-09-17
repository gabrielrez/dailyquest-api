<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BadRequestException extends Exception
{
    public function __construct(string $message = "Bad Request")
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'bad_request',
            'message' => $this->getMessage(),
        ], 400);
    }
}
