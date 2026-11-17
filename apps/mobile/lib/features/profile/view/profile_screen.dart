import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../core/widgets/app_button.dart';
import '../../auth/view_model/auth_view_model.dart';
import '../../feed/view_model/feed_view_model.dart';
import '../../feed/widgets/post_card.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authViewModelProvider).user;
    final allPosts = ref.watch(feedViewModelProvider).posts;
    final userPosts = allPosts.where((p) => p.authorUserId == user?.id).toList();

    return Scaffold(
      backgroundColor: AppColors.scaffoldBackground,
      appBar: AppBar(
        title: const Text('Trang cá nhân'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Đăng xuất',
            onPressed: () => ref.read(authViewModelProvider.notifier).logout(),
          ),
        ],
      ),
      body: ListView(
        children: [
          // Profile Header Card
          Container(
            color: Colors.white,
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                AppAvatar(
                  name: user?.displayName ?? 'User',
                  avatarUrl: user?.avatarUrl,
                  size: 80,
                ),
                const SizedBox(height: 12),
                Text(
                  user?.displayName ?? 'Thành viên',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  user?.role ?? user?.email ?? '',
                  style: const TextStyle(
                    fontSize: 14,
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 16),

                // Stats row
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    _buildStat('${userPosts.length}', 'Bài viết'),
                    const SizedBox(width: 32),
                    _buildStat('28', 'Người theo dõi'),
                    const SizedBox(width: 32),
                    _buildStat('14', 'Đang theo dõi'),
                  ],
                ),
                const SizedBox(height: 16),
                AppButton(
                  title: 'Đăng xuất',
                  variant: ButtonVariant.secondary,
                  height: 40,
                  onPressed: () => ref.read(authViewModelProvider.notifier).logout(),
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),

          // User posts section header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Text(
              'Bài viết của tôi (${userPosts.length})',
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.bold,
                color: AppColors.textSecondary,
              ),
            ),
          ),

          if (userPosts.isEmpty) ...[
            Container(
              color: Colors.white,
              padding: const EdgeInsets.all(32),
              alignment: Alignment.center,
              child: const Text(
                'Bạn chưa đăng bài viết nào.',
                style: TextStyle(color: AppColors.textMuted),
              ),
            ),
          ] else ...[
            ...userPosts.map((p) => PostCard(post: p)),
          ],
        ],
      ),
    );
  }

  Widget _buildStat(String count, String label) {
    return Column(
      children: [
        Text(
          count,
          style: const TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
        ),
      ],
    );
  }
}
