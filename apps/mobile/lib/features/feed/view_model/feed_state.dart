import '../../../data/models/channel_model.dart';
import '../../../data/models/comment_model.dart';
import '../../../data/models/post_model.dart';
import '../../../data/models/story_model.dart';

class FeedState {
  final bool isLoading;
  final bool isRefreshing;
  final List<PostModel> posts;
  final List<StoryModel> stories;
  final List<ChannelModel> channels;
  final List<String> joinedChannelIds;
  final String? selectedChannelId;
  final String? cursor;
  final String? errorMessage;
  final String? toastMessage;
  final Map<String, List<CommentModel>> commentsByPostId;

  const FeedState({
    this.isLoading = false,
    this.isRefreshing = false,
    this.posts = const [],
    this.stories = const [],
    this.channels = const [],
    this.joinedChannelIds = const ['general', 'tech', 'life'],
    this.selectedChannelId,
    this.cursor,
    this.errorMessage,
    this.toastMessage,
    this.commentsByPostId = const {},
  });

  FeedState copyWith({
    bool? isLoading,
    bool? isRefreshing,
    List<PostModel>? posts,
    List<StoryModel>? stories,
    List<ChannelModel>? channels,
    List<String>? joinedChannelIds,
    String? selectedChannelId,
    bool clearChannel = false,
    String? cursor,
    String? errorMessage,
    bool clearError = false,
    String? toastMessage,
    bool clearToast = false,
    Map<String, List<CommentModel>>? commentsByPostId,
  }) {
    return FeedState(
      isLoading: isLoading ?? this.isLoading,
      isRefreshing: isRefreshing ?? this.isRefreshing,
      posts: posts ?? this.posts,
      stories: stories ?? this.stories,
      channels: channels ?? this.channels,
      joinedChannelIds: joinedChannelIds ?? this.joinedChannelIds,
      selectedChannelId: clearChannel ? null : (selectedChannelId ?? this.selectedChannelId),
      cursor: cursor ?? this.cursor,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      toastMessage: clearToast ? null : (toastMessage ?? this.toastMessage),
      commentsByPostId: commentsByPostId ?? this.commentsByPostId,
    );
  }
}
