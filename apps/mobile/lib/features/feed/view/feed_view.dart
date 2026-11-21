import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../core/widgets/empty_state.dart';
import '../../auth/view_model/auth_view_model.dart';
import '../../search/view/search_screen.dart';
import '../view_model/feed_view_model.dart';
import '../widgets/post_card.dart';
import '../widgets/post_composer_dialog.dart';

class FeedView extends ConsumerStatefulWidget {
  const FeedView({super.key});

  @override
  ConsumerState<FeedView> createState() => _FeedViewState();
}

class _FeedViewState extends ConsumerState<FeedView> {
  void _openComposer() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => const PostComposerDialog(),
      ),
    );
  }

  void _openSearch() {
    Navigator.of(context)
        .push(MaterialPageRoute(builder: (_) => const SearchScreen()));
  }

  @override
  Widget build(BuildContext context) {
    final authUser = ref.watch(authViewModelProvider).user;
    final feedState = ref.watch(feedViewModelProvider);

    // Toast listener
    ref.listen(feedViewModelProvider.select((s) => s.toastMessage), (
      prev,
      next,
    ) {
      if (next != null && next.isNotEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(next),
            duration: const Duration(seconds: 3),
            behavior: SnackBarBehavior.floating,
          ),
        );
        ref.read(feedViewModelProvider.notifier).clearToast();
      }
    });

    return Scaffold(
      backgroundColor: AppColors.scaffoldBackground,
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.public, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 8),
            const Flexible(
              child: Text(
                'Openhello',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: AppColors.primary,
                  fontWeight: FontWeight.bold,
                  fontSize: 22,
                  letterSpacing: -0.5,
                ),
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.search, size: 26),
            tooltip: 'Tìm kiếm',
            onPressed: _openSearch,
          ),
          IconButton(
            icon: const Icon(Icons.add_circle_outline, size: 26),
            tooltip: 'Đăng bài',
            onPressed: () => _openComposer(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(feedViewModelProvider.notifier).refresh(),
        child: CustomScrollView(
          slivers: [
            // Quick Composer Bar
            SliverToBoxAdapter(
              child: Container(
                color: Colors.white,
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                child: Row(
                  children: [
                    AppAvatar(
                      name: authUser?.displayName ?? 'User',
                      avatarUrl: authUser?.avatarUrl,
                      size: 40,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: InkWell(
                        borderRadius: BorderRadius.circular(20),
                        onTap: () => _openComposer(),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 10,
                          ),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: const Text(
                            'Bạn đang nghĩ gì thế?',
                            style: TextStyle(
                              color: AppColors.textMuted,
                              fontSize: 14,
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton(
                      icon: const Icon(
                        Icons.photo_library_outlined,
                        color: Color(0xFF10B981),
                      ),
                      tooltip: 'Ảnh',
                      onPressed: () => _openComposer(),
                    ),
                  ],
                ),
              ),
            ),

            const SliverToBoxAdapter(child: SizedBox(height: 8)),

            // Channels Filter Chips
            if (feedState.channels.isNotEmpty) ...[
              SliverToBoxAdapter(
                child: Container(
                  color: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Row(
                      children: [
                        FilterChip(
                          selected: feedState.selectedChannelId == null,
                          label: const Text('Tất cả'),
                          labelStyle: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: feedState.selectedChannelId == null
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                          selectedColor: AppColors.primary,
                          backgroundColor: const Color(0xFFF1F5F9),
                          checkmarkColor: Colors.white,
                          onSelected: (_) {
                            ref
                                .read(feedViewModelProvider.notifier)
                                .selectChannel(null);
                          },
                        ),
                        const SizedBox(width: 8),
                        ...feedState.channels.map((ch) {
                          final isSelected =
                              feedState.selectedChannelId == ch.id;
                          return Padding(
                            padding: const EdgeInsets.only(right: 8),
                            child: FilterChip(
                              selected: isSelected,
                              label: Text('# ${ch.name}'),
                              labelStyle: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: isSelected
                                    ? Colors.white
                                    : AppColors.textPrimary,
                              ),
                              selectedColor: AppColors.primary,
                              backgroundColor: const Color(0xFFF1F5F9),
                              checkmarkColor: Colors.white,
                              onSelected: (_) {
                                ref
                                    .read(feedViewModelProvider.notifier)
                                    .selectChannel(isSelected ? null : ch.id);
                              },
                            ),
                          );
                        }),
                      ],
                    ),
                  ),
                ),
              ),
              const SliverToBoxAdapter(child: SizedBox(height: 8)),
            ],

            // Posts List or Loading / Empty state
            if (feedState.isLoading) ...[
              const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
            ] else if (feedState.posts.isEmpty) ...[
              SliverFillRemaining(
                child: EmptyState(
                  icon: Icons.article_outlined,
                  title: 'Chưa có bài viết nào',
                  subtitle: 'Hãy là người đầu tiên chia sẻ thông tin hoặc kết nối với đồng đội!',
                  actionTitle: 'Tạo bài viết',
                  onAction: () => _openComposer(),
                ),
              ),
            ] else ...[
              SliverList(
                delegate: SliverChildBuilderDelegate((context, index) {
                  final post = feedState.posts[index];
                  return PostCard(post: post);
                }, childCount: feedState.posts.length),
              ),
              const SliverToBoxAdapter(child: SizedBox(height: 24)),
            ],
          ],
        ),
      ),
    );
  }
}
