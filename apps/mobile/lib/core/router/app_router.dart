import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/view/change_password_screen.dart';
import '../../features/auth/view/login_screen.dart';
import '../../features/auth/view_model/auth_view_model.dart';
import '../../features/home/view/main_scaffold_screen.dart';
import '../../features/library/view/library_screen.dart';
import '../../features/profile/view/profile_screen.dart';
import '../../features/search/view/search_screen.dart';
import 'app_routes.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authViewModelProvider);

  return GoRouter(
    initialLocation: AppRoutes.home,
    debugLogDiagnostics: false,
    redirect: (context, state) {
      if (authState.isBooting) {
        return null;
      }

      final isAuthenticated = authState.isAuthenticated;
      final mustChangePassword = authState.mustChangePassword;
      final isLoginRoute = state.matchedLocation == AppRoutes.login;
      final isChangePasswordRoute = state.matchedLocation == AppRoutes.changePassword;

      if (!isAuthenticated) {
        return isLoginRoute ? null : AppRoutes.login;
      }

      if (mustChangePassword) {
        return isChangePasswordRoute ? null : AppRoutes.changePassword;
      }

      if (isLoginRoute || isChangePasswordRoute) {
        return AppRoutes.home;
      }

      return null;
    },
    routes: [
      GoRoute(
        path: AppRoutes.home,
        builder: (context, state) => authState.isBooting
            ? const Scaffold(
                body: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircularProgressIndicator(),
                      SizedBox(height: 16),
                      Text('Đang mở không gian của bạn…', style: TextStyle(color: Colors.grey)),
                    ],
                  ),
                ),
              )
            : const MainScaffoldScreen(),
      ),
      GoRoute(
        path: AppRoutes.login,
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: AppRoutes.changePassword,
        builder: (context, state) => const ChangePasswordScreen(),
      ),
      GoRoute(
        path: AppRoutes.profile,
        builder: (context, state) => const ProfileScreen(),
      ),
      GoRoute(
        path: AppRoutes.library,
        builder: (context, state) => const LibraryScreen(),
      ),
      GoRoute(
        path: AppRoutes.search,
        builder: (context, state) => const SearchScreen(),
      ),
    ],
  );
});
