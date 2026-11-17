import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;

/// Environment configuration for API endpoints and environments.
class EnvConfig {
  EnvConfig._();

  static const String _defaultIosUrl = 'http://localhost:8000/api';
  static const String _defaultAndroidUrl = 'http://10.0.2.2:8000/api';

  /// Resolves the base API URL dynamically based on environment and platform.
  /// Can be overridden at runtime via:
  /// flutter run --dart-define=API_BASE_URL=http://your-server:8000/api
  static String get apiBaseUrl {
    const definedUrl = String.fromEnvironment('API_BASE_URL');
    if (definedUrl.isNotEmpty) {
      return definedUrl.replaceAll(RegExp(r'/+$'), '');
    }

    if (kIsWeb) {
      return _defaultIosUrl;
    }

    if (Platform.isAndroid) {
      return _defaultAndroidUrl;
    }

    return _defaultIosUrl;
  }
}
