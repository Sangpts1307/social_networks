import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/error/failures.dart';
import '../../core/utils/result.dart';
import '../models/channel_model.dart';
import '../models/comment_model.dart';
import '../models/follow_stats_model.dart';
import '../models/knowledge_model.dart';
import '../models/post_model.dart';
import '../services/post_service.dart';

final postRepositoryProvider = Provider<PostRepository>((ref) {
  final service = ref.watch(postServiceProvider);
  return PostRepository(service);
});

class PostRepository {
  final PostService _service;

  PostRepository(this._service);

  Future<Result<PostModel>> getPost(String id) async {
    try {
      return Result.success(await _service.getPost(id));
    } on DioException catch (e) {
      return Result.failure(
        ServerFailure(
          'Không mở được bài viết.',
          statusCode: e.response?.statusCode,
        ),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<PageResponse<PostModel>>> getPosts({
    String? channelId,
    bool? isSaved,
    String? authorUserId,
    String? postType,
    String? queryText,
    int? limit,
    String? cursor,
  }) async {
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
      final message =
          e.response?.data is Map &&
              (e.response!.data as Map)['message'] != null
          ? (e.response!.data as Map)['message'].toString()
          : 'Không thể tải bảng tin.';
      return Result.failure(
        ServerFailure(message, statusCode: e.response?.statusCode),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<PostModel>> createPost({
    required String channelId,
    required String bodyMarkdown,
  }) async {
    try {
      final post = await _service.createPost(
        channelId: channelId,
        bodyMarkdown: bodyMarkdown,
      );
      return Result.success(post);
    } on DioException catch (e) {
      final message =
          e.response?.data is Map &&
              (e.response!.data as Map)['message'] != null
          ? (e.response!.data as Map)['message'].toString()
          : 'Không thể đăng bài viết.';
      return Result.failure(
        ServerFailure(message, statusCode: e.response?.statusCode),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<PostModel>> updatePost({
    required String id,
    required String bodyMarkdown,
  }) async {
    try {
      final post = await _service.updatePost(id, bodyMarkdown);
      return Result.success(post);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Cập nhật thất bại.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> removePost(String id) async {
    try {
      await _service.removePost(id);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Xóa bài thất bại.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> reportPost({
    required String postId,
    required String reasonCode,
    String? detail,
  }) async {
    try {
      await _service.reportPost(postId, reasonCode, detail);
      return const Result.success(null);
    } on DioException catch (e) {
      final data = e.response?.data;
      final message = data is Map && data['message'] != null
          ? data['message'].toString()
          : 'Không gửi được báo cáo vi phạm.';
      return Result.failure(
        ServerFailure(message, statusCode: e.response?.statusCode),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> react({
    required String postId,
    required String? reactionType,
  }) async {
    try {
      await _service.react(postId, reactionType);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(
        ServerFailure(e.message ?? 'Không thể thả cảm xúc.'),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> savePost({
    required String postId,
    required bool isSaved,
  }) async {
    try {
      await _service.savePost(postId, isSaved);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(
        ServerFailure(e.message ?? 'Không thể lưu bài viết.'),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<PageResponse<CommentModel>>> getComments(
    String postId, {
    String? cursor,
  }) async {
    try {
      final res = await _service.listComments(postId, cursor: cursor);
      return Result.success(res);
    } on DioException catch (e) {
      return Result.failure(
        ServerFailure(e.message ?? 'Không thể tải bình luận.'),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<CommentModel>> addComment({
    required String postId,
    required String bodyMarkdown,
    String? parentCommentId,
  }) async {
    try {
      final comment = await _service.addComment(
        postId: postId,
        bodyMarkdown: bodyMarkdown,
        parentCommentId: parentCommentId,
      );
      return Result.success(comment);
    } on DioException catch (e) {
      return Result.failure(
        ServerFailure(e.message ?? 'Không thể gửi bình luận.'),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<void>> deleteComment(String commentId) async {
    try {
      await _service.deleteComment(commentId);
      return const Result.success(null);
    } on DioException catch (e) {
      return Result.failure(
        ServerFailure(e.message ?? 'Không thể xóa bình luận.'),
      );
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }

  Future<Result<List<ChannelModel>>> getChannels() async {
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
  }) async {
    try {
      final ch = await _service.createChannel(
        code: code,
        name: name,
        description: description,
      );
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
  }) async {
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

  Future<Result<FollowStatsModel>> getFollowStats() async {
    try {
      final stats = await _service.getFollowStats();
      return Result.success(stats);
    } catch (_) {
      return const Result.success(FollowStatsModel());
    }
  }
}
