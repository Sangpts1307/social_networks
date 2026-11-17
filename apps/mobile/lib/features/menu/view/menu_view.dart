import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../auth/view_model/auth_view_model.dart';
import '../../library/view/library_screen.dart';
import '../../profile/view/profile_screen.dart';

class MenuView extends ConsumerWidget {
  final VoidCallback? onSwitchToChannels;
  final VoidCallback? onSwitchToSaved;

  const MenuView({super.key, this.onSwitchToChannels, this.onSwitchToSaved});

  void _openProfile(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const ProfileScreen()),
    );
  }

  void _openLibrary(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const LibraryScreen()),
    );
  }

  void _confirmLogout(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Xác nhận đăng xuất'),
        content: const Text('Bạn có chắc chắn muốn đăng xuất khỏi ứng dụng không?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Hủy'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(ctx).pop();
              ref.read(authViewModelProvider.notifier).logout();
            },
            child: const Text('Đăng xuất', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authViewModelProvider).user;

    return Scaffold(
      backgroundColor: AppColors.scaffoldBackground,
      appBar: AppBar(
        title: const Text('Menu'),
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(vertical: 12),
        children: [
          // Profile Card
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: const [
                BoxShadow(color: Color(0x06000000), blurRadius: 4, offset: Offset(0, 1)),
              ],
            ),
            child: ListTile(
              contentPadding: const EdgeInsets.all(12),
              leading: AppAvatar(
                name: user?.displayName ?? 'User',
                avatarUrl: user?.avatarUrl,
                size: 50,
              ),
              title: Text(
                user?.displayName ?? 'Thành viên',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              subtitle: Text(
                'Xem trang cá nhân của bạn',
                style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
              ),
              trailing: const Icon(Icons.arrow_forward_ios, size: 16, color: AppColors.textMuted),
              onTap: () => _openProfile(context),
            ),
          ),
          const SizedBox(height: 12),

          // Shortcut Group
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: const [
                BoxShadow(color: Color(0x06000000), blurRadius: 4, offset: Offset(0, 1)),
              ],
            ),
            child: Column(
              children: [
                _buildMenuItem(
                  icon: Icons.bookmark_outline,
                  iconColor: const Color(0xFF7C3AED),
                  title: 'Bài viết đã lưu',
                  subtitle: 'Xem lại các bài viết bạn đã đánh dấu',
                  onTap: () {
                    onSwitchToSaved?.call();
                  },
                ),
                const Divider(height: 1, indent: 56),
                _buildMenuItem(
                  icon: Icons.person_outline,
                  iconColor: AppColors.primary,
                  title: 'Bài viết của tôi',
                  subtitle: 'Quản lý các nội dung bạn đã chia sẻ',
                  onTap: () => _openProfile(context),
                ),
                const Divider(height: 1, indent: 56),
                _buildMenuItem(
                  icon: Icons.menu_book_outlined,
                  iconColor: const Color(0xFFD97706),
                  title: 'Thư viện tri thức',
                  subtitle: 'Base Library - Tài liệu, cẩm nang nội bộ',
                  onTap: () => _openLibrary(context),
                ),
                const Divider(height: 1, indent: 56),
                _buildMenuItem(
                  icon: Icons.tag,
                  iconColor: const Color(0xFF059669),
                  title: 'Kênh thảo luận',
                  subtitle: 'Tham gia các nhóm theo phòng ban và chủ đề',
                  onTap: () => onSwitchToChannels?.call(),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // App info & Logout
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: const [
                BoxShadow(color: Color(0x06000000), blurRadius: 4, offset: Offset(0, 1)),
              ],
            ),
            child: Column(
              children: [
                _buildMenuItem(
                  icon: Icons.info_outline,
                  iconColor: AppColors.textSecondary,
                  title: 'Về Openhello.vn Mobile',
                  subtitle: 'Phiên bản 1.0.0 (Flutter Enterprise)',
                  onTap: () {},
                ),
                const Divider(height: 1, indent: 56),
                _buildMenuItem(
                  icon: Icons.logout,
                  iconColor: Colors.red,
                  title: 'Đăng xuất',
                  subtitle: 'Rời phiên đăng nhập hiện tại',
                  titleColor: Colors.red,
                  onTap: () => _confirmLogout(context, ref),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuItem({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    Color? titleColor,
    required VoidCallback onTap,
  }) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      leading: Container(
        width: 38,
        height: 38,
        decoration: BoxDecoration(
          color: iconColor.withValues(alpha: 0.1),
          shape: BoxShape.circle,
        ),
        child: Icon(icon, color: iconColor, size: 20),
      ),
      title: Text(
        title,
        style: TextStyle(
          fontWeight: FontWeight.bold,
          fontSize: 15,
          color: titleColor ?? AppColors.textPrimary,
        ),
      ),
      subtitle: Text(
        subtitle,
        style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
      ),
      trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.textMuted),
      onTap: onTap,
    );
  }
}
