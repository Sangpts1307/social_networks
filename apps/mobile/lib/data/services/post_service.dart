import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/dio_client.dart';
import '../models/channel_model.dart';
import '../models/comment_model.dart';
import '../models/follow_stats_model.dart';
import '../models/knowledge_model.dart';
import '../models/post_model.dart';

final postServiceProvider = Provider<PostService>((ref) {
  final dio = ref.watch(dioProvider);
  return PostService(dio);
});

class PostService {
  final Dio _dio;
  static const String _base = '/v1/social';

  PostService(this._dio);

  Future<PageResponse<PostModel>> listPosts({
    String? channelId,
    bool? isSaved,
    String? authorUserId,
    String? postType,
    String? queryText,
    int? limit,
    String? cursor,
  }) async {
    final query = <String, dynamic>{};
    if (channelId != null && channelId.isNotEmpty) query['channel_id'] = channelId;
    if (isSaved == true) query['saved'] = '1';
    if (authorUserId != null && authorUserId.isNotEmpty) query['author_user_id'] = authorUserId;
    if (postType != null && postType.isNotEmpty) query['post_type'] = postType;
    if (queryText != null && queryText.trim().length >= 2) query['q'] = queryText.trim();
    if (limit != null) query['limit'] = limit.toString();
    if (cursor != null && cursor.isNotEmpty) query['cursor'] = cursor;

    final response = await _dio.get('$_base/posts', queryParameters: query);
    final data = response.data as Map<String, dynamic>;
    final items = (data['data'] as List<dynamic>?)
            ?.map((e) => PostModel.fromJson(e as Map<String, dynamic>))
            .toList() ??
        [];
    final meta = data['meta'] as Map<String, dynamic>?;
    final nextCursor = meta?['next_cursor'] as String?;

    return PageResponse(data: items, nextCursor: nextCursor);
  }

  Future<PostModel> createPost({
    required String channelId,
    required String bodyMarkdown,
  }) async {
    final response = await _dio.post(
      '$_base/posts',
      data: {
        'channel_id': channelId,
        'body_markdown': bodyMarkdown,
        'post_type': 'update',
        'publication_status': 'published',
      },
    );
    final data = response.data as Map<String, dynamic>;
    return PostModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<PostModel> updatePost(String id, String bodyMarkdown) async {
    final response = await _dio.patch(
      '$_base/posts/$id',
      data: {'body_markdown': bodyMarkdown},
    );
    final data = response.data as Map<String, dynamic>;
    return PostModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<void> removePost(String id) async {
    await _dio.delete('$_base/posts/$id');
  }

  Future<void> react(String id, String? reactionType) async {
    if (reactionType != null) {
      await _dio.put(
        '$_base/posts/$id/reaction',
        data: {'reaction_type': reactionType},
      );
    } else {
      await _dio.delete('$_base/posts/$id/reaction');
    }
  }

  Future<void> savePost(String id, bool save) async {
    if (save) {
      await _dio.put('$_base/posts/$id/save');
    } else {
      await _dio.delete('$_base/posts/$id/save');
    }
  }

  Future<PageResponse<CommentModel>> listComments(String postId, {String? cursor}) async {
    final query = <String, dynamic>{};
    if (cursor != null) query['cursor'] = cursor;

    final response = await _dio.get('$_base/posts/$postId/comments', queryParameters: query);
    final data = response.data as Map<String, dynamic>;
    final items = (data['data'] as List<dynamic>?)
            ?.map((e) => CommentModel.fromJson(e as Map<String, dynamic>))
            .toList() ??
        [];
    final meta = data['meta'] as Map<String, dynamic>?;
    final nextCursor = meta?['next_cursor'] as String?;

    return PageResponse(data: items, nextCursor: nextCursor);
  }

  Future<CommentModel> addComment({
    required String postId,
    required String bodyMarkdown,
    String? parentCommentId,
  }) async {
    final payload = <String, dynamic>{
      'body_markdown': bodyMarkdown,
    };
    if (parentCommentId != null) payload['parent_comment_id'] = parentCommentId;

    final response = await _dio.post('$_base/posts/$postId/comments', data: payload);
    final data = response.data as Map<String, dynamic>;
    return CommentModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<void> deleteComment(String commentId) async {
    await _dio.delete('$_base/comments/$commentId');
  }

  Future<PageResponse<ChannelModel>> listChannels({String? cursor}) async {
    final query = <String, dynamic>{};
    if (cursor != null) query['cursor'] = cursor;

    final response = await _dio.get('$_base/channels', queryParameters: query);
    final data = response.data as Map<String, dynamic>;
    final items = (data['data'] as List<dynamic>?)
            ?.map((e) => ChannelModel.fromJson(e as Map<String, dynamic>))
            .toList() ??
        [];
    final meta = data['meta'] as Map<String, dynamic>?;
    final nextCursor = meta?['next_cursor'] as String?;

    return PageResponse(data: items, nextCursor: nextCursor);
  }

  Future<ChannelModel> createChannel({
    required String code,
    required String name,
    String? description,
  }) async {
    final response = await _dio.post(
      '$_base/channels',
      data: {
        'code': code,
        'name': name,
        'description': description ?? 'Kênh trao đổi nội bộ.',
        'visibility': 'organization',
        'membership_policy': 'open',
      },
    );
    final data = response.data as Map<String, dynamic>;
    return ChannelModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<PageResponse<ChannelMemberModel>> channelMembers(String id, {String? cursor}) async {
    final query = <String, dynamic>{};
    if (cursor != null) query['cursor'] = cursor;

    final response = await _dio.get('$_base/channels/$id/members', queryParameters: query);
    final data = response.data as Map<String, dynamic>;
    final items = (data['data'] as List<dynamic>?)
            ?.map((e) => ChannelMemberModel.fromJson(e as Map<String, dynamic>))
            .toList() ??
        [];
    final meta = data['meta'] as Map<String, dynamic>?;
    final nextCursor = meta?['next_cursor'] as String?;

    return PageResponse(data: items, nextCursor: nextCursor);
  }

  Future<void> joinChannel(String id) async {
    await _dio.put('$_base/channels/$id/membership', data: {});
  }

  Future<void> leaveChannel(String id) async {
    await _dio.delete('$_base/channels/$id/membership');
  }

  Future<FollowStatsModel> getFollowStats() async {
    final response = await _dio.get('$_base/follow-stats');
    final data = response.data as Map<String, dynamic>;
    return FollowStatsModel.fromJson(data['data'] as Map<String, dynamic>);
  }
}
