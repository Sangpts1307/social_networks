import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/string_utils.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../view_model/chat_view_model.dart';
import 'chat_detail_screen.dart';

class CreateGroupDialog extends ConsumerStatefulWidget {
  const CreateGroupDialog({super.key});

  @override
  ConsumerState<CreateGroupDialog> createState() => _CreateGroupDialogState();
}

class _CreateGroupDialogState extends ConsumerState<CreateGroupDialog> {
  final _titleController = TextEditingController();
  final Set<String> _selectedMemberIds = {};
  final String _search = '';
  bool _busy = false;

  @override
  void dispose() {
    _titleController.dispose();
    super.dispose();
  }

  void _handleCreate() async {
    final title = _titleController.text.trim();
    if (title.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Vui lòng nhập tên nhóm.')),
      );
      return;
    }
    if (_selectedMemberIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Vui lòng chọn ít nhất 1 thành viên.')),
      );
      return;
    }

    setState(() => _busy = true);
    final conv = await ref.read(chatViewModelProvider.notifier).createGroupChat(
          title,
          _selectedMemberIds.toList(),
        );

    if (mounted) {
      setState(() => _busy = false);
      if (conv != null) {
        Navigator.of(context).pop();
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => ChatDetailScreen(conversation: conv)),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final users = ref.watch(chatViewModelProvider).directory;
    final filtered = _search.isEmpty
        ? users
        : users.where((u) => StringUtils.matches(u.displayName, _search)).toList();

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        height: 520,
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Tạo nhóm chat mới',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 20),
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ],
            ),
            const SizedBox(height: 8),
            AppTextField(
              label: 'Tên nhóm',
              placeholder: 'Ví dụ: Ban Dự Án ĐATN...',
              controller: _titleController,
            ),
            const SizedBox(height: 12),
            Text(
              'Chọn thành viên (${_selectedMemberIds.length})',
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 6),
            Expanded(
              child: ListView.builder(
                itemCount: filtered.length,
                itemBuilder: (context, index) {
                  final u = filtered[index];
                  final isChecked = _selectedMemberIds.contains(u.id);

                  return CheckboxListTile(
                    value: isChecked,
                    contentPadding: EdgeInsets.zero,
                    activeColor: AppColors.primary,
                    secondary: AppAvatar(name: u.displayName, avatarUrl: u.avatarKey, size: 36),
                    title: Text(u.displayName, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                    subtitle: u.jobTitle != null
                        ? Text(u.jobTitle!, style: const TextStyle(fontSize: 11, color: AppColors.textSecondary))
                        : null,
                    onChanged: (val) {
                      setState(() {
                        if (val == true) {
                          _selectedMemberIds.add(u.id);
                        } else {
                          _selectedMemberIds.remove(u.id);
                        }
                      });
                    },
                  );
                },
              ),
            ),
            const SizedBox(height: 12),
            AppButton(
              title: 'Tạo nhóm (${_selectedMemberIds.length} người)',
              busy: _busy,
              onPressed: _handleCreate,
            ),
          ],
        ),
      ),
    );
  }
}
