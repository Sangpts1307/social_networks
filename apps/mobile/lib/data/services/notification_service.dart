import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/dio_client.dart';
import '../models/knowledge_model.dart';
import '../models/notification_model.dart';

final notificationServiceProvider = Provider<NotificationService>((ref) {
  final dio = ref.watch(dioProvider);
  return NotificationService(dio);
});

class NotificationService {
  final Dio _dio;
  static const String _base = '/v1/notifications';

  NotificationService(this._dio);

  Future<PageResponse<NotificationModel>> getNotifications({
    bool? unread,
    String? cursor,
  }) async {
    final query = <String, dynamic>{};
    if (unread == true) query['unread'] = 'true';
    if (cursor != null && cursor.isNotEmpty) query['cursor'] = cursor;

    final response = await _dio.get(_base, queryParameters: query);
    final data = response.data as Map<String, dynamic>;
    final items = (data['data'] as List<dynamic>?)
            ?.map((e) => NotificationModel.fromJson(e as Map<String, dynamic>))
            .toList() ??
        [];
    final meta = data['meta'] as Map<String, dynamic>?;
    final nextCursor = meta?['next_cursor'] as String?;

    return PageResponse(data: items, nextCursor: nextCursor);
  }

  Future<int> getUnreadCount() async {
    final response = await _dio.get('$_base/unread-count');
    final data = response.data as Map<String, dynamic>;
    final inner = data['data'] as Map<String, dynamic>?;
    return (inner?['unread_count'] as num?)?.toInt() ?? 0;
  }

  Future<void> markRead(String notificationId) async {
    await _dio.put('$_base/$notificationId/read', data: {});
  }

  Future<void> markAllRead() async {
    await _dio.put('$_base/read-all', data: {});
  }

  Future<void> registerDevice({
    required String platform, // 'android' | 'ios'
    required String pushToken,
    String? deviceLabel,
    String? appVersion,
  }) async {
    await _dio.post(
      '/v1/devices',
      data: {
        'platform': platform,
        'push_token': pushToken,
        'device_label': deviceLabel,
        'app_version': appVersion,
      },
    );
  }
}
