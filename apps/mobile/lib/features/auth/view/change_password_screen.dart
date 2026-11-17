import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_text_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../../../core/widgets/notice_banner.dart';
import '../view_model/auth_view_model.dart';

class ChangePasswordScreen extends ConsumerStatefulWidget {
  const ChangePasswordScreen({super.key});

  @override
  ConsumerState<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends ConsumerState<ChangePasswordScreen> {
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  @override
  void dispose() {
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  void _handleSubmit() {
    ref.read(authViewModelProvider.notifier).changePassword(
          currentPassword: _currentPasswordController.text,
          newPassword: _newPasswordController.text,
          confirmation: _confirmPasswordController.text,
        );
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authViewModelProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Đổi mật khẩu bắt buộc'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Đăng xuất',
            onPressed: () => ref.read(authViewModelProvider.notifier).logout(),
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Cập nhật mật khẩu',
                style: AppTextStyles.h2,
              ),
              const SizedBox(height: 8),
              const Text(
                'Tài khoản của bạn yêu cầu thay đổi mật khẩu lần đầu tiên để đảm bảo tính an toàn dữ liệu.',
                style: AppTextStyles.bodySecondary,
              ),
              const SizedBox(height: 24),
              AppTextField(
                label: 'Mật khẩu hiện tại',
                placeholder: 'Nhập mật khẩu hiện tại',
                controller: _currentPasswordController,
                obscureText: true,
              ),
              const SizedBox(height: 16),
              AppTextField(
                label: 'Mật khẩu mới',
                placeholder: 'Ít nhất 6 ký tự',
                controller: _newPasswordController,
                obscureText: true,
              ),
              const SizedBox(height: 16),
              AppTextField(
                label: 'Xác nhận mật khẩu mới',
                placeholder: 'Nhập lại mật khẩu mới',
                controller: _confirmPasswordController,
                obscureText: true,
              ),
              if (authState.errorMessage != null) ...[
                const SizedBox(height: 16),
                NoticeBanner(message: authState.errorMessage!),
              ],
              const SizedBox(height: 24),
              AppButton(
                title: 'Lưu mật khẩu mới',
                busy: authState.isLoading,
                onPressed: _handleSubmit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
