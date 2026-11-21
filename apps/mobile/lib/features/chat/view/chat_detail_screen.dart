import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/date_time_formatter.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../data/models/chat_model.dart';
import '../../auth/view_model/auth_view_model.dart';
import '../view_model/chat_view_model.dart';

class ChatDetailScreen extends ConsumerStatefulWidget {
  final ConversationDto conversation;

  const ChatDetailScreen({super.key, required this.conversation});

  @override
  ConsumerState<ChatDetailScreen> createState() => _ChatDetailScreenState();
}

class _ChatDetailScreenState extends ConsumerState<ChatDetailScreen> {
  final _messageController = TextEditingController();
  final _scrollController = ScrollController();
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref
          .read(chatViewModelProvider.notifier)
          .loadMessages(widget.conversation.id);
      ref.read(chatViewModelProvider.notifier).markRead(widget.conversation.id);
    });
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _sendMessage() async {
    final text = _messageController.text.trim();
    if (text.isEmpty) return;

    setState(() => _sending = true);
    final ok = await ref
        .read(chatViewModelProvider.notifier)
        .sendMessage(widget.conversation.id, text);
    if (mounted) {
      setState(() => _sending = false);
      if (ok) {
        _messageController.clear();
        _scrollToBottom();
      }
    }
  }

  void _scrollToBottom() {
    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        _scrollController.position.maxScrollExtent + 80,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentUserId = ref.watch(authViewModelProvider).user?.id ?? '';
    final chatState = ref.watch(chatViewModelProvider);
    final messages = chatState.messagesByConvId[widget.conversation.id] ?? [];
    final senderNames = {
      for (final user in chatState.directory) user.id: user.displayName,
    };
    final myName = ref.watch(authViewModelProvider).user?.displayName ?? 'Bạn';

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        titleSpacing: 0,
        title: Row(
          children: [
            AppAvatar(name: widget.conversation.title ?? 'Chat', size: 36),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.conversation.title ?? 'Tin nhắn',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    widget.conversation.type == 'group'
                        ? '${widget.conversation.memberCount} thành viên'
                        : 'Đang hoạt động',
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFF10B981),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.call_outlined, color: AppColors.primary),
            onPressed: () {},
          ),
          IconButton(
            icon: const Icon(Icons.videocam_outlined, color: AppColors.primary),
            onPressed: () {},
          ),
          IconButton(
            icon: const Icon(
              Icons.info_outline,
              color: AppColors.textSecondary,
            ),
            onPressed: () {},
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Message List
            Expanded(
              child: messages.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          AppAvatar(
                            name: widget.conversation.title ?? 'Chat',
                            size: 64,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            widget.conversation.title ?? 'Trò chuyện',
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 6),
                          const Text(
                            'Gửi tin nhắn để bắt đầu cuộc trò chuyện!',
                            style: TextStyle(
                              color: AppColors.textSecondary,
                              fontSize: 13,
                            ),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 16,
                      ),
                      itemCount: messages.length,
                      itemBuilder: (context, index) {
                        final msg = messages[index];
                        final isMe = msg.senderUserId == currentUserId;

                        final senderName = isMe
                            ? myName
                            : (senderNames[msg.senderUserId] ?? 'Thành viên');
                        return Align(
                          alignment: isMe
                              ? Alignment.centerRight
                              : Alignment.centerLeft,
                          child: Column(
                            crossAxisAlignment: isMe
                                ? CrossAxisAlignment.end
                                : CrossAxisAlignment.start,
                            children: [
                              if (widget.conversation.type == 'group')
                                Padding(
                                  padding: const EdgeInsets.only(bottom: 4),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      AppAvatar(name: senderName, size: 24),
                                      const SizedBox(width: 6),
                                      Text(
                                        senderName,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w600,
                                          color: AppColors.textSecondary,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              Container(
                                margin: const EdgeInsets.only(bottom: 10),
                                constraints: BoxConstraints(
                                  maxWidth:
                                      MediaQuery.of(context).size.width * 0.75,
                                ),
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 14,
                                  vertical: 10,
                                ),
                                decoration: BoxDecoration(
                                  color: isMe
                                      ? AppColors.primary
                                      : Colors.white,
                                  borderRadius: BorderRadius.only(
                                    topLeft: const Radius.circular(16),
                                    topRight: const Radius.circular(16),
                                    bottomLeft: Radius.circular(isMe ? 16 : 4),
                                    bottomRight: Radius.circular(isMe ? 4 : 16),
                                  ),
                                  boxShadow: const [
                                    BoxShadow(
                                      color: Color(0x0A000000),
                                      blurRadius: 4,
                                      offset: Offset(0, 1),
                                    ),
                                  ],
                                ),
                                child: Column(
                                  crossAxisAlignment: isMe
                                      ? CrossAxisAlignment.end
                                      : CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      msg.bodyPlaintext,
                                      style: TextStyle(
                                        color: isMe
                                            ? Colors.white
                                            : AppColors.textPrimary,
                                        fontSize: 15,
                                        height: 1.35,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      DateTimeFormatter.formatRelativeTime(
                                        msg.sentAt,
                                      ),
                                      style: TextStyle(
                                        color: isMe
                                            ? Colors.white70
                                            : AppColors.textMuted,
                                        fontSize: 10,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
            ),

            // Message Composer Input Box
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border(
                  top: BorderSide(color: AppColors.border, width: 0.5),
                ),
              ),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(
                      Icons.add_circle_outline,
                      color: AppColors.primary,
                    ),
                    onPressed: () {},
                  ),
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: TextField(
                        controller: _messageController,
                        decoration: const InputDecoration(
                          hintText: 'Nhập tin nhắn…',
                          hintStyle: TextStyle(
                            color: AppColors.textMuted,
                            fontSize: 14,
                          ),
                          border: InputBorder.none,
                          isDense: true,
                          contentPadding: EdgeInsets.symmetric(vertical: 10),
                        ),
                        maxLines: null,
                        textInputAction: TextInputAction.send,
                        onSubmitted: (_) => _sendMessage(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 6),
                  _sending
                      ? const SizedBox(
                          width: 28,
                          height: 28,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : IconButton(
                          icon: const Icon(
                            Icons.send_rounded,
                            color: AppColors.primary,
                          ),
                          onPressed: _sendMessage,
                        ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
