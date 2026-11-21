import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/date_time_formatter.dart';
import '../../../core/widgets/empty_state.dart';
import '../../../data/models/notification_model.dart';
import '../../../data/repositories/notification_repository.dart';
import '../../../data/repositories/post_repository.dart';
import '../../feed/widgets/post_card.dart';
import '../../profile/view/profile_screen.dart';

class NotificationsView extends ConsumerStatefulWidget {
  final ValueChanged<String>? onOpenChannel;
  const NotificationsView({super.key, this.onOpenChannel});

  @override
  ConsumerState<NotificationsView> createState() => _NotificationsViewState();
}

class _NotificationsViewState extends ConsumerState<NotificationsView> {
  bool _isLoading = true;
  List<NotificationModel> _notifications = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadNotifications());
  }

  Future<void> _loadNotifications() async {
    setState(() => _isLoading = true);
    final repo = ref.read(notificationRepositoryProvider);

    final result = await repo.getNotifications();
    if (!mounted) return;

    result.when(
      success: (page) {
        setState(() {
          _notifications = page.data;
          _isLoading = false;
        });
      },
      failure: (failure) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(failure.message)));
      },
    );
  }

  Future<void> _markRead(String id) async {
    final repo = ref.read(notificationRepositoryProvider);

    final result = await repo.markRead(id);
    if (!mounted) return;
    final failure = result.failureOrNull;
    if (failure != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(failure.message)));
      return;
    }
    setState(() {
      final index = _notifications.indexWhere((n) => n.id == id);
      if (index != -1) {
        _notifications[index] = _notifications[index].copyWith(
          readAt: DateTime.now().toIso8601String(),
        );
      }
    });
  }

  Future<void> _markAllRead() async {
    final repo = ref.read(notificationRepositoryProvider);

    final result = await repo.markAllRead();
    if (!mounted) return;
    final failure = result.failureOrNull;
    if (failure != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(failure.message)));
      return;
    }
    setState(() {
      _notifications = _notifications
          .map((n) => n.copyWith(readAt: DateTime.now().toIso8601String()))
          .toList();
    });

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Đã đánh dấu tất cả là đã đọc.')),
      );
    }
  }

  Future<void> _openNotification(NotificationModel notification) async {
    if (!notification.isRead) _markRead(notification.id);
    final sourceId = notification.sourceId;
    if (notification.sourceType == 'channel' && sourceId != null) {
      widget.onOpenChannel?.call(sourceId);
      return;
    }
    if (notification.sourceType == 'user' ||
        notification.notificationType == 'profile_update_request') {
      Navigator.of(context)
          .push(MaterialPageRoute(builder: (_) => const ProfileScreen()));
      return;
    }
    if (notification.sourceType != 'post' || sourceId == null) return;
    final result = await ref.read(postRepositoryProvider).getPost(sourceId);
    if (!mounted) return;
    result.when(
      success: (post) {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => Scaffold(
              appBar: AppBar(title: const Text('Bài viết')),
              body: ListView(children: [PostCard(post: post)]),
            ),
          ),
        );
      },
      failure: (failure) =>
          ScaffoldMessenger.of(context)
              .showSnackBar(SnackBar(content: Text(failure.message))),
    );
  }

  IconData _iconForType(String type) {
    switch (type) {
      case 'announcement':
        return Icons.campaign_rounded;
      case 'post_like':
        return Icons.thumb_up_rounded;
      case 'post_save':
        return Icons.bookmark_rounded;
      case 'post_share':
        return Icons.share_rounded;
      case 'profile_update_request':
        return Icons.badge_rounded;
      case 'mention':
        return Icons.alternate_email_rounded;
      case 'comment':
        return Icons.chat_bubble_rounded;
      case 'answer':
        return Icons.school_rounded;
      case 'document_approved':
        return Icons.verified_outlined;
      default:
        return Icons.notifications_outlined;
    }
  }

  Color _colorForType(String type) {
    switch (type) {
      case 'announcement':
        return const Color(0xFFEA580C);
      case 'post_like':
        return const Color(0xFF2563EB);
      case 'post_save':
        return const Color(0xFF7C3AED);
      case 'post_share':
        return const Color(0xFF0E7490);
      case 'profile_update_request':
        return const Color(0xFF4F46E5);
      case 'mention':
        return AppColors.primary;
      case 'comment':
        return const Color(0xFF059669);
      case 'answer':
        return const Color(0xFFF59E0B);
      case 'document_approved':
        return const Color(0xFF8B5CF6);
      default:
        return AppColors.primary;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Thông báo'),
        actions: [
          IconButton(
            icon: const Icon(Icons.done_all, size: 22),
            tooltip: 'Đánh dấu đã đọc',
            onPressed: _markAllRead,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _notifications.isEmpty
          ? const EmptyState(
              icon: Icons.notifications_none_rounded,
              title: 'Chưa có thông báo nào',
              subtitle: 'Các cập nhật hoạt động, phản hồi và tin tức sẽ xuất hiện tại đây.',
            )
          : RefreshIndicator(
              onRefresh: _loadNotifications,
              child: ListView.separated(
                itemCount: _notifications.length,
                separatorBuilder: (_, _) => const Divider(height: 1),
                itemBuilder: (context, index) {
                  final n = _notifications[index];
                  final icon = _iconForType(n.notificationType);
                  final iconColor = _colorForType(n.notificationType);

                  return Container(
                    color: n.isRead ? Colors.white : const Color(0xFFF8FAFC),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                      leading: Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: iconColor,
                          shape: BoxShape.circle,
                        ),
                        child: Icon(icon, color: Colors.white, size: 24),
                      ),
                      title: Text(
                        n.title,
                        style: TextStyle(
                          fontWeight: n.isRead
                              ? FontWeight.w600
                              : FontWeight.bold,
                          fontSize: 14,
                          color: AppColors.textPrimary,
                        ),
                      ),
                      subtitle: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SizedBox(height: 4),
                          Text(
                            n.body,
                            style: const TextStyle(
                              fontSize: 13,
                              color: AppColors.textSecondary,
                              height: 1.3,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            DateTimeFormatter.formatRelativeTime(n.createdAt),
                            style: const TextStyle(
                              fontSize: 11,
                              color: AppColors.textMuted,
                            ),
                          ),
                        ],
                      ),
                      trailing: n.isRead
                          ? null
                          : const Icon(
                              Icons.circle,
                              color: Color(0xFF2563EB),
                              size: 9,
                            ),
                      onTap: () => _openNotification(n),
                    ),
                  );
                },
              ),
            ),
    );
  }
}
