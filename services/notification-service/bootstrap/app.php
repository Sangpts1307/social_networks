<?php

use App\Http\Middleware\RequireGatewayContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(api: __DIR__.'/../routes/api.php', web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToPriorityList(ThrottleRequests::class, RequireGatewayContext::class);
        $middleware->alias(['notification.gateway' => RequireGatewayContext::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
        $exceptions->render(function (Throwable $error, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            if ($error instanceof HttpResponseException) {
                return $error->getResponse();
            }
            $status = $error instanceof ValidationException ? 422 : ($error instanceof HttpExceptionInterface ? $error->getStatusCode() : 500);
            $codes = [401 => 'UNAUTHENTICATED', 403 => 'FORBIDDEN', 404 => 'NOT_FOUND', 405 => 'METHOD_NOT_ALLOWED', 409 => 'CONFLICT', 422 => 'VALIDATION_ERROR', 429 => 'RATE_LIMITED', 503 => 'SERVICE_UNAVAILABLE'];

            return response()->json(['message' => $status >= 500 ? 'Notification service unavailable.' : $error->getMessage(), 'code' => $codes[$status] ?? 'INTERNAL_ERROR', 'errors' => $error instanceof ValidationException ? $error->errors() : (object) []], $status, $error instanceof HttpExceptionInterface ? $error->getHeaders() : []);
        });
        $exceptions->respond(function ($response) {
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
        });
    })->create();
