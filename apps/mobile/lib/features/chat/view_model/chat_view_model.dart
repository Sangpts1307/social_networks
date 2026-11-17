import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/utils/string_utils.dart';
import '../../../data/models/chat_model.dart';
import '../../../data/repositories/chat_repository.dart';
import '../../auth/view_model/auth_view_model.dart';

class ChatState {
  final bool isLoading;
  final List<ConversationDto> conversations;
  final List<DirectoryUser> directory;
  final Map<String, List<ChatMessageDto>> messagesByConvId;
  final String searchQuery;
  final String filter; // 'all' | 'direct' | 'group'
  final String? errorMessage;
  final String? toastMessage;

  const ChatState({
    this.isLoading = false,
    this.conversations = const [],
    this.directory = const [],
    this.messagesByConvId = const {},
    this.searchQuery = '',
    this.filter = 'all',
    this.errorMessage,
    this.toastMessage,
  });

  List<ConversationDto> get filteredConversations {
    var list = conversations;
    if (filter == 'direct') {
      list = list.where((c) => c.type == 'direct').toList();
    } else if (filter == 'group') {
      list = list.where((c) => c.type == 'group').toList();
    }
    if (searchQuery.isNotEmpty) {
      list = list.where((c) => StringUtils.matches(c.title ?? '', searchQuery)).toList();
    }
    return list;
  }

  ChatState copyWith({
    bool? isLoading,
    List<ConversationDto>? conversations,
    List<DirectoryUser>? directory,
    Map<String, List<ChatMessageDto>>? messagesByConvId,
    String? searchQuery,
    String? filter,
    String? errorMessage,
    bool clearError = false,
    String? toastMessage,
    bool clearToast = false,
  }) {
    return ChatState(
      isLoading: isLoading ?? this.isLoading,
      conversations: conversations ?? this.conversations,
      directory: directory ?? this.directory,
      messagesByConvId: messagesByConvId ?? this.messagesByConvId,
      searchQuery: searchQuery ?? this.searchQuery,
      filter: filter ?? this.filter,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      toastMessage: clearToast ? null : (toastMessage ?? this.toastMessage),
    );
  }
}

final chatViewModelProvider =
    StateNotifierProvider<ChatViewModel, ChatState>((ref) {
  final repo = ref.watch(chatRepositoryProvider);
  final authState = ref.watch(authViewModelProvider);
  return ChatViewModel(repo, isDemo: authState.isDemo, currentUserId: authState.user?.id ?? 'demo-user');
});

class ChatViewModel extends StateNotifier<ChatState> {
  final ChatRepository _repository;
  final bool isDemo;
  final String currentUserId;

  ChatViewModel(
    this._repository, {
    required this.isDemo,
    required this.currentUserId,
  }) : super(const ChatState()) {
    loadChatData();
  }

  Future<void> loadChatData() async {
    state = state.copyWith(isLoading: true, clearError: true);
    final convsRes = await _repository.getConversations(isDemo: isDemo);
    final usersRes = await _repository.getDirectoryUsers(isDemo: isDemo);

    List<ConversationDto> convs = [];
    List<DirectoryUser> users = [];

    convsRes.when(success: (p) => convs = p.data, failure: (_) {});
    usersRes.when(success: (u) => users = u.where((usr) => usr.id != currentUserId).toList(), failure: (_) {});

    state = state.copyWith(
      isLoading: false,
      conversations: convs,
      directory: users,
    );
  }

  void setSearchQuery(String query) {
    state = state.copyWith(searchQuery: query);
  }

  void setFilter(String filter) {
    state = state.copyWith(filter: filter);
  }

  Future<void> loadMessages(String conversationId) async {
    final res = await _repository.getMessages(conversationId, isDemo: isDemo);
    res.when(
      success: (page) {
        final updated = Map<String, List<ChatMessageDto>>.from(state.messagesByConvId);
        updated[conversationId] = page.data;
        state = state.copyWith(messagesByConvId: updated);
      },
      failure: (_) {},
    );
  }

  Future<bool> sendMessage(String conversationId, String text) async {
    final trimmed = text.trim();
    if (trimmed.isEmpty) return false;

    final res = await _repository.sendMessage(
      conversationId: conversationId,
      bodyMarkdown: trimmed,
      isDemo: isDemo,
    );

    return res.when(
      success: (newMsg) {
        final updated = Map<String, List<ChatMessageDto>>.from(state.messagesByConvId);
        final currentMsgs = updated[conversationId] ?? [];
        updated[conversationId] = [...currentMsgs, newMsg];

        final updatedConvs = state.conversations.map((c) {
          if (c.id == conversationId) {
            return c.copyWith(lastMessage: newMsg);
          }
          return c;
        }).toList();

        state = state.copyWith(
          messagesByConvId: updated,
          conversations: updatedConvs,
        );
        return true;
      },
      failure: (failure) {
        state = state.copyWith(errorMessage: failure.message);
        return false;
      },
    );
  }

  Future<ConversationDto?> createDirectChat(DirectoryUser user) async {
    final existing = state.conversations.where((c) => c.type == 'direct' && (c.title == user.displayName || c.directKey?.contains(user.id) == true));
    if (existing.isNotEmpty) {
      return existing.first;
    }

    final res = await _repository.createConversation(
      type: 'direct',
      title: user.displayName,
      memberIds: [user.id],
      isDemo: isDemo,
    );

    return res.when(
      success: (newConv) {
        state = state.copyWith(
          conversations: [newConv, ...state.conversations],
        );
        return newConv;
      },
      failure: (failure) {
        state = state.copyWith(errorMessage: failure.message);
        return null;
      },
    );
  }

  Future<ConversationDto?> createGroupChat(String title, List<String> memberIds) async {
    final res = await _repository.createConversation(
      type: 'group',
      title: title,
      memberIds: memberIds,
      isDemo: isDemo,
    );

    return res.when(
      success: (newConv) {
        state = state.copyWith(
          conversations: [newConv, ...state.conversations],
          toastMessage: 'Đã tạo nhóm "${newConv.title}"',
        );
        return newConv;
      },
      failure: (failure) {
        state = state.copyWith(errorMessage: failure.message);
        return null;
      },
    );
  }

  void markRead(String conversationId) {
    final updatedConvs = state.conversations.map((c) {
      if (c.id == conversationId) {
        return c.copyWith(unreadCount: 0);
      }
      return c;
    }).toList();
    state = state.copyWith(conversations: updatedConvs);
    _repository.markRead(conversationId, isDemo: isDemo);
  }

  void clearToast() {
    state = state.copyWith(clearToast: true);
  }
}
