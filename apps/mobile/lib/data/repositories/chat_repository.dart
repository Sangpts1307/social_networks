import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/error/failures.dart';
import '../../core/utils/result.dart';
import '../models/chat_model.dart';
import '../models/knowledge_model.dart';
import '../services/chat_service.dart';

final chatRepositoryProvider = Provider<ChatRepository>((ref) {
  final service = ref.watch(chatServiceProvider);
  return ChatRepository(service);
});

class ChatRepository {
  final ChatService _service;

  ChatRepository(this._service);

  Future<Result<List<DirectoryUser>>> getDirectoryUsers() async {
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
  }) async {
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
  }) async {
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
  }) async {
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
  }) async {
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

  Future<void> markRead(String conversationId) async {
    try {
      await _service.markRead(conversationId);
    } catch (_) {}
  }
}
