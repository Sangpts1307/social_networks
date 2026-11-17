import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config/env_config.dart';
import '../storage/secure_storage_service.dart';
import 'interceptors/auth_interceptor.dart';
import 'interceptors/logger_interceptor.dart';

final dioProvider = Provider<Dio>((ref) {
  final storageService = ref.watch(secureStorageServiceProvider);
  return createDioClient(storageService);
});

Dio createDioClient(SecureStorageService storageService, {void Function()? onTokenExpired}) {
  final dio = Dio(
    BaseOptions(
      baseUrl: EnvConfig.apiBaseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  dio.interceptors.addAll([
    AuthInterceptor(storageService, onTokenExpired: onTokenExpired),
    LoggerInterceptor(),
  ]);

  return dio;
}
