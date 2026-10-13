<?php

namespace Database\Seeders;

use App\Models\ChannelMember;
use App\Models\Post;
use App\Models\PostStat;
use App\Models\SocialCategory;
use App\Models\SocialChannel;
use App\Models\SocialTag;
use Illuminate\Database\Seeder;

class SocialDemoSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = '10000000-0000-4000-8000-000000000001';
        $adminUserId = '30000000-0000-4000-8000-000000000001';

        $channel = SocialChannel::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'code' => 'general'],
            ['id' => '60000000-0000-4000-8000-000000000001', 'name' => 'Thông báo chung', 'channel_type' => 'organization', 'visibility' => 'organization', 'membership_policy' => 'managed', 'owner_user_id' => $adminUserId, 'status' => 'active'],
        );

        ChannelMember::query()->updateOrCreate(
            ['channel_id' => $channel->id, 'user_id' => $adminUserId],
            ['member_role' => 'owner', 'membership_status' => 'active', 'joined_at' => now()],
        );

        SocialCategory::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'code' => 'news'],
            ['id' => '60000000-0000-4000-8000-000000000002', 'name' => 'Tin nội bộ', 'category_type' => 'news', 'status' => 'active'],
        );

        $post = Post::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'title' => 'Chào mừng đến Inside'],
            ['id' => '60000000-0000-4000-8000-000000000003', 'channel_id' => $channel->id, 'author_user_id' => $adminUserId, 'post_type' => 'announcement', 'body_markdown' => 'Bảng tin nội bộ đã sẵn sàng.', 'body_plaintext' => 'Bảng tin nội bộ đã sẵn sàng.', 'visibility' => 'organization', 'publication_status' => 'published', 'moderation_status' => 'clear', 'published_at' => now(), 'created_by_user_id' => $adminUserId],
        );

        PostStat::query()->updateOrCreate(['post_id' => $post->id], []);
        SocialTag::query()->updateOrCreate(['organization_id' => $organizationId, 'normalized_name' => 'welcome'], ['display_name' => 'Welcome', 'status' => 'active', 'created_by_user_id' => $adminUserId]);
    }
}
