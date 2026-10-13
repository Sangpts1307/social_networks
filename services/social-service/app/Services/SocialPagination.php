<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SocialPagination
{
    public function page(Builder $query, Request $request, string $column, string $idColumn, string $scope): array
    {
        $data = $request->validate(['limit' => ['sometimes', 'integer', 'min:1', 'max:100'], 'cursor' => ['sometimes', 'string', 'max:2048']]);
        $limit = (int) ($data['limit'] ?? 20);
        $actor = $request->attributes->get('social_actor');
        $scope = hash('sha256', $scope.'|'.$actor['organization_id'].'|'.$actor['user_id']);
        if (isset($data['cursor'])) {
            try {
                $cursor = json_decode(Crypt::decryptString($data['cursor']), true, 8, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                abort(422, 'Invalid cursor.');
            }
            abort_unless(is_array($cursor) && ($cursor['scope'] ?? '') === $scope && is_string($cursor['time'] ?? null)
                && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d{1,6})?$/D', $cursor['time'])
                && Str::isUuid($cursor['id'] ?? ''), 422, 'Invalid cursor.');
            $query->where(function (Builder $q) use ($column, $idColumn, $cursor): void {
                $q->where($column, '<', $cursor['time'])->orWhere(function (Builder $q) use ($column, $idColumn, $cursor): void {
                    $q->where($column, $cursor['time'])->where($idColumn, '<', $cursor['id']);
                });
            });
        }
        $items = $query->orderByDesc($column)->orderByDesc($idColumn)->limit($limit + 1)->get();
        $hasMore = $items->count() > $limit;
        if ($hasMore) {
            $items->pop();
        }
        $last = $items->last();
        $timeField = substr($column, strrpos($column, '.') + 1);
        $next = $hasMore ? Crypt::encryptString(json_encode(['scope' => $scope, 'time' => $last->{$timeField}, 'id' => $last->id], JSON_THROW_ON_ERROR)) : null;

        return ['data' => $items->values(), 'meta' => ['next_cursor' => $next, 'has_more' => $hasMore, 'limit' => $limit]];
    }
}
