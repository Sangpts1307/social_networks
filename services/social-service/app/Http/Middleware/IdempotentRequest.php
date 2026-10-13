<?php

namespace App\Http\Middleware;

use App\Services\SocialAccess;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IdempotentRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post') || ! $request->hasHeader('Idempotency-Key')) {
            return $next($request);
        }
        $key = $request->header('Idempotency-Key');
        abort_unless(is_string($key) && preg_match('/^[a-zA-Z0-9._:-]{1,128}$/D', $key), 422, 'Invalid Idempotency-Key.');
        $actor = $request->attributes->get('social_actor');
        $scope = hash('sha256', implode('|', [$actor['organization_id'], $actor['user_id'], $request->method(), $request->path(), $key]));
        $hash = hash('sha256', $request->getContent());

        return DB::transaction(function () use ($request, $next, $scope, $hash): Response {
            // ON DUPLICATE KEY UPDATE acquires an exclusive lock immediately; INSERT IGNORE
            // can leave competing shared locks that deadlock when upgraded by SELECT FOR UPDATE.
            DB::table('api_idempotency_keys')->upsert([['scope_hash' => $scope, 'request_hash' => $hash, 'created_at' => now()]], ['scope_hash'], ['scope_hash']);
            $entry = DB::table('api_idempotency_keys')->where('scope_hash', $scope)->lockForUpdate()->first();
            abort_unless(hash_equals($entry->request_hash, $hash), 409, 'Idempotency-Key was used for a different request.');
            if ($entry->response_status !== null) {
                $this->authorizeReplay($request, $actor = $request->attributes->get('social_actor'));
                $response = response($entry->response_body, $entry->response_status)->header('Content-Type', 'application/json')->header('Idempotency-Replayed', 'true');
                if ($entry->response_location) {
                    $response->header('Location', $entry->response_location);
                }

                return $response;
            }
            $response = $next($request);
            if ($response->getStatusCode() >= 400) {
                // Route exceptions are rendered inside Laravel's pipeline. Roll back any nested writes too.
                throw new HttpResponseException($response);
            }
            DB::table('api_idempotency_keys')->where('scope_hash', $scope)->update(['response_status' => $response->getStatusCode(), 'response_body' => $response->getContent(), 'response_location' => $response->headers->get('Location')]);

            return $response;
        }, 3);
    }

    private function authorizeReplay(Request $request, array $actor): void
    {
        $access = app(SocialAccess::class);
        if ($request->is('api/v1/channels')) {
            $access->permission($actor, 'social.channels.manage');
        } elseif ($request->is('api/v1/posts')) {
            $access->channel($actor, (string) $request->input('channel_id'), true);
            $type = $request->input('post_type', 'update');
            if ($type !== 'update') {
                $access->permission($actor, 'social.'.$type.'.publish');
            }
        } elseif ($request->is('api/v1/admin/posts/*/moderation')) {
            $access->permission($actor, 'social.moderation.manage');
            $access->post($actor, $request->route('postId'), moderator: true);
        } elseif ($request->is('api/v1/posts/*/comments') || $request->is('api/v1/posts/*/reports')) {
            $post = $access->post($actor, $request->route('postId'));
            if ($request->is('api/v1/posts/*/comments')) {
                $access->channel($actor, $post->channel_id, true);
            }
        }
    }
}
