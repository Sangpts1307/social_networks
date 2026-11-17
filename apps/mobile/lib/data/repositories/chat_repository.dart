import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/error/failures.dart';
import '../../core/utils/result.dart';
import '../models/chat_model.dart';
import '../models/knowledge_model.dart';
import '../services/chat_service.dart';
import '../services/demo_data.dart';

final chatRepositoryProvider = Provider<ChatRepository>((ref) {
  final service = ref.watch(chatServiceProvider);
  return ChatRepository(service);
});

class ChatRepository {
  final ChatService _service;

  ChatRepository(this._service);

  Future<Result<List<DirectoryUser>>> getDirectoryUsers({bool isDemo = false}) async {
    if (isDemo) {
      final list = DemoData.demoMembers.values.map((u) => DirectoryUser(
        id: u.id,
        displayName: u.displayName,
        jobTitle: u.role,
        avatarKey: u.avatarUrl,
      )).toList();
      return Result.success(list);
    }
    try {
      final users = await _service.getDirectoryUsers();
      return Result.success(users);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể tải danh bạ.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<PageResponse<ConversationDto>>> getConversations({
    String? cursor,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      return Result.success(PageResponse(data: DemoData.conversations));
    }
    try {
      final res = await _service.getConversations(cursor: cursor);
      return Result.success(res);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể tải cuộc trò chuyện.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<PageResponse<ChatMessageDto>>> getMessages(
    String conversationId, {
    String? cursor,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      final list = DemoData.conversationMessages[conversationId] ?? [];
      return Result.success(PageResponse(data: list));
    }
    try {
      final res = await _service.getMessages(conversationId, cursor: cursor);
      return Result.success(res);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể tải tin nhắn.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<ChatMessageDto>> sendMessage({
    required String conversationId,
    required String bodyMarkdown,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      final msg = ChatMessageDto(
        id: 'msg-${DateTime.now().millisecondsSinceEpoch}',
        conversationId: conversationId,
        senderUserId: DemoData.demoUser.id,
        bodyPlaintext: bodyMarkdown,
        sentAt: DateTime.now().toIso8601String(),
      );
      return Result.success(msg);
    }
    try {
      final msg = await _service.sendMessage(conversationId, bodyMarkdown: bodyMarkdown);
      return Result.success(msg);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể gửi tin nhắn.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<ConversationDto>> createConversation({
    required String type,
    String? title,
    required List<String> memberIds,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      final conv = ConversationDto(
        id: 'demo-conv-${DateTime.now().millisecondsSinceEpoch}',
        type: type,
        title: title ?? 'Nhóm thảo luận',
        createdByUserId: DemoData.demoUser.id,
        memberCount: memberIds.length + 1,
      );
      return Result.success(conv);
    }
    try {
      final conv = await _service.createConversation(
        type: type,
        title: title,
        memberIds: memberIds,
      );
      return Result.success(conv);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể tạo nhóm.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<void> markRead(String conversationId, {bool isDemo = false}) async {
    if (isDemo) return;
    try {
      await _service.markRead(conversationId);
    } catch (_) {}
  }
}
