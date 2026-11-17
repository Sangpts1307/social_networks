import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/date_time_formatter.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../core/widgets/empty_state.dart';
import '../../../data/models/chat_model.dart';
import '../view_model/chat_view_model.dart';
import 'chat_detail_screen.dart';
import 'create_group_dialog.dart';
import 'new_direct_chat_dialog.dart';

class ChatListView extends ConsumerStatefulWidget {
  const ChatListView({super.key});

  @override
  ConsumerState<ChatListView> createState() => _ChatListViewState();
}

class _ChatListViewState extends ConsumerState<ChatListView> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _openDirectDialog() {
    showDialog(
      context: context,
      builder: (_) => const NewDirectChatDialog(),
    );
  }

  void _openGroupDialog() {
    showDialog(
      context: context,
      builder: (_) => const CreateGroupDialog(),
    );
  }

  void _openChat(ConversationDto conv) {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => ChatDetailScreen(conversation: conv)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final chatState = ref.watch(chatViewModelProvider);
    final convs = chatState.filteredConversations;

    // Toast listener
    ref.listen(chatViewModelProvider.select((s) => s.toastMessage), (prev, next) {
      if (next != null && next.isNotEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(next), duration: const Duration(seconds: 2)),
        );
        ref.read(chatViewModelProvider.notifier).clearToast();
      }
    });

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Tin nhắn'),
        actions: [
          IconButton(
            icon: const Icon(Icons.edit_square, size: 24),
            tooltip: 'Chat mới',
            onPressed: _openDirectDialog,
          ),
          IconButton(
            icon: const Icon(Icons.group_add_outlined, size: 26),
            tooltip: 'Tạo nhóm',
            onPressed: _openGroupDialog,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(chatViewModelProvider.notifier).loadChatData(),
        child: Column(
          children: [
            // Search Input Bar
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: TextField(
                controller: _searchController,
                onChanged: (v) => ref.read(chatViewModelProvider.notifier).setSearchQuery(v),
                decoration: InputDecoration(
                  hintText: 'Tìm kiếm người hoặc tin nhắn...',
                  hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 14),
                  prefixIcon: const Icon(Icons.search, size: 20, color: AppColors.textSecondary),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 18),
                          onPressed: () {
                            _searchController.clear();
                            ref.read(chatViewModelProvider.notifier).setSearchQuery('');
                          },
                        )
                      : null,
                  filled: true,
                  fillColor: const Color(0xFFF1F5F9),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
            ),

            // Filter Tabs (Tất cả, Trực tiếp, Nhóm)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: Row(
                children: [
                  _buildTab('all', 'Tất cả', chatState.filter),
                  const SizedBox(width: 8),
                  _buildTab('direct', 'Trực tiếp', chatState.filter),
                  const SizedBox(width: 8),
                  _buildTab('group', 'Nhóm', chatState.filter),
                ],
              ),
            ),
            const Divider(height: 1),

            // Active Conversations List
            Expanded(
              child: chatState.isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : convs.isEmpty
                      ? EmptyState(
                          icon: Icons.chat_bubble_outline,
                          title: 'Không có tin nhắn nào',
                          subtitle: 'Bắt đầu kết nối với đồng đội ngay hôm nay!',
                          actionTitle: 'Chat mới',
                          onAction: _openDirectDialog,
                        )
                      : ListView.separated(
                          itemCount: convs.length,
                          separatorBuilder: (_, _) => const Divider(height: 1, indent: 72),
                          itemBuilder: (context, index) {
                            final c = convs[index];
                            final lastMsg = c.lastMessage;

                            return ListTile(
                              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                              leading: Stack(
                                children: [
                                  AppAvatar(
                                    name: c.title ?? 'Chat',
                                    size: 48,
                                  ),
                                  if (c.type == 'group') ...[
                                    Positioned(
                                      bottom: 0,
                                      right: 0,
                                      child: Container(
                                        padding: const EdgeInsets.all(2),
                                        decoration: const BoxDecoration(
                                          color: Colors.white,
                                          shape: BoxShape.circle,
                                        ),
                                        child: const Icon(
                                          Icons.group,
                                          size: 14,
                                          color: AppColors.primary,
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                              title: Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      c.title ?? 'Trò chuyện',
                                      style: TextStyle(
                                        fontWeight: c.unreadCount > 0 ? FontWeight.bold : FontWeight.w600,
                                        fontSize: 15,
                                        color: AppColors.textPrimary,
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  if (lastMsg != null) ...[
                                    Text(
                                      DateTimeFormatter.formatRelativeTime(lastMsg.sentAt),
                                      style: TextStyle(
                                        fontSize: 11,
                                        color: c.unreadCount > 0 ? AppColors.primary : AppColors.textMuted,
                                        fontWeight: c.unreadCount > 0 ? FontWeight.bold : FontWeight.normal,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                              subtitle: Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      lastMsg?.bodyPlaintext ?? 'Chưa có tin nhắn nào',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        fontSize: 13,
                                        color: c.unreadCount > 0 ? AppColors.textPrimary : AppColors.textSecondary,
                                        fontWeight: c.unreadCount > 0 ? FontWeight.bold : FontWeight.normal,
                                      ),
                                    ),
                                  ),
                                  if (c.unreadCount > 0) ...[
                                    const SizedBox(width: 6),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: AppColors.primary,
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                      child: Text(
                                        '${c.unreadCount}',
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 11,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                              onTap: () => _openChat(c),
                            );
                          },
                        ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTab(String key, String label, String activeKey) {
    final isActive = key == activeKey;
    return ChoiceChip(
      selected: isActive,
      label: Text(label),
      labelStyle: TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: isActive ? Colors.white : AppColors.textSecondary,
      ),
      selectedColor: AppColors.primary,
      backgroundColor: const Color(0xFFF1F5F9),
      showCheckmark: false,
      onSelected: (_) {
        ref.read(chatViewModelProvider.notifier).setFilter(key);
      },
    );
  }
}
