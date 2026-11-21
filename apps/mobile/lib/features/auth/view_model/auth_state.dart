import '../../../data/models/user_model.dart';

class AuthState {
  final bool isBooting;
  final bool isLoading;
  final UserModel? user;
  final String? token;
  final String? errorMessage;
  final String? emailError;
  final String? passwordError;

  const AuthState({
    this.isBooting = true,
    this.isLoading = false,
    this.user,
    this.token,
    this.errorMessage,
    this.emailError,
    this.passwordError,
  });

  bool get isAuthenticated => user != null;
  bool get mustChangePassword => user?.mustChangePassword ?? false;

  AuthState copyWith({
    bool? isBooting,
    bool? isLoading,
    UserModel? user,
    bool clearUser = false,
    String? token,
    bool clearToken = false,
    String? errorMessage,
    bool clearError = false,
    String? emailError,
    String? passwordError,
  }) {
    return AuthState(
      isBooting: isBooting ?? this.isBooting,
      isLoading: isLoading ?? this.isLoading,
      user: clearUser ? null : (user ?? this.user),
      token: clearToken ? null : (token ?? this.token),
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      emailError: emailError,
      passwordError: passwordError,
    );
  }
}
