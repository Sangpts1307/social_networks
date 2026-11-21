import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/error/failures.dart';
import '../../core/utils/result.dart';
import '../models/knowledge_model.dart';
import '../models/notification_model.dart';
import '../services/notification_service.dart';

final notificationRepositoryProvider = Provider<NotificationRepository>((ref) {
  final service = ref.watch(notificationServiceProvider);
  return NotificationRepository(service);
});

class NotificationRepository {
  final NotificationService _service;

  NotificationRepository(this._service);

  Future<Result<PageResponse<NotificationModel>>> getNotifications({
    bool? unread,
    String? cursor,
  }) async {

    try {
      final res = await _service.getNotifications(unread: unread, cursor: cursor);
      return Result.success(res);
    } on DioException catch (e) {
      final message = e.response?.data is Map && (e.response!.data as Map)['message'] != null
          ? (e.response!.data as Map)['message'].toString()
          : 'Không thể tải thông báo.';
      return Result.failure(ServerFailure(message, statusCode: e.response?.statusCode));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<int>> getUnreadCount() async {
    try {
      final count = await _service.getUnreadCount();
      return Result.success(count);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể kiểm tra thông báo chưa đọc.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> markRead(String notificationId) async {
    try {
      await _service.markRead(notificationId);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể đánh dấu đã đọc.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> markAllRead() async {
    try {
      await _service.markAllRead();
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể đánh dấu tất cả đã đọc.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }
}
