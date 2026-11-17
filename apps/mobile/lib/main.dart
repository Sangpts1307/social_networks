import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';
import 'core/utils/logger.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  // Global Flutter error boundary
  FlutterError.onError = (details) {
    AppLogger.e('FlutterError: ${details.exceptionAsString()}', details.exception, details.stack);
    FlutterError.presentError(details);
  };

  // Global platform error boundary
  PlatformDispatcher.instance.onError = (error, stack) {
    AppLogger.e('PlatformDispatcher error: $error', error, stack);
    return true;
  };

  runApp(const ProviderScope(child: OpenhelloApp()));
}
