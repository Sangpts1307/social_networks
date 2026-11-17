import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../data/models/media_attachment_model.dart';
import '../../../data/models/post_model.dart';
import '../../auth/view_model/auth_view_model.dart';
import '../view_model/feed_view_model.dart';

class PostComposerDialog extends ConsumerStatefulWidget {
  final PostModel? editingPost;
  final String? initialChannelId;
  final bool initialIsStory;

  const PostComposerDialog({
    super.key,
    this.editingPost,
    this.initialChannelId,
    this.initialIsStory = false,
  });

  @override
  ConsumerState<PostComposerDialog> createState() => _PostComposerDialogState();
}

class _PostComposerDialogState extends ConsumerState<PostComposerDialog> {
  late bool _isStory;
  late TextEditingController _bodyController;
  late String _selectedChannelId;
  String _storyBg = '#1877F2';
  final List<MediaAttachment> _attachments = [];
  bool _submitting = false;

  static const List<String> _bgColors = [
    '#1877F2',
    '#7C3AED',
    '#D97706',
    '#059669',
    '#DB2777',
  ];

  @override
  void initState() {
    super.initState();
    _isStory = widget.initialIsStory;
    _bodyController = TextEditingController(text: widget.editingPost?.bodyPlaintext ?? '');
    _selectedChannelId = widget.editingPost?.channelId ?? widget.initialChannelId ?? 'general';
    if (widget.editingPost?.media != null) {
      _attachments.addAll(widget.editingPost!.media);
    }
  }

  @override
  void dispose() {
    _bodyController.dispose();
    super.dispose();
  }

  Future<void> _pickMedia() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.gallery);
    if (picked != null) {
      setState(() {
        _attachments.add(
          MediaAttachment(
            id: 'media-${DateTime.now().millisecondsSinceEpoch}',
            uri: picked.path,
            type: 'image',
            fileName: picked.name,
          ),
        );
      });
    }
  }

  void _handleSubmit() async {
    final body = _bodyController.text.trim();
    if (body.isEmpty && _attachments.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Vui lòng nhập nội dung bài viết hoặc đính kèm ảnh.')),
      );
      return;
    }

    setState(() => _submitting = true);

    if (_isStory) {
      ref.read(feedViewModelProvider.notifier).publishStory(
            body: body,
            background: _storyBg,
            media: _attachments,
          );
      if (mounted) Navigator.of(context).pop();
      return;
    }

    if (widget.editingPost != null) {
      final ok = await ref.read(feedViewModelProvider.notifier).updatePost(
            postId: widget.editingPost!.id,
            bodyMarkdown: body,
          );
      if (mounted) {
        setState(() => _submitting = false);
        if (ok) Navigator.of(context).pop();
      }
    } else {
      final ok = await ref.read(feedViewModelProvider.notifier).createPost(
            bodyMarkdown: body,
            channelId: _selectedChannelId,
            media: _attachments,
          );
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
        title: Text(widget.editingPost != null ? 'Chỉnh sửa bài viết' : (_isStory ? 'Tạo tin mới' : 'Tạo bài viết')),
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
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              ),
              child: _submitting
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                    )
                  : Text(widget.editingPost != null ? 'Lưu' : 'Đăng', style: const TextStyle(fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Mode toggle (Bài viết / Tin) if creating new
            if (widget.editingPost == null) ...[
              Container(
                margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: InkWell(
                        borderRadius: BorderRadius.circular(8),
                        onTap: () => setState(() => _isStory = false),
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          decoration: BoxDecoration(
                            color: !_isStory ? Colors.white : Colors.transparent,
                            borderRadius: BorderRadius.circular(8),
                            boxShadow: !_isStory
                                ? const [BoxShadow(color: Color(0x14000000), blurRadius: 4)]
                                : null,
                          ),
                          alignment: Alignment.center,
                          child: Text(
                            'Bài viết',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                              color: !_isStory ? AppColors.primary : AppColors.textSecondary,
                            ),
                          ),
                        ),
                      ),
                    ),
                    Expanded(
                      child: InkWell(
                        borderRadius: BorderRadius.circular(8),
                        onTap: () => setState(() => _isStory = true),
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          decoration: BoxDecoration(
                            color: _isStory ? Colors.white : Colors.transparent,
                            borderRadius: BorderRadius.circular(8),
                            boxShadow: _isStory
                                ? const [BoxShadow(color: Color(0x14000000), blurRadius: 4)]
                                : null,
                          ),
                          alignment: Alignment.center,
                          child: Text(
                            'Tin (24h)',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                              color: _isStory ? AppColors.primary : AppColors.textSecondary,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],

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
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                        ),
                        if (!_isStory) ...[
                          const SizedBox(height: 4),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF1F5F9),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: AppColors.border),
                            ),
                            child: DropdownButtonHideUnderline(
                              child: DropdownButton<String>(
                                value: channels.any((c) => c.id == _selectedChannelId)
                                    ? _selectedChannelId
                                    : (channels.isNotEmpty ? channels.first.id : 'general'),
                                isDense: true,
                                icon: const Icon(Icons.arrow_drop_down, size: 18),
                                items: channels.map((c) {
                                  return DropdownMenuItem<String>(
                                    value: c.id,
                                    child: Text(
                                      '# ${c.name}',
                                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                                    ),
                                  );
                                }).toList(),
                                onChanged: (v) {
                                  if (v != null) setState(() => _selectedChannelId = v);
                                },
                              ),
                            ),
                          ),
                        ] else ...[
                          const SizedBox(height: 2),
                          const Text(
                            'Công khai trong nội bộ công ty · 24 giờ',
                            style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
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
                    hintText: _isStory
                        ? 'Bạn đang nghĩ gì về ngày hôm nay?'
                        : 'Chia sẻ điều bạn đang làm, đặt câu hỏi hoặc gửi lời cảm ơn đồng đội…',
                    hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 16),
                    border: InputBorder.none,
                  ),
                ),
              ),
            ),

            // Color palette selector for Story
            if (_isStory) ...[
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Row(
                  children: [
                    const Text('Màu nền: ', style: TextStyle(fontSize: 13, color: AppColors.textSecondary)),
                    const SizedBox(width: 8),
                    ..._bgColors.map((colorHex) {
                      final c = Color(int.parse(colorHex.replaceFirst('#', '0xFF')));
                      final isSelected = _storyBg == colorHex;
                      return GestureDetector(
                        onTap: () => setState(() => _storyBg = colorHex),
                        child: Container(
                          margin: const EdgeInsets.symmetric(horizontal: 4),
                          width: 28,
                          height: 28,
                          decoration: BoxDecoration(
                            color: c,
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: isSelected ? Colors.black : Colors.white,
                              width: isSelected ? 2.5 : 1,
                            ),
                          ),
                        ),
                      );
                    }),
                  ],
                ),
              ),
            ],

            // Attachments Preview
            if (_attachments.isNotEmpty) ...[
              Container(
                height: 100,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: _attachments.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 8),
                  itemBuilder: (context, i) {
                    return Stack(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Container(
                            width: 84,
                            height: 84,
                            color: const Color(0xFFF1F5F9),
                            child: const Center(
                              child: Icon(Icons.image, color: AppColors.textSecondary, size: 36),
                            ),
                          ),
                        ),
                        Positioned(
                          top: 4,
                          right: 4,
                          child: GestureDetector(
                            onTap: () => setState(() => _attachments.removeAt(i)),
                            child: Container(
                              padding: const EdgeInsets.all(2),
                              decoration: const BoxDecoration(
                                color: Colors.black54,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.close, size: 14, color: Colors.white),
                            ),
                          ),
                        ),
                      ],
                    );
                  },
                ),
              ),
            ],

            // Bottom toolbar
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              decoration: const BoxDecoration(
                border: Border(top: BorderSide(color: AppColors.border, width: 0.5)),
              ),
              child: Row(
                children: [
                  const Text(
                    'Thêm vào bài viết:',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.textSecondary),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.photo_library_outlined, color: Color(0xFF10B981)),
                    tooltip: 'Ảnh / Video',
                    onPressed: _pickMedia,
                  ),
                  IconButton(
                    icon: const Icon(Icons.tag, color: AppColors.primary),
                    tooltip: 'Kênh',
                    onPressed: () {},
                  ),
                  IconButton(
                    icon: const Icon(Icons.sentiment_satisfied_alt_outlined, color: Color(0xFFF59E0B)),
                    tooltip: 'Cảm xúc',
                    onPressed: () {},
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
