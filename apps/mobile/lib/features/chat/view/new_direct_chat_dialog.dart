import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/string_utils.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../data/models/chat_model.dart';
import '../view_model/chat_view_model.dart';
import 'chat_detail_screen.dart';

class NewDirectChatDialog extends ConsumerStatefulWidget {
  const NewDirectChatDialog({super.key});

  @override
  ConsumerState<NewDirectChatDialog> createState() => _NewDirectChatDialogState();
}

class _NewDirectChatDialogState extends ConsumerState<NewDirectChatDialog> {
  final _searchController = TextEditingController();
  String _search = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _startChat(DirectoryUser user) async {
    Navigator.of(context).pop();
    final conv = await ref.read(chatViewModelProvider.notifier).createDirectChat(user);
    if (conv != null && mounted) {
      Navigator.of(context).push(
        MaterialPageRoute(builder: (_) => ChatDetailScreen(conversation: conv)),
      );
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
        height: 480,
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Tin nhắn mới',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 20),
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ],
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _searchController,
              onChanged: (v) => setState(() => _search = v),
              decoration: InputDecoration(
                hintText: 'Tìm đồng nghiệp...',
                hintStyle: const TextStyle(fontSize: 14, color: AppColors.textMuted),
                prefixIcon: const Icon(Icons.search, size: 20, color: AppColors.textSecondary),
                filled: true,
                fillColor: const Color(0xFFF1F5F9),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(20),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: filtered.isEmpty
                  ? const Center(
                      child: Text('Không tìm thấy người phù hợp', style: TextStyle(color: AppColors.textSecondary)),
                    )
                  : ListView.builder(
                      itemCount: filtered.length,
                      itemBuilder: (context, index) {
                        final u = filtered[index];
                        return ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          leading: AppAvatar(name: u.displayName, avatarUrl: u.avatarKey, size: 40),
                          title: Text(u.displayName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                          subtitle: u.jobTitle != null
                              ? Text(u.jobTitle!, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary))
                              : null,
                          onTap: () => _startChat(u),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
