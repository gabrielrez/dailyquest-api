<?php

namespace App\Http\Traits;

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
        return response()->json($data, $status, $headers);
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
        return response()->json($data, $status, $headers);
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
        return response()->json($data, $status, $headers);
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

        return response()->json($data, $status, $headers);
    }
}
