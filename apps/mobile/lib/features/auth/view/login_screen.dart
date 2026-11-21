import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_text_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../../../core/widgets/notice_banner.dart';
import '../view_model/auth_view_model.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _handleLogin() {
    final email = _emailController.text;
    final password = _passwordController.text;
    ref.read(authViewModelProvider.notifier).login(email, password);
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authViewModelProvider);

    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Align(
          alignment: Alignment.topCenter,
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(28, 12, 28, 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Brand Icon
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primary.withValues(alpha: 0.3),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.public_rounded,
                    color: Colors.white,
                    size: 32,
                  ),
                ),
                const SizedBox(height: 20),

                // Subtitle overline
                const Text(
                  'Openhello · WORK TOGETHER',
                  style: AppTextStyles.overline,
                ),
                const SizedBox(height: 8),

                // Main headline
                const Text(
                  'Kết nối hôm nay.\nVươn xa ngày mai.',
                  style: TextStyle(
                    fontSize: 32,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                    height: 1.2,
                  ),
                ),
                const SizedBox(height: 12),

                // Descriptive copy
                const Text(
                  'Không gian chung để chia sẻ, học hỏi và cùng đồng đội tạo nên những điều có ý nghĩa.',
                  style: AppTextStyles.bodySecondary,
                ),
                const SizedBox(height: 28),

                // Email field
                AppTextField(
                  label: 'Email công ty',
                  placeholder: 'ban@congty.com',
                  controller: _emailController,
                  errorText: authState.emailError,
                  keyboardType: TextInputType.emailAddress,
                  prefixIcon: const Icon(Icons.mail_outline, size: 20, color: AppColors.textSecondary),
                ),
                const SizedBox(height: 16),

                // Password field
                AppTextField(
                  label: 'Mật khẩu',
                  placeholder: 'Nhập mật khẩu',
                  controller: _passwordController,
                  errorText: authState.passwordError,
                  obscureText: true,
                  prefixIcon: const Icon(Icons.lock_outline, size: 20, color: AppColors.textSecondary),
                  onSubmitted: (_) => _handleLogin(),
                ),

                // Global Error Notice Banner
                if (authState.errorMessage != null) ...[
                  const SizedBox(height: 12),
                  NoticeBanner(message: authState.errorMessage!),
                ],
                const SizedBox(height: 24),

                // Login Button
                AppButton(
                  title: 'Đăng nhập',
                  busy: authState.isLoading,
                  onPressed: _handleLogin,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
