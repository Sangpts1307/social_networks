import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/error/failures.dart';
import '../../core/utils/result.dart';
import '../models/channel_model.dart';
import '../models/comment_model.dart';
import '../models/follow_stats_model.dart';
import '../models/knowledge_model.dart';
import '../models/post_model.dart';
import '../services/demo_data.dart';
import '../services/post_service.dart';

final postRepositoryProvider = Provider<PostRepository>((ref) {
  final service = ref.watch(postServiceProvider);
  return PostRepository(service);
});

class PostRepository {
  final PostService _service;

  PostRepository(this._service);

  Future<Result<PageResponse<PostModel>>> getPosts({
    String? channelId,
    bool? isSaved,
    String? authorUserId,
    String? postType,
    String? queryText,
    int? limit,
    String? cursor,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      var list = DemoData.initialPosts;
      if (channelId != null && channelId.isNotEmpty) {
        list = list.where((p) => p.channelId == channelId).toList();
      }
      if (isSaved == true) {
        list = list.where((p) => p.isSaved).toList();
      }
      if (authorUserId != null && authorUserId.isNotEmpty) {
        list = list.where((p) => p.authorUserId == authorUserId).toList();
      }
      if (queryText != null && queryText.isNotEmpty) {
        final q = queryText.toLowerCase();
        list = list.where((p) => p.bodyPlaintext.toLowerCase().contains(q) || (p.title?.toLowerCase().contains(q) ?? false)).toList();
      }
      return Result.success(PageResponse(data: list));
    }

    try {
      final res = await _service.listPosts(
        channelId: channelId,
        isSaved: isSaved,
        authorUserId: authorUserId,
        postType: postType,
        queryText: queryText,
        limit: limit,
        cursor: cursor,
      );
      return Result.success(res);
    } on DioException catch (e) {
      final message = e.response?.data is Map && (e.response!.data as Map)['message'] != null
          ? (e.response!.data as Map)['message'].toString()
          : 'Không thể tải bảng tin.';
      return Result.failure(ServerFailure(message, statusCode: e.response?.statusCode));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<PostModel>> createPost({
    required String channelId,
    required String bodyMarkdown,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      final post = PostModel(
        id: 'local-${DateTime.now().millisecondsSinceEpoch}',
        channelId: channelId,
        authorUserId: DemoData.demoUser.id,
        authorName: DemoData.demoUser.displayName,
        authorAvatar: DemoData.demoUser.avatarUrl,
        bodyPlaintext: bodyMarkdown,
        publishedAt: DateTime.now().toIso8601String(),
        reactionCount: 0,
        commentCount: 0,
        postType: 'update',
      );
      return Result.success(post);
    }

    try {
      final post = await _service.createPost(
        channelId: channelId,
        bodyMarkdown: bodyMarkdown,
      );
      return Result.success(post);
    } on DioException catch (e) {
      final message = e.response?.data is Map && (e.response!.data as Map)['message'] != null
          ? (e.response!.data as Map)['message'].toString()
          : 'Không thể đăng bài viết.';
      return Result.failure(ServerFailure(message, statusCode: e.response?.statusCode));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<PostModel>> updatePost({
    required String id,
    required String bodyMarkdown,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      final post = PostModel(
        id: id,
        channelId: 'general',
        authorUserId: DemoData.demoUser.id,
        bodyPlaintext: bodyMarkdown,
        editedAt: DateTime.now().toIso8601String(),
      );
      return Result.success(post);
    }

    try {
      final post = await _service.updatePost(id, bodyMarkdown);
      return Result.success(post);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Cập nhật thất bại.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> removePost(String id, {bool isDemo = false}) async {
    if (isDemo) return const Result.success(null);
    try {
      await _service.removePost(id);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Xóa bài thất bại.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> react({
    required String postId,
    required String? reactionType,
    bool isDemo = false,
  }) async {
    if (isDemo) return const Result.success(null);
    try {
      await _service.react(postId, reactionType);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể thả cảm xúc.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> savePost({
    required String postId,
    required bool isSaved,
    bool isDemo = false,
  }) async {
    if (isDemo) return const Result.success(null);
    try {
      await _service.savePost(postId, isSaved);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể lưu bài viết.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<PageResponse<CommentModel>>> getComments(
    String postId, {
    String? cursor,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      final comments = DemoData.initialComments[postId] ?? [];
      return Result.success(PageResponse(data: comments));
    }
    try {
      final res = await _service.listComments(postId, cursor: cursor);
      return Result.success(res);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể tải bình luận.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<CommentModel>> addComment({
    required String postId,
    required String bodyMarkdown,
    String? parentCommentId,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      final comment = CommentModel(
        id: 'local-c-${DateTime.now().millisecondsSinceEpoch}',
        postId: postId,
        parentCommentId: parentCommentId,
        authorUserId: DemoData.demoUser.id,
        authorName: DemoData.demoUser.displayName,
        authorAvatar: DemoData.demoUser.avatarUrl,
        bodyPlaintext: bodyMarkdown,
        createdAt: DateTime.now().toIso8601String(),
      );
      return Result.success(comment);
    }
    try {
      final comment = await _service.addComment(
        postId: postId,
        bodyMarkdown: bodyMarkdown,
        parentCommentId: parentCommentId,
      );
      return Result.success(comment);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể gửi bình luận.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> deleteComment(String commentId, {bool isDemo = false}) async {
    if (isDemo) return const Result.success(null);
    try {
      await _service.deleteComment(commentId);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể xóa bình luận.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<List<ChannelModel>>> getChannels({bool isDemo = false}) async {
    if (isDemo) return Result.success(DemoData.channels);
    try {
      final res = await _service.listChannels();
      return Result.success(res.data);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể tải kênh.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<ChannelModel>> createChannel({
    required String code,
    required String name,
    String? description,
    bool isDemo = false,
  }) async {
    if (isDemo) {
      final ch = ChannelModel(id: code, name: name, description: description);
      return Result.success(ch);
    }
    try {
      final ch = await _service.createChannel(code: code, name: name, description: description);
      return Result.success(ch);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể tạo kênh.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> toggleJoinChannel({
    required String channelId,
    required bool isJoined,
    bool isDemo = false,
  }) async {
    if (isDemo) return const Result.success(null);
    try {
      if (isJoined) {
        await _service.leaveChannel(channelId);
      } else {
        await _service.joinChannel(channelId);
      }
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Thao tác thất bại.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<FollowStatsModel>> getFollowStats({bool isDemo = false}) async {
    if (isDemo) {
      return const Result.success(FollowStatsModel(followingCount: 14, followersCount: 28));
    }
    try {
      final stats = await _service.getFollowStats();
      return Result.success(stats);
    } catch (_) {
      return const Result.success(FollowStatsModel());
    }
  }
}
