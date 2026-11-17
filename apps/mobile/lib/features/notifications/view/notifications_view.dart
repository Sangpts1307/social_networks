import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/date_time_formatter.dart';
import '../../../core/widgets/empty_state.dart';
import '../../../data/models/notification_model.dart';
import '../../../data/repositories/notification_repository.dart';
import '../../auth/view_model/auth_view_model.dart';

class NotificationsView extends ConsumerStatefulWidget {
  const NotificationsView({super.key});

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
    final isDemo = ref.read(authViewModelProvider).isDemo;
    final repo = ref.read(notificationRepositoryProvider);

    final result = await repo.getNotifications(isDemo: isDemo);
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(failure.message)),
        );
      },
    );
  }

  Future<void> _markRead(String id) async {
    final isDemo = ref.read(authViewModelProvider).isDemo;
    final repo = ref.read(notificationRepositoryProvider);

    await repo.markRead(id, isDemo: isDemo);
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
    final isDemo = ref.read(authViewModelProvider).isDemo;
    final repo = ref.read(notificationRepositoryProvider);

    await repo.markAllRead(isDemo: isDemo);
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

  IconData _iconForType(String type) {
    switch (type) {
      case 'announcement':
        return Icons.campaign_rounded;
      case 'mention':
        return Icons.alternate_email_rounded;
      case 'comment':
        return Icons.chat_bubble_outline_rounded;
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
        return const Color(0xFFEF4444);
      case 'mention':
        return AppColors.primary;
      case 'comment':
        return const Color(0xFF10B981);
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
                        color: n.isRead ? Colors.white : const Color(0xFFF0F7FF),
                        child: ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          leading: Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: iconColor.withValues(alpha: 0.1),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(icon, color: iconColor, size: 24),
                          ),
                          title: Text(
                            n.title,
                            style: TextStyle(
                              fontWeight: n.isRead ? FontWeight.w600 : FontWeight.bold,
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
                                style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                              ),
                            ],
                          ),
                          onTap: () {
                            if (!n.isRead) {
                              _markRead(n.id);
                            }
                          },
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}
