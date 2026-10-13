<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocialAccess
{
    public function actor(Request $request): array
    {
        return $request->attributes->get('social_actor');
    }

    public function permission(array $actor, string $permission): void
    {
        abort_unless(in_array($permission, $actor['permissions'], true), 403, 'Permission required.');
    }

    public function channels(array $actor): Builder
    {
        return DB::table('social_channels as c')->where('c.organization_id', $actor['organization_id'])
            ->where('c.status', 'active')->whereNull('c.deleted_at')
            ->where(function (Builder $q) use ($actor): void {
                $q->where('c.visibility', 'organization')->orWhereExists(function (Builder $m) use ($actor): void {
                    $m->selectRaw('1')->from('channel_members as m')->whereColumn('m.channel_id', 'c.id')
                        ->where('m.user_id', $actor['user_id'])->where('m.membership_status', 'active');
                });
            });
    }

    public function channel(array $actor, string $id, bool $write = false, bool $lock = false): object
    {
        $q = $this->channels($actor)->where('c.id', $id);
        $channel = ($lock ? $q->lockForUpdate() : $q)->first();
        abort_unless($channel, 404, 'Channel not found.');
        if ($write) {
            abort_unless(DB::table('channel_members')->where('channel_id', $id)->where('user_id', $actor['user_id'])
                ->where('membership_status', 'active')->exists(), 403, 'Active channel membership required.');
        }

        return $channel;
    }

    public function posts(array $actor): Builder
    {
        return DB::table('posts as p')->where('p.organization_id', $actor['organization_id'])->whereNull('p.deleted_at')
            ->whereIn('p.visibility', ['organization', 'channel'])
            ->whereIn('p.channel_id', $this->channels($actor)->select('c.id'));
    }

    public function post(array $actor, string $id, bool $owner = false, bool $moderator = false, bool $lock = false): object
    {
        $q = $this->posts($actor)->where('p.id', $id);
        if (! $owner && ! $moderator) {
            $q->where('p.publication_status', 'published')->where('p.moderation_status', 'clear');
        }
        $post = ($lock ? $q->lockForUpdate() : $q)->first();
        abort_unless($post, 404, 'Post not found.');
        if ($owner) {
            abort_unless($post->author_user_id === $actor['user_id'], 403, 'Only the author can change this post.');
            abort_unless(in_array($post->publication_status, ['draft', 'published'], true) && $post->moderation_status === 'clear', 409, 'Post is moderated.');
            $this->channel($actor, $post->channel_id, true);
        }

        return $post;
    }
}
