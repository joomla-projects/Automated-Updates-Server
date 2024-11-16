<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Api Response
 */
trait ApiResponse
{
    /**
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function ok(string $message = 'OK'): JsonResponse
    {
        return $this->success($message, 200);
    }

    /**
     * @param string $message
     * @param int    $statusCode
     *
     * @return JsonResponse
     */
    protected function success(string $message, int $statusCode = 200): JsonResponse
    {
        return $this->response($message, $statusCode);
    }

    /**
     * @param string $message
     * @param int    $statusCode
     *
     * @return JsonResponse
     */
    protected function error(string $message, int $statusCode = 400): JsonResponse
    {
        return $this->response($message, $statusCode);
    }

    /**
     * @param string $message
     * @param int    $statusCode
     *
     * @return JsonResponse
     */
    protected function response(string $message, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'status' => $statusCode
        ], $statusCode);
    }
}
