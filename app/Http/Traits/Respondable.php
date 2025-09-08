<?php

namespace App\Http\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;

trait Respondable
{
    /**
     * Return a JSON response with the given data, status, and headers.
     *
     * @param mixed $data    The response data to be returned as JSON.
     * @param int   $status  The HTTP status code (default: 200).
     * @param array $headers Additional headers for the response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respond($data, $status = 200, $headers = [])
    {
        if ($data instanceof LengthAwarePaginator || $data instanceof Paginator) {
            return response()->json($data, $status, $headers);
        }

        if (is_string($data)) {
            return response()->json([
                'message' => $data,
            ], $status, $headers);
        }

        return response()->json([
            'data' => $data,
        ], $status, $headers);
    }

    /**
     * Return a JSON response for a successfully created resource.
     *
     * @param mixed $data    The created resource data.
     * @param int   $status  The HTTP status code (default: 201).
     * @param array $headers Additional headers for the response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondCreated($data, $status = 201, $headers = [])
    {
        return $this->respond($data, $status, $headers);
    }

    /**
     * Return a JSON response for a successfully updated resource.
     *
     * @param mixed $data    The updated resource data.
     * @param int   $status  The HTTP status code (default: 200).
     * @param array $headers Additional headers for the response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondUpdated($data, $status = 200, $headers = [])
    {
        return $this->respond($data, $status, $headers);
    }

    /**
     * Return a JSON response for a successfully deleted resource.
     *
     * Note: By REST convention, the default status is 204 (No Content),
     * which usually does not return any body. But you can still pass $data if needed.
     *
     * @param mixed $data    Additional response data (optional).
     * @param int   $status  The HTTP status code (default: 204).
     * @param array $headers Additional headers for the response.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function respondDeleted($data = null, $status = 204, $headers = [])
    {
        if ($status === 204) {
            return response()->noContent($status, $headers);
        }

        return $this->respond($data, $status, $headers);
    }

    /**
     * Throw a NotFoundException (HTTP 404).
     *
     * @param string $message Error message.
     * @throws \App\Exceptions\NotFoundException
     * @return never
     */
    public function failNotFound($message = 'Not Found')
    {
        throw new \App\Exceptions\NotFoundException($message);
    }

    /**
     * Throw an UnauthorizedException (HTTP 401).
     *
     * @param string $message Error message.
     * @throws \App\Exceptions\UnauthorizedException
     * @return never
     */
    public function failUnauthorized($message = 'Unauthorized')
    {
        throw new \App\Exceptions\UnauthorizedException($message);
    }

    /**
     * Throw a ForbiddenException (HTTP 403).
     *
     * @param string $message Error message.
     * @throws \App\Exceptions\ForbiddenException
     * @return never
     */
    public function failForbidden($message = 'Forbidden')
    {
        throw new \App\Exceptions\ForbiddenException($message);
    }

    /**
     * Throw an UnprocessableEntityException (HTTP 422).
     *
     * @param string $message Error message.
     * @throws \App\Exceptions\UnprocessableEntityException
     * @return never
     */
    public function failUnprocessableEntity($message = 'Unprocessable Entity')
    {
        throw new \App\Exceptions\UnprocessableEntityException($message);
    }
}
