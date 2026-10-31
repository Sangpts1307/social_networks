<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ResponseAPI
{
    public static function success(mixed $data, int $status = 200, ?array $meta = null): JsonResponse
    {
        $body = ['data' => $data];
        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    public static function error(string $message, int $status, ?string $code = null, array|object|null $errors = null): JsonResponse
    {
        $body = ['message' => $message];
        if ($code !== null) {
            $body['code'] = $code;
        }
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
