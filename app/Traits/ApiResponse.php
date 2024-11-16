<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Api Response
 */
trait ApiResponse
{
    /**
     * @param $message
     *
     * @return JsonResponse
     */
    protected function ok($message = 'OK'): JsonResponse
    {
        return $this->success($message, 200);
    }

    /**
     * @param $message
     * @param $statusCode
     *
     * @return JsonResponse
     */
    protected function success($message, $statusCode = 200): JsonResponse
    {
        return $this->response($message, $statusCode);
    }

    /**
     * @param $message
     * @param $statusCode
     *
     * @return JsonResponse
     */
    protected function error($message, $statusCode = 400): JsonResponse
    {
        return $this->response($message, $statusCode);
    }

    /**
     * @param $message
     * @param $statusCode
     *
     * @return JsonResponse
     */
    protected function response($message, $statusCode = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'status' => $statusCode
        ], $statusCode);
    }
}
