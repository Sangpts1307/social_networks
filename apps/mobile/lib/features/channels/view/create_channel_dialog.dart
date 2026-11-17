import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../view_model/channels_view_model.dart';

class CreateChannelDialog extends ConsumerStatefulWidget {
  const CreateChannelDialog({super.key});

  @override
  ConsumerState<CreateChannelDialog> createState() => _CreateChannelDialogState();
}

class _CreateChannelDialogState extends ConsumerState<CreateChannelDialog> {
  final _nameController = TextEditingController();
  final _descController = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _nameController.dispose();
    _descController.dispose();
    super.dispose();
  }

  void _handleSubmit() async {
    final name = _nameController.text.trim();
    if (name.isEmpty) return;

    setState(() => _busy = true);
    final ok = await ref.read(channelsViewModelProvider.notifier).createChannel(
          name,
          _descController.text.trim(),
        );
    if (mounted) {
      setState(() => _busy = false);
      if (ok) Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Tạo kênh thảo luận',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 20),
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ],
            ),
            const SizedBox(height: 12),
            AppTextField(
              label: 'Tên kênh',
              placeholder: 'Ví dụ: Thiết kế & UI/UX',
              controller: _nameController,
              autofocus: true,
            ),
            const SizedBox(height: 14),
            AppTextField(
              label: 'Mô tả (tùy chọn)',
              placeholder: 'Mục đích và chủ đề trao đổi của kênh...',
              controller: _descController,
              maxLines: 3,
            ),
            const SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Hủy'),
                ),
                const SizedBox(width: 8),
                AppButton(
                  title: 'Tạo kênh',
                  width: 110,
                  height: 40,
                  busy: _busy,
                  onPressed: _handleSubmit,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
