import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/date_time_formatter.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../data/models/post_model.dart';
import '../../auth/view_model/auth_view_model.dart';
import '../view_model/feed_view_model.dart';
import 'comments_bottom_sheet.dart';
import 'post_composer_dialog.dart';
import 'reaction_picker.dart';

class PostCard extends ConsumerStatefulWidget {
  final PostModel post;
  final VoidCallback? onTap;

  const PostCard({super.key, required this.post, this.onTap});

  @override
  ConsumerState<PostCard> createState() => _PostCardState();
}

class _PostCardState extends ConsumerState<PostCard> {
  bool _showReactions = false;

  Color _getBadgeColor(String type) {
    switch (type) {
      case 'announcement':
        return const Color(0xFFEF4444);
      case 'news':
        return const Color(0xFF10B981);
      default:
        return AppColors.primary;
    }
  }

  String _getBadgeLabel(String type) {
    switch (type) {
      case 'announcement':
        return 'Thông báo';
      case 'news':
        return 'Tin tức';
      default:
        return 'Cập nhật';
    }
  }

  String _getReactionEmoji(String? type) {
    switch (type) {
      case 'love':
        return '❤️';
      case 'sad':
        return '😢';
      case 'angry':
        return '😡';
      default:
        return '👍';
    }
  }

  Color _getReactionColor(String? type) {
    switch (type) {
      case 'love':
        return AppColors.reactionLove;
      case 'sad':
        return AppColors.reactionSad;
      case 'angry':
        return AppColors.reactionAngry;
      default:
        return AppColors.reactionLike;
    }
  }

  void _openComments() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => CommentsBottomSheet(post: widget.post),
    );
  }

  void _editPost() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PostComposerDialog(editingPost: widget.post),
      ),
    );
  }

  void _deletePost() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Xác nhận xóa'),
        content: const Text('Bạn có chắc chắn muốn xóa bài viết này không?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Hủy'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(ctx).pop();
              ref.read(feedViewModelProvider.notifier).deletePost(widget.post.id);
            },
            child: const Text('Xóa', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final post = widget.post;
    final currentUserId = ref.watch(authViewModelProvider).user?.id;
    final isAuthor = post.authorUserId == currentUserId || currentUserId == 'demo-user';
    final hasImage = (post.demoImageUrl != null && post.demoImageUrl!.isNotEmpty) ||
        (post.media.isNotEmpty && post.media.first.uri.isNotEmpty);
    final imageUrl = post.demoImageUrl ?? (post.media.isNotEmpty ? post.media.first.uri : null);

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: const BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Color(0x08000000),
            blurRadius: 4,
            offset: Offset(0, 1),
          ),
        ],
      ),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          InkWell(
            onTap: widget.onTap,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Author Header Row
                  Row(
                    children: [
                      AppAvatar(
                        name: post.authorName ?? 'Member',
                        avatarUrl: post.authorAvatar,
                        size: 42,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Flexible(
                                  child: Text(
                                    post.authorName ?? 'Đồng nghiệp',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 15,
                                      color: AppColors.textPrimary,
                                    ),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                                const SizedBox(width: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: _getBadgeColor(post.postType).withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: Text(
                                    _getBadgeLabel(post.postType),
                                    style: TextStyle(
                                      color: _getBadgeColor(post.postType),
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                Text(
                                  DateTimeFormatter.formatRelativeTime(post.publishedAt),
                                  style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                                ),
                                if (post.channelId.isNotEmpty) ...[
                                  const Text(' · ', style: TextStyle(color: AppColors.textMuted)),
                                  Text(
                                    '#${post.channelId}',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: AppColors.primary,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ],
                        ),
                      ),

                      // More options Menu
                      PopupMenuButton<String>(
                        icon: const Icon(Icons.more_horiz, color: AppColors.textSecondary),
                        padding: EdgeInsets.zero,
                        onSelected: (v) {
                          if (v == 'save') {
                            ref.read(feedViewModelProvider.notifier).toggleSave(post);
                          } else if (v == 'edit') {
                            _editPost();
                          } else if (v == 'delete') {
                            _deletePost();
                          }
                        },
                        itemBuilder: (context) => [
                          PopupMenuItem(
                            value: 'save',
                            child: Row(
                              children: [
                                Icon(
                                  post.isSaved ? Icons.bookmark : Icons.bookmark_border,
                                  size: 18,
                                  color: AppColors.textPrimary,
                                ),
                                const SizedBox(width: 8),
                                Text(post.isSaved ? 'Bỏ lưu bài viết' : 'Lưu bài viết'),
                              ],
                            ),
                          ),
                          if (isAuthor) ...[
                            const PopupMenuItem(
                              value: 'edit',
                              child: Row(
                                children: [
                                  Icon(Icons.edit_outlined, size: 18, color: AppColors.textPrimary),
                                  SizedBox(width: 8),
                                  Text('Chỉnh sửa'),
                                ],
                              ),
                            ),
                            const PopupMenuItem(
                              value: 'delete',
                              child: Row(
                                children: [
                                  Icon(Icons.delete_outline, size: 18, color: Colors.red),
                                  SizedBox(width: 8),
                                  Text('Xóa bài viết', style: TextStyle(color: Colors.red)),
                                ],
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),

                  // Post Title
                  if (post.title != null && post.title!.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Text(
                      post.title!,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ],

                  // Post Body
                  if (post.bodyPlaintext.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      post.bodyPlaintext,
                      style: const TextStyle(
                        fontSize: 15,
                        color: AppColors.textPrimary,
                        height: 1.45,
                      ),
                    ),
                  ],

                  // Media Image Attachment
                  if (hasImage && imageUrl != null) ...[
                    const SizedBox(height: 12),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: Image.network(
                        imageUrl,
                        width: double.infinity,
                        height: 220,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) => const SizedBox.shrink(),
                      ),
                    ),
                  ],

                  // Reactions & Comments counts summary
                  if (post.reactionCount > 0 || post.commentCount > 0) ...[
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        if (post.reactionCount > 0)
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(3),
                                decoration: const BoxDecoration(
                                  color: AppColors.primary,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.thumb_up, size: 10, color: Colors.white),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                '${post.reactionCount}',
                                style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                              ),
                            ],
                          )
                        else
                          const SizedBox.shrink(),
                        if (post.commentCount > 0)
                          Text(
                            '${post.commentCount} bình luận',
                            style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                          ),
                      ],
                    ),
                  ],

                  const SizedBox(height: 10),
                  const Divider(height: 1),
                  const SizedBox(height: 4),

                  // Action Buttons Row
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      // Like / React Button
                      InkWell(
                        borderRadius: BorderRadius.circular(8),
                        onTap: () {
                          ref.read(feedViewModelProvider.notifier).react(
                                post,
                                post.myReaction != null ? null : 'like',
                              );
                        },
                        onLongPress: () {
                          setState(() => _showReactions = !_showReactions);
                        },
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          child: Row(
                            children: [
                              Text(
                                post.myReaction != null ? _getReactionEmoji(post.myReaction) : '👍',
                                style: const TextStyle(fontSize: 16),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                post.myReaction != null ? 'Đã thích' : 'Thích',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: post.myReaction != null
                                      ? _getReactionColor(post.myReaction)
                                      : AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),

                      // Comment Button
                      InkWell(
                        borderRadius: BorderRadius.circular(8),
                        onTap: _openComments,
                        child: const Padding(
                          padding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          child: Row(
                            children: [
                              Icon(Icons.chat_bubble_outline, size: 18, color: AppColors.textSecondary),
                              SizedBox(width: 6),
                              Text(
                                'Bình luận',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),

                      // Save Button
                      InkWell(
                        borderRadius: BorderRadius.circular(8),
                        onTap: () {
                          ref.read(feedViewModelProvider.notifier).toggleSave(post);
                        },
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          child: Row(
                            children: [
                              Icon(
                                post.isSaved ? Icons.bookmark : Icons.bookmark_border,
                                size: 18,
                                color: post.isSaved ? AppColors.primary : AppColors.textSecondary,
                              ),
                              const SizedBox(width: 6),
                              Text(
                                post.isSaved ? 'Đã lưu' : 'Lưu',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: post.isSaved ? AppColors.primary : AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          // Floating Reaction Picker
          if (_showReactions) ...[
            Positioned(
              bottom: 48,
              left: 16,
              child: ReactionPicker(
                onSelected: (reaction) {
                  setState(() => _showReactions = false);
                  ref.read(feedViewModelProvider.notifier).react(post, reaction);
                },
              ),
            ),
          ],
        ],
      ),
    );
  }
}
