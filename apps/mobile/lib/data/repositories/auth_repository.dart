import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/error/failures.dart';
import '../../core/storage/secure_storage_service.dart';
import '../../core/utils/result.dart';
import '../models/session_model.dart';
import '../models/user_model.dart';
import '../services/auth_service.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  final service = ref.watch(authServiceProvider);
  final storage = ref.watch(secureStorageServiceProvider);
  return AuthRepository(service, storage);
});

class AuthRepository {
  final AuthService _service;
  final SecureStorageService _storage;

  AuthRepository(this._service, this._storage);

  Future<Result<SessionModel>> login({
    required String email,
    required String password,
  }) async {
    try {
      final session = await _service.login(email, password);
      await _storage.saveToken(session.accessToken);
      await _storage.saveUserJson(jsonEncode(session.user.toJson()));
      return Result.success(session);
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      final body = e.response?.data;
      String message = 'Đăng nhập không thành công.';
      if (body is Map<String, dynamic>) {
        if (body['message'] != null) {
          message = body['message'].toString();
        } else if (body['errors'] is Map) {
          final errors = body['errors'] as Map;
          if (errors.isNotEmpty && errors.values.first is List) {
            message = (errors.values.first as List).first.toString();
          }
        }
      }
      return Result.failure(ServerFailure(message, statusCode: status));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<UserModel>> inspectSession() async {
    try {
      final token = await _storage.getToken();
      if (token == null || token.isEmpty) {
        return const Result.failure(AuthFailure('Chưa đăng nhập.'));
      }
      final user = await _service.inspectSession(token);
      await _storage.saveUserJson(jsonEncode(user.toJson()));
      return Result.success(user);
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        await _storage.clearAll();
        return const Result.failure(AuthFailure('Phiên đăng nhập đã hết hạn.'));
      }
      // If network fails but local user cache exists, return local cache
      final cachedJson = await _storage.getUserJson();
      if (cachedJson != null && cachedJson.isNotEmpty) {
        try {
          final user = UserModel.fromJson(
            jsonDecode(cachedJson) as Map<String, dynamic>,
          );
          return Result.success(user);
        } catch (_) {}
      }
      return Result.failure(NetworkFailure('Không thể kết nối đến máy chủ.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<void> logout() async {
    try {
      final token = await _storage.getToken();
      await _service.logout(token);
    } catch (_) {
      // Ignore remote logout error and always clear local
    }
    await _storage.clearAll();
  }

  Future<Result<void>> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmation,
  }) async {
    try {
      final token = await _storage.getToken();
      await _service.changePassword(
        currentPassword: currentPassword,
        newPassword: newPassword,
        confirmation: confirmation,
        token: token,
      );
      return const Result.success(null);
    } on DioException catch (e) {
      final message =
          e.response?.data is Map &&
              (e.response!.data as Map)['message'] != null
          ? (e.response!.data as Map)['message'].toString()
          : 'Đổi mật khẩu không thành công.';
      return Result.failure(
        ServerFailure(message, statusCode: e.response?.statusCode),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<Map<String, dynamic>>> getProfile() async {
    try {
      final token = await _storage.getToken();
      final profile = await _service.getProfile(token);
      return Result.success(profile);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể tải hồ sơ.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<Map<String, dynamic>>> updateProfile({
    String? bio,
    String? phone,
    String? location,
    List<String>? skills,
  }) async {
    try {
      final token = await _storage.getToken();
      final profile = await _service.updateProfile(
        bio: bio,
        phone: phone,
        location: location,
        skills: skills,
        token: token,
      );
      return Result.success(profile);
    } on DioException catch (e) {
      return Result.failure(
        ServerFailure(e.message ?? 'Không thể cập nhật hồ sơ.'),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }
}
