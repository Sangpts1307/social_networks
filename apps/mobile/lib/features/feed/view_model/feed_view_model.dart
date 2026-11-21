import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../data/models/comment_model.dart';
import '../../../data/models/post_model.dart';
import '../../../data/repositories/post_repository.dart';
import '../../auth/view_model/auth_view_model.dart';
import 'feed_state.dart';

final feedViewModelProvider = StateNotifierProvider<FeedViewModel, FeedState>((
  ref,
) {
  final repo = ref.watch(postRepositoryProvider);
  final authState = ref.watch(authViewModelProvider);
  return FeedViewModel(
    repo,
    currentUserId: authState.user?.id ?? '',
    currentUserName: authState.user?.displayName ?? 'Đồng nghiệp',
    currentUserAvatar: authState.user?.avatarUrl,
  );
});

class FeedViewModel extends StateNotifier<FeedState> {
  static List<PostModel> orderPosts(Iterable<PostModel> posts) {
    final sorted = posts.toList();
    sorted.sort((a, b) {
      final aPinned =
          a.isPinned &&
          (a.pinExpiresAt == null ||
              (DateTime.tryParse(a.pinExpiresAt!)?.isAfter(DateTime.now()) ??
                  false));
      final bPinned =
          b.isPinned &&
          (b.pinExpiresAt == null ||
              (DateTime.tryParse(b.pinExpiresAt!)?.isAfter(DateTime.now()) ??
                  false));
      if (aPinned != bPinned) return aPinned ? -1 : 1;
      final aTime = DateTime.tryParse(a.publishedAt ?? '');
      final bTime = DateTime.tryParse(b.publishedAt ?? '');
      return (bTime ?? DateTime.fromMillisecondsSinceEpoch(0)).compareTo(
        aTime ?? DateTime.fromMillisecondsSinceEpoch(0),
      );
    });
    return sorted;
  }

  final PostRepository _repository;
  final String currentUserId;
  final String currentUserName;
  final String? currentUserAvatar;

  FeedViewModel(
    this._repository, {
    required this.currentUserId,
    required this.currentUserName,
    this.currentUserAvatar,
  }) : super(const FeedState()) {
    init();
  }

  Future<void> init() async {
    state = state.copyWith(isLoading: true, clearError: true);
    await Future.wait([_loadChannels(), loadPosts()]);
    state = state.copyWith(isLoading: false);
  }

  Future<void> _loadChannels() async {
    final res = await _repository.getChannels();
    res.when(
      success: (channels) {
        state = state.copyWith(channels: channels);
      },
      failure: (_) {},
    );
  }

  Future<void> loadPosts({String? channelId}) async {
    final targetChannel = channelId ?? state.selectedChannelId;
    final res = await _repository.getPosts(channelId: targetChannel);
    res.when(
      success: (page) {
        state = state.copyWith(
          posts: orderPosts(page.data),
          cursor: page.nextCursor,
          selectedChannelId: targetChannel,
          clearError: true,
        );
      },
      failure: (failure) {
        state = state.copyWith(errorMessage: failure.message);
      },
    );
  }

  Future<void> refresh() async {
    state = state.copyWith(isRefreshing: true);
    await loadPosts();
    state = state.copyWith(isRefreshing: false);
  }

  void selectChannel(String? channelId) {
    if (channelId == state.selectedChannelId) return;
    state = state.copyWith(
      selectedChannelId: channelId,
      clearChannel: channelId == null,
      isLoading: true,
    );
    loadPosts(channelId: channelId).then((_) {
      state = state.copyWith(isLoading: false);
    });
  }

  Future<void> react(PostModel post, String? reactionType) async {
    final oldReaction = post.myReaction;
    final oldCount = post.reactionCount;

    // Optimistic update
    final newReaction = (oldReaction == reactionType) ? null : reactionType;
    int newCount = oldCount;
    if (oldReaction == null && newReaction != null) {
      newCount = oldCount + 1;
    } else if (oldReaction != null && newReaction == null) {
      newCount = (oldCount > 0) ? oldCount - 1 : 0;
    }

    final updatedPost = post.copyWith(
      myReaction: newReaction,
      clearReaction: newReaction == null,
      reactionCount: newCount,
    );

    state = state.copyWith(
      posts: state.posts.map((p) => p.id == post.id ? updatedPost : p).toList(),
    );

    final res = await _repository.react(
      postId: post.id,
      reactionType: newReaction,
    );
    if (res.isFailure) {
      // Rollback on failure
      state = state.copyWith(
        posts: state.posts.map((p) => p.id == post.id ? post : p).toList(),
        errorMessage: res.failureOrNull?.message,
      );
    }
  }

  Future<void> toggleSave(PostModel post) async {
    final newSaved = !post.isSaved;
    final updatedPost = post.copyWith(isSaved: newSaved);

    state = state.copyWith(
      posts: state.posts.map((p) => p.id == post.id ? updatedPost : p).toList(),
      toastMessage: newSaved
          ? 'Đã thêm vào bài viết đã lưu'
          : 'Đã bỏ lưu bài viết',
    );

    await _repository.savePost(postId: post.id, isSaved: newSaved);
  }

  Future<void> deletePost(String postId) async {
    final backup = state.posts;
    state = state.copyWith(
      posts: state.posts.where((p) => p.id != postId).toList(),
      toastMessage: 'Đã xóa bài viết',
    );

    final res = await _repository.removePost(postId);
    if (res.isFailure) {
      state = state.copyWith(
        posts: backup,
        errorMessage: res.failureOrNull?.message,
      );
    }
  }

  Future<bool> reportPost(
    String postId,
    String reasonCode,
    String? detail,
  ) async {
    final result = await _repository.reportPost(
      postId: postId,
      reasonCode: reasonCode,
      detail: detail,
    );
    return result.when(
      success: (_) {
        state = state.copyWith(toastMessage: 'Đã gửi báo cáo vi phạm');
        return true;
      },
      failure: (failure) {
        state = state.copyWith(errorMessage: failure.message);
        return false;
      },
    );
  }

  Future<bool> createPost({
    required String bodyMarkdown,
    required String channelId,
  }) async {
    final res = await _repository.createPost(
      channelId: channelId,
      bodyMarkdown: bodyMarkdown,
    );

    return res.when(
      success: (newPost) {
        final decoratedPost = newPost.copyWith(
          authorName: currentUserName,
          authorAvatar: currentUserAvatar,
        );
        state = state.copyWith(
          posts: orderPosts([decoratedPost, ...state.posts]),
          toastMessage: 'Bài viết của bạn đã được chia sẻ',
        );
        return true;
      },
      failure: (failure) {
        state = state.copyWith(errorMessage: failure.message);
        return false;
      },
    );
  }

  Future<bool> updatePost({
    required String postId,
    required String bodyMarkdown,
  }) async {
    final res = await _repository.updatePost(
      id: postId,
      bodyMarkdown: bodyMarkdown,
    );

    return res.when(
      success: (updated) {
        state = state.copyWith(
          posts: state.posts
              .map(
                (p) => p.id == postId
                    ? p.copyWith(bodyPlaintext: bodyMarkdown)
                    : p,
              )
              .toList(),
          toastMessage: 'Đã cập nhật bài viết',
        );
        return true;
      },
      failure: (failure) {
        state = state.copyWith(errorMessage: failure.message);
        return false;
      },
    );
  }

  Future<void> loadComments(String postId) async {
    final res = await _repository.getComments(postId);
    res.when(
      success: (page) {
        final updated = Map<String, List<CommentModel>>.from(
          state.commentsByPostId,
        );
        updated[postId] = page.data;
        state = state.copyWith(commentsByPostId: updated);
      },
      failure: (_) {},
    );
  }

  Future<bool> addComment({
    required String postId,
    required String bodyMarkdown,
    String? parentCommentId,
  }) async {
    final res = await _repository.addComment(
      postId: postId,
      bodyMarkdown: bodyMarkdown,
      parentCommentId: parentCommentId,
    );

    return res.when(
      success: (comment) {
        final updated = Map<String, List<CommentModel>>.from(
          state.commentsByPostId,
        );
        final currentList = updated[postId] ?? [];
        updated[postId] = [
          ...currentList,
          comment.copyWith(
            authorName: currentUserName,
            authorAvatar: currentUserAvatar,
          ),
        ];

        final updatedPosts = state.posts.map((p) {
          if (p.id == postId) {
            return p.copyWith(commentCount: p.commentCount + 1);
          }
          return p;
        }).toList();

        state = state.copyWith(commentsByPostId: updated, posts: updatedPosts);
        return true;
      },
      failure: (failure) {
        state = state.copyWith(errorMessage: failure.message);
        return false;
      },
    );
  }

  void clearToast() {
    state = state.copyWith(clearToast: true);
  }
}
