import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/utils/validators.dart';
import '../../../data/repositories/auth_repository.dart';
import 'auth_state.dart';

final authViewModelProvider = StateNotifierProvider<AuthViewModel, AuthState>((ref) {
  final repo = ref.watch(authRepositoryProvider);
  return AuthViewModel(repo);
});

class AuthViewModel extends StateNotifier<AuthState> {
  final AuthRepository _repository;

  AuthViewModel(this._repository) : super(const AuthState()) {
    restoreSession();
  }

  Future<void> restoreSession() async {
    state = state.copyWith(isBooting: true, clearError: true);
    final result = await _repository.inspectSession();
    result.when(
      success: (user) {
        state = state.copyWith(
          isBooting: false,
          user: user,
          isDemo: false,
        );
      },
      failure: (failure) {
        state = state.copyWith(
          isBooting: false,
          clearUser: true,
          clearToken: true,
        );
      },
    );
  }

  Future<bool> login(String email, String password) async {
    final emailErr = Validators.validateEmail(email);
    final passwordErr = Validators.validatePassword(password);

    if (emailErr != null || passwordErr != null) {
      state = state.copyWith(
        emailError: emailErr,
        passwordError: passwordErr,
        clearError: true,
      );
      return false;
    }

    state = state.copyWith(isLoading: true, clearError: true);

    final result = await _repository.login(email: email.trim(), password: password);
    return result.when(
      success: (session) {
        state = state.copyWith(
          isLoading: false,
          user: session.user,
          token: session.accessToken,
          isDemo: false,
          clearError: true,
        );
        return true;
      },
      failure: (failure) {
        state = state.copyWith(
          isLoading: false,
          errorMessage: failure.message,
        );
        return false;
      },
    );
  }

  void startDemo() {
    final demoSession = _repository.getDemoSession();
    state = state.copyWith(
      user: demoSession.user,
      token: demoSession.accessToken,
      isDemo: true,
      clearError: true,
    );
  }

  Future<void> logout() async {
    state = state.copyWith(isLoading: true);
    await _repository.logout(state.isDemo);
    state = const AuthState(isBooting: false);
  }

  Future<bool> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmation,
  }) async {
    if (newPassword.length < 6) {
      state = state.copyWith(errorMessage: 'Mật khẩu mới phải có ít nhất 6 ký tự.');
      return false;
    }
    if (newPassword != confirmation) {
      state = state.copyWith(errorMessage: 'Mật khẩu xác nhận không trùng khớp.');
      return false;
    }

    state = state.copyWith(isLoading: true, clearError: true);

    if (state.isDemo) {
      state = state.copyWith(
        isLoading: false,
        user: state.user?.copyWith(mustChangePassword: false),
      );
      return true;
    }

    final result = await _repository.changePassword(
      currentPassword: currentPassword,
      newPassword: newPassword,
      confirmation: confirmation,
    );

    return result.when(
      success: (_) {
        state = state.copyWith(
          isLoading: false,
          user: state.user?.copyWith(mustChangePassword: false),
          clearError: true,
        );
        return true;
      },
      failure: (failure) {
        state = state.copyWith(
          isLoading: false,
          errorMessage: failure.message,
        );
        return false;
      },
    );
  }
}
