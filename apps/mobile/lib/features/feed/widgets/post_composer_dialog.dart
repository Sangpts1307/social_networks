import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../data/models/post_model.dart';
import '../../auth/view_model/auth_view_model.dart';
import '../view_model/feed_view_model.dart';

class PostComposerDialog extends ConsumerStatefulWidget {
  final PostModel? editingPost;
  final String? initialChannelId;

  const PostComposerDialog({
    super.key,
    this.editingPost,
    this.initialChannelId,
  });

  @override
  ConsumerState<PostComposerDialog> createState() => _PostComposerDialogState();
}

class _PostComposerDialogState extends ConsumerState<PostComposerDialog> {
  late TextEditingController _bodyController;
  late String _selectedChannelId;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _bodyController = TextEditingController(
      text: widget.editingPost?.bodyPlaintext ?? '',
    );
    _selectedChannelId =
        widget.editingPost?.channelId ?? widget.initialChannelId ?? 'general';
  }

  @override
  void dispose() {
    _bodyController.dispose();
    super.dispose();
  }

  void _handleSubmit() async {
    final body = _bodyController.text.trim();
    if (body.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Vui lòng nhập nội dung bài viết.')),
      );
      return;
    }

    final channels = ref.read(feedViewModelProvider).channels;
    if (widget.editingPost == null && channels.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Chưa tải được kênh. Vui lòng thử lại sau.'),
        ),
      );
      return;
    }

    final channelId =
        channels.isEmpty ||
            channels.any((channel) => channel.id == _selectedChannelId)
        ? _selectedChannelId
        : channels.first.id;
    setState(() => _submitting = true);

    if (widget.editingPost != null) {
      final ok = await ref
          .read(feedViewModelProvider.notifier)
          .updatePost(postId: widget.editingPost!.id, bodyMarkdown: body);
      if (mounted) {
        setState(() => _submitting = false);
        if (ok) Navigator.of(context).pop();
      }
    } else {
      final ok = await ref
          .read(feedViewModelProvider.notifier)
          .createPost(bodyMarkdown: body, channelId: channelId);
      if (mounted) {
        setState(() => _submitting = false);
        if (ok) Navigator.of(context).pop();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final authUser = ref.watch(authViewModelProvider).user;
    final channels = ref.watch(feedViewModelProvider).channels;

    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.editingPost != null ? 'Chỉnh sửa bài viết' : 'Tạo bài viết',
        ),
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => Navigator.of(context).pop(),
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: TextButton(
              onPressed: _submitting ? null : _handleSubmit,
              style: TextButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                ),
              ),
              child: _submitting
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                  : Text(
                      widget.editingPost != null ? 'Lưu' : 'Đăng',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
            ),
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // User info & Channel selector
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                children: [
                  AppAvatar(
                    name: authUser?.displayName ?? 'Tôi',
                    avatarUrl: authUser?.avatarUrl,
                    size: 44,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          authUser?.displayName ?? 'Bạn',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 15,
                          ),
                        ),
                        ...[
                          const SizedBox(height: 4),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF1F5F9),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: AppColors.border),
                            ),
                            child: DropdownButtonHideUnderline(
                              child: DropdownButton<String>(
                                value:
                                    channels.any(
                                      (c) => c.id == _selectedChannelId,
                                    )
                                    ? _selectedChannelId
                                    : (channels.isNotEmpty
                                          ? channels.first.id
                                          : 'general'),
                                isDense: true,
                                icon: const Icon(
                                  Icons.arrow_drop_down,
                                  size: 18,
                                ),
                                items: channels.map((c) {
                                  return DropdownMenuItem<String>(
                                    value: c.id,
                                    child: Text(
                                      '# ${c.name}',
                                      style: const TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  );
                                }).toList(),
                                onChanged: (v) {
                                  if (v != null) {
                                    setState(() => _selectedChannelId = v);
                                  }
                                },
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),

            // Text Input area
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: TextField(
                  controller: _bodyController,
                  maxLines: null,
                  autofocus: true,
                  style: const TextStyle(fontSize: 16, height: 1.4),
                  decoration: InputDecoration(
                    hintText: 'Chia sẻ điều bạn đang làm, đặt câu hỏi hoặc gửi lời cảm ơn đồng đội…',
                    hintStyle: const TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 16,
                    ),
                    border: InputBorder.none,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
