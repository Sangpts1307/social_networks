import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/utils/string_utils.dart';
import '../../../data/models/channel_model.dart';
import '../../../data/repositories/post_repository.dart';

import '../../auth/view_model/auth_view_model.dart';

class ChannelsState {
  final bool isLoading;
  final List<ChannelModel> channels;
  final List<String> joinedIds;
  final String searchQuery;
  final String? errorMessage;
  final String? toastMessage;

  const ChannelsState({
    this.isLoading = false,
    this.channels = const [],
    this.joinedIds = const ['general', 'tech', 'life'],
    this.searchQuery = '',
    this.errorMessage,
    this.toastMessage,
  });

  List<ChannelModel> get joinedChannels {
    final list = channels.where((c) => joinedIds.contains(c.id)).toList();
    if (searchQuery.isEmpty) return list;
    return list.where((c) => StringUtils.matches(c.name, searchQuery)).toList();
  }

  List<ChannelModel> get exploreChannels {
    final list = channels.where((c) => !joinedIds.contains(c.id)).toList();
    if (searchQuery.isEmpty) return list;
    return list.where((c) => StringUtils.matches(c.name, searchQuery)).toList();
  }

  ChannelsState copyWith({
    bool? isLoading,
    List<ChannelModel>? channels,
    List<String>? joinedIds,
    String? searchQuery,
    String? errorMessage,
    bool clearError = false,
    String? toastMessage,
    bool clearToast = false,
  }) {
    return ChannelsState(
      isLoading: isLoading ?? this.isLoading,
      channels: channels ?? this.channels,
      joinedIds: joinedIds ?? this.joinedIds,
      searchQuery: searchQuery ?? this.searchQuery,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      toastMessage: clearToast ? null : (toastMessage ?? this.toastMessage),
    );
  }
}

final channelsViewModelProvider =
    StateNotifierProvider<ChannelsViewModel, ChannelsState>((ref) {
  ref.watch(authViewModelProvider.select((state) => state.user?.id));
  final repo = ref.watch(postRepositoryProvider);
  return ChannelsViewModel(repo);
});

class ChannelsViewModel extends StateNotifier<ChannelsState> {
  final PostRepository _repository;

  ChannelsViewModel(this._repository) : super(const ChannelsState()) {
    loadChannels();
  }

  Future<void> loadChannels() async {
    state = state.copyWith(isLoading: true, clearError: true);
    final res = await _repository.getChannels();
    res.when(
      success: (channels) {
        state = state.copyWith(isLoading: false, channels: channels);
      },
      failure: (failure) {
        state = state.copyWith(isLoading: false, errorMessage: failure.message);
      },
    );
  }

  void setSearchQuery(String query) {
    state = state.copyWith(searchQuery: query);
  }

  Future<void> toggleJoin(String channelId) async {
    final currentlyJoined = state.joinedIds.contains(channelId);
    final nextJoined = currentlyJoined
        ? state.joinedIds.where((id) => id != channelId).toList()
        : [...state.joinedIds, channelId];

    state = state.copyWith(
      joinedIds: nextJoined,
      toastMessage: currentlyJoined ? 'Đã rời kênh' : 'Đã tham gia kênh',
    );

    await _repository.toggleJoinChannel(
      channelId: channelId,
      isJoined: currentlyJoined,
    );
  }

  Future<bool> createChannel(String name, String? description) async {
    final trimmed = name.trim();
    if (trimmed.isEmpty) return false;

    final slug = StringUtils.normalize(trimmed).replaceAll(RegExp(r'[^a-z0-9]+'), '-');
    final res = await _repository.createChannel(
      code: slug.isNotEmpty ? slug : 'channel-${DateTime.now().millisecondsSinceEpoch}',
      name: trimmed,
      description: description,
    );

    return res.when(
      success: (newChannel) {
        state = state.copyWith(
          channels: [newChannel, ...state.channels],
          joinedIds: [...state.joinedIds, newChannel.id],
          toastMessage: 'Đã tạo kênh "$trimmed"',
        );
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
