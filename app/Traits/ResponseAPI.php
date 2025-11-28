<?php

namespace App\Traits;

trait ResponseAPI
{
    /**
     * return error response.
     *
     * @param  mixed  $message
     * @param  null|mixed  $data
     */
    public function sendSuccess($message, $data = null, int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'error' => false,
                'status' => 'success',
                'message' => $message,
                'data' => $data,
            ],
            $code,
            [],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * return error response.
     *
     * @param  mixed  $message
     * @param  null|mixed  $data
     */
    public function sendError($message, int $errorCode = 500, $data = null): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'error' => true,
                'status' => 'error',
                'message' => $message,
                'data' => $data,
            ],
            $errorCode,
            [],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}
