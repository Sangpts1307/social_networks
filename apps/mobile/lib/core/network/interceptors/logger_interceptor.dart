import 'package:dio/dio.dart';
import '../../utils/logger.dart';

class LoggerInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    AppLogger.d('--> ${options.method.toUpperCase()} ${options.uri}');
    if (options.data != null) {
      final headers = Map<String, dynamic>.from(options.headers);
      headers.removeWhere((key, _) => key.toLowerCase() == 'authorization');
      AppLogger.d('Headers: $headers');
      final body = options.data;
      AppLogger.d(body is Map ? 'Body fields: ${body.keys.join(', ')}' : 'Body: [non-JSON payload]');
    }
    handler.next(options);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    AppLogger.d('<-- ${response.statusCode} ${response.requestOptions.uri}');
    handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    AppLogger.e(
      '<-- ERROR ${err.response?.statusCode} ${err.requestOptions.uri}: ${err.message}',
      err.error,
    );
    handler.next(err);
  }
}
