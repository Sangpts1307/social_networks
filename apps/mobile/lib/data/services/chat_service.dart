import 'dart:math';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/dio_client.dart';
import '../models/chat_model.dart';
import '../models/knowledge_model.dart';

final chatServiceProvider = Provider<ChatService>((ref) {
  final dio = ref.watch(dioProvider);
  return ChatService(dio);
});

class ChatService {
  final Dio _dio;

  ChatService(this._dio);

  String _newClientMessageId() {
    final bytes = List<int>.generate(16, (_) => Random.secure().nextInt(256));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    final hex = bytes
        .map((byte) => byte.toRadixString(16).padLeft(2, '0'))
        .join();
    return '${hex.substring(0, 8)}-${hex.substring(8, 12)}-${hex.substring(12, 16)}-${hex.substring(16, 20)}-${hex.substring(20)}';
  }

  Future<List<DirectoryUser>> getDirectoryUsers() async {
    final response = await _dio.get('/v1/directory/users');
    final data = response.data as Map<String, dynamic>;
    final items =
        (data['data'] as List<dynamic>?)
            ?.map((e) => DirectoryUser.fromJson(e as Map<String, dynamic>))
            .toList() ??
        [];
    return items;
  }

  Future<PageResponse<ConversationDto>> getConversations({
    String? cursor,
  }) async {
    final query = <String, dynamic>{};
    if (cursor != null) query['cursor'] = cursor;

    final response = await _dio.get(
      '/v1/chat/conversations',
      queryParameters: query,
    );
    final data = response.data as Map<String, dynamic>;
    final items =
        (data['data'] as List<dynamic>?)
            ?.map((e) => ConversationDto.fromJson(e as Map<String, dynamic>))
            .toList() ??
        [];
    final meta = data['meta'] as Map<String, dynamic>?;
    final nextCursor = meta?['next_cursor'] as String?;

    return PageResponse(data: items, nextCursor: nextCursor);
  }

  Future<ConversationDto> createConversation({
    required String type, // 'direct' | 'group'
    String? title,
    required List<String> memberIds,
  }) async {
    final response = await _dio.post(
      '/v1/chat/conversations',
      data: {
        'type': type,
        // ignore: use_null_aware_elements
        if (title != null) 'title': title,
        'member_ids': memberIds,
      },
    );
    final data = response.data as Map<String, dynamic>;
    return ConversationDto.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<PageResponse<ChatMessageDto>> getMessages(
    String conversationId, {
    String? cursor,
  }) async {
    final query = <String, dynamic>{};
    if (cursor != null) query['cursor'] = cursor;

    final response = await _dio.get(
      '/v1/chat/conversations/$conversationId/messages',
      queryParameters: query,
    );
    final data = response.data as Map<String, dynamic>;
    final items =
        (data['data'] as List<dynamic>?)
            ?.map((e) => ChatMessageDto.fromJson(e as Map<String, dynamic>))
            .toList() ??
        [];
    final meta = data['meta'] as Map<String, dynamic>?;
    final nextCursor = meta?['next_cursor'] as String?;

    return PageResponse(data: items, nextCursor: nextCursor);
  }

  Future<ChatMessageDto> sendMessage(
    String conversationId, {
    required String bodyMarkdown,
    String? clientMessageId,
  }) async {
    final response = await _dio.post(
      '/v1/chat/conversations/$conversationId/messages',
      data: {
        'body_markdown': bodyMarkdown,
        'client_message_id': clientMessageId ?? _newClientMessageId(),
      },
    );
    final data = response.data as Map<String, dynamic>;
    return ChatMessageDto.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<void> markRead(String conversationId) async {
    await _dio.put('/v1/chat/conversations/$conversationId/read', data: {});
  }
}
