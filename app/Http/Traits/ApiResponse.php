<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function ok(mixed $data, string $message = ''): JsonResponse
    {
        return $this->success($data, $message, 200);
    }

    protected function created(mixed $data, string $message = ''): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function success(mixed $data, string $message = '', int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if ($message !== '') {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    protected function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    protected function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
