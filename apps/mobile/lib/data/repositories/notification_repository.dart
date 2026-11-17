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

  static final List<NotificationModel> _demoNotifications = [
    NotificationModel(
      id: 'n1',
      title: 'Thông báo từ Ban Giám Đốc',
      body: 'Cùng chào đón các thành viên mới gia nhập đại gia đình Openhello.vn trong tháng này!',
      createdAt: DateTime.now().subtract(const Duration(minutes: 40)).toIso8601String(),
      notificationType: 'announcement',
    ),
    NotificationModel(
      id: 'n2',
      title: 'Cập nhật từ Tech & Architecture',
      body: 'Cập nhật mới về pipeline AI Knowledge Graph đã hoàn tất kiểm thử.',
      createdAt: DateTime.now().subtract(const Duration(hours: 3)).toIso8601String(),
      notificationType: 'mention',
    ),
    NotificationModel(
      id: 'n3',
      title: 'Hoạt động nội bộ công ty',
      body: 'Buổi chia sẻ kiến thức chuyên đề Microservices sẽ diễn ra vào thứ Sáu tuần này.',
      createdAt: DateTime.now().subtract(const Duration(days: 1)).toIso8601String(),
      notificationType: 'comment',
      readAt: DateTime.now().subtract(const Duration(hours: 12)).toIso8601String(),
    ),
  ];

  Future<Result<PageResponse<NotificationModel>>> getNotifications({
    bool? unread,
    String? cursor,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      var items = _demoNotifications;
      if (unread == true) {
        items = items.where((n) => !n.isRead).toList();
      }
      return Result.success(PageResponse(data: items));
    }

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

  Future<Result<int>> getUnreadCount({bool isDemo = false}) async {
    if (isDemo) {
      return Result.success(_demoNotifications.where((n) => !n.isRead).length);
    }
    try {
      final count = await _service.getUnreadCount();
      return Result.success(count);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể kiểm tra thông báo chưa đọc.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> markRead(String notificationId, {bool isDemo = false}) async {
    if (isDemo) {
      final index = _demoNotifications.indexWhere((n) => n.id == notificationId);
      if (index != -1) {
        _demoNotifications[index] = _demoNotifications[index].copyWith(
          readAt: DateTime.now().toIso8601String(),
        );
      }
      return const Result.success(null);
    }
    try {
      await _service.markRead(notificationId);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể đánh dấu đã đọc.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> markAllRead({bool isDemo = false}) async {
    if (isDemo) {
      for (var i = 0; i < _demoNotifications.length; i++) {
        _demoNotifications[i] = _demoNotifications[i].copyWith(
          readAt: DateTime.now().toIso8601String(),
        );
      }
      return const Result.success(null);
    }
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
