<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatAccess;
use App\Http\Controllers\Api\ChatMessageController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ChatRouteContractTest extends TestCase
{
    public function test_message_routes_keep_their_methods_paths_and_gateway_guard(): void
    {
        $expected = [
            'GET' => ['api/v1/conversations/{conversationId}/messages' => 'messages'],
            'POST' => ['api/v1/conversations/{conversationId}/messages' => 'send'],
            'PUT' => ['api/v1/conversations/{conversationId}/read' => 'read'],
        ];

        foreach ($expected as $method => $paths) {
            foreach ($paths as $path => $action) {
                $route = collect(Route::getRoutes()->getRoutes())->first(
                    fn ($route) => $route->uri() === $path && in_array($method, $route->methods(), true)
                );
                $this->assertNotNull($route);
                $this->assertSame(ChatMessageController::class.'@'.$action, $route->getActionName());
                $this->assertContains('chat.gateway', $route->gatherMiddleware());
                $this->assertContains('throttle:chat', $route->gatherMiddleware());
            }
        }

        $this->assertTrue(is_subclass_of(ChatMessageController::class, ChatAccess::class));
        $this->assertTrue(is_subclass_of(ChatController::class, ChatAccess::class));
    }

    public function test_message_routes_reject_requests_without_gateway_identity(): void
    {
        config()->set('services.gateway_key', str_repeat('c', 40));

        $this->getJson('/api/v1/conversations/10000000-0000-4000-8000-000000000001/messages')->assertUnauthorized();
        $this->postJson('/api/v1/conversations/10000000-0000-4000-8000-000000000001/messages', [])->assertUnauthorized();
        $this->putJson('/api/v1/conversations/10000000-0000-4000-8000-000000000001/read', [])->assertUnauthorized();
    }
}
