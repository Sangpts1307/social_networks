<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class JsonContract
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store');
        if ($response instanceof JsonResponse) {
            $response->setData($this->normalize($response->getData(true)));
        }

        return $response;
    }

    private function normalize(mixed $value, string $key = ''): mixed
    {
        if ($key === 'errors' && $value === []) {
            return (object) [];
        }
        if (is_array($value)) {
            foreach ($value as $field => $item) {
                $value[$field] = $this->normalize($item, (string) $field);
            }
        } elseif ($value !== null && str_ends_with($key, '_at') && is_string($value)) {
            $value = Carbon::parse($value, 'UTC')->utc()->toISOString();
        } elseif ($key === 'is_saved') {
            $value = (bool) $value;
        } elseif (str_ends_with($key, '_count')) {
            $value = (int) $value;
        }

        return $value;
    }
}
