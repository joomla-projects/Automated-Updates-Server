<?php

namespace App\Traits;

trait ApiResponse
{
    protected function ok($message = 'OK') {
        return $this->success($message, 200);
    }

    protected function success($message, $statusCode = 200)
    {
        return $this->response($message, $statusCode);
    }

    protected function error($message, $statusCode = 400)
    {
        return $this->response($message, $statusCode);
    }

    protected function response($message, $statusCode = 200)
    {
        return response()->json([
            'message' => $message,
            'status' => $statusCode
        ], $statusCode);
    }
}
