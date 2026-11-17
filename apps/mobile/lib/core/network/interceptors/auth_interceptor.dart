import 'package:dio/dio.dart';
import '../../storage/secure_storage_service.dart';
import '../../utils/logger.dart';

class AuthInterceptor extends Interceptor {
  final SecureStorageService _storageService;
  final void Function()? onTokenExpired;

  AuthInterceptor(this._storageService, {this.onTokenExpired});

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    if (!options.headers.containsKey('Authorization')) {
      final token = await _storageService.getToken();
      if (token != null && token.isNotEmpty) {
        options.headers['Authorization'] = 'Bearer $token';
      }
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    final statusCode = err.response?.statusCode;
    final path = err.requestOptions.path;

    if (statusCode == 401 && !path.contains('/auth/login')) {
      AppLogger.w('Token expired or unauthorized for path: $path');
      onTokenExpired?.call();
    }
    handler.next(err);
  }
}
