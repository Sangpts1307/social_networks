import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/dio_client.dart';
import '../models/session_model.dart';
import '../models/user_model.dart';

final authServiceProvider = Provider<AuthService>((ref) {
  final dio = ref.watch(dioProvider);
  return AuthService(dio);
});

class AuthService {
  final Dio _dio;

  AuthService(this._dio);

  Future<SessionModel> login(String email, String password) async {
    final response = await _dio.post(
      '/v1/auth/login',
      data: {'email': email, 'password': password},
    );
    return SessionModel.fromJson(response.data as Map<String, dynamic>);
  }

  Future<UserModel> inspectSession([String? token]) async {
    final response = await _dio.get(
      '/v1/auth/session',
      options: token != null
          ? Options(headers: {'Authorization': 'Bearer $token'})
          : null,
    );
    final data = response.data as Map<String, dynamic>;
    if (data['user'] is Map<String, dynamic>) {
      return UserModel.fromJson(data['user'] as Map<String, dynamic>);
    }
    return UserModel.fromJson(data);
  }

  Future<void> logout([String? token]) async {
    await _dio.post(
      '/v1/auth/logout',
      data: {},
      options: token != null
          ? Options(headers: {'Authorization': 'Bearer $token'})
          : null,
    );
  }

  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmation,
    String? token,
  }) async {
    await _dio.post(
      '/v1/auth/password',
      data: {
        'current_password': currentPassword,
        'password': newPassword,
        'password_confirmation': confirmation,
      },
      options: token != null
          ? Options(headers: {'Authorization': 'Bearer $token'})
          : null,
    );
  }

  Future<Map<String, dynamic>> getProfile([String? token]) async {
    final response = await _dio.get(
      '/v1/me/profile',
      options: token != null ? Options(headers: {'Authorization': 'Bearer $token'}) : null,
    );
    final data = response.data as Map<String, dynamic>;
    return (data['data'] as Map<String, dynamic>?) ?? data;
  }

  Future<Map<String, dynamic>> updateProfile({
    String? bio,
    String? phone,
    String? location,
    List<String>? skills,
    String? token,
  }) async {
    final payload = <String, dynamic>{};
    if (bio != null) payload['bio'] = bio;
    if (phone != null) payload['phone'] = phone;
    if (location != null) payload['location'] = location;
    if (skills != null) payload['skills'] = skills;

    final response = await _dio.patch(
      '/v1/me/profile',
      data: payload,
      options: token != null ? Options(headers: {'Authorization': 'Bearer $token'}) : null,
    );
    final data = response.data as Map<String, dynamic>;
    return (data['data'] as Map<String, dynamic>?) ?? data;
  }
}
