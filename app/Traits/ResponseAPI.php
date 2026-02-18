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

    public function sendSuccessPagination($message, $pagination, $data = null, int $code = 200, $extra = []): \Illuminate\Http\JsonResponse
    {
        $hasTotal = method_exists($pagination, 'total');

        return response()->json(
            array_merge([
                    'error' => false,
                    'status' => 'success',
                    'message' => $message,
                    'data' => $data ?? $pagination->items(),
                    'current_page' => $pagination->currentPage(),
                    'per_page' => $pagination->perPage(),
                    'total' => $hasTotal ? $pagination->total() : null,
                    'has_more_pages' => $pagination->hasMorePages(),
                ], $extra),
            $code,
            [],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    public function sendSuccessSimplePagination($message, $pagination, $data = null, int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'error' => false,
                'status' => 'success',
                'message' => $message,
                'data' => $data ?? $pagination->items(),
                'current_page' => $pagination->currentPage(),
                'per_page' => $pagination->perPage(),
                'has_more_pages' => $pagination->hasMorePages(),
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
