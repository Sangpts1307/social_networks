class DirectoryUser {
  final String id;
  final String displayName;
  final String? jobTitle;
  final String? avatarKey;

  const DirectoryUser({
    required this.id,
    required this.displayName,
    this.jobTitle,
    this.avatarKey,
  });

  factory DirectoryUser.fromJson(Map<String, dynamic> json) {
    return DirectoryUser(
      id: json['id'] as String? ?? json['_id'] as String? ?? '',
      displayName: json['display_name'] as String? ?? json['name'] as String? ?? '',
      jobTitle: json['job_title'] as String?,
      avatarKey: json['avatar_key'] as String? ?? json['avatar'] as String?,
    );
  }
}

class ChatMessageDto {
  final String id;
  final String conversationId;
  final String senderUserId;
  final String bodyPlaintext;
  final String sentAt;

  const ChatMessageDto({
    required this.id,
    required this.conversationId,
    required this.senderUserId,
    required this.bodyPlaintext,
    required this.sentAt,
  });

  factory ChatMessageDto.fromJson(Map<String, dynamic> json) {
    return ChatMessageDto(
      id: json['id'] as String? ?? json['_id'] as String? ?? '',
      conversationId: json['conversationId'] as String? ?? json['conversation_id'] as String? ?? '',
      senderUserId: json['senderUserId'] as String? ?? json['sender_user_id'] as String? ?? '',
      bodyPlaintext: json['bodyPlaintext'] as String? ?? json['body'] as String? ?? '',
      sentAt: json['sentAt'] as String? ?? json['created_at'] as String? ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'conversationId': conversationId,
    'senderUserId': senderUserId,
    'bodyPlaintext': bodyPlaintext,
    'sentAt': sentAt,
  };
}

class ConversationMemberDto {
  final String userId;
  final String memberRole;
  final String joinedAt;

  const ConversationMemberDto({
    required this.userId,
    this.memberRole = 'member',
    required this.joinedAt,
  });

  factory ConversationMemberDto.fromJson(Map<String, dynamic> json) {
    return ConversationMemberDto(
      userId: json['userId'] as String? ?? json['user_id'] as String? ?? '',
      memberRole: json['memberRole'] as String? ?? json['member_role'] as String? ?? 'member',
      joinedAt: json['joinedAt'] as String? ?? json['joined_at'] as String? ?? '',
    );
  }
}

class ConversationDto {
  final String id;
  final String type; // 'direct' | 'group'
  final String? title;
  final String? directKey;
  final String createdByUserId;
  final int memberCount;
  final int unreadCount;
  final ChatMessageDto? lastMessage;
  final String? createdAt;

  const ConversationDto({
    required this.id,
    required this.type,
    this.title,
    this.directKey,
    required this.createdByUserId,
    this.memberCount = 2,
    this.unreadCount = 0,
    this.lastMessage,
    this.createdAt,
  });

  factory ConversationDto.fromJson(Map<String, dynamic> json) {
    ChatMessageDto? msg;
    if (json['lastMessage'] is Map<String, dynamic>) {
      msg = ChatMessageDto.fromJson(json['lastMessage'] as Map<String, dynamic>);
    }

    return ConversationDto(
      id: json['id'] as String? ?? json['_id'] as String? ?? '',
      type: json['type'] as String? ?? 'direct',
      title: json['title'] as String?,
      directKey: json['directKey'] as String?,
      createdByUserId: json['createdByUserId'] as String? ?? '',
      memberCount: json['memberCount'] is int ? json['memberCount'] as int : 2,
      unreadCount: json['unread_count'] is int ? json['unread_count'] as int : 0,
      lastMessage: msg,
      createdAt: json['createdAt'] as String?,
    );
  }

  ConversationDto copyWith({
    String? id,
    String? type,
    String? title,
    String? directKey,
    String? createdByUserId,
    int? memberCount,
    int? unreadCount,
    ChatMessageDto? lastMessage,
    String? createdAt,
  }) {
    return ConversationDto(
      id: id ?? this.id,
      type: type ?? this.type,
      title: title ?? this.title,
      directKey: directKey ?? this.directKey,
      createdByUserId: createdByUserId ?? this.createdByUserId,
      memberCount: memberCount ?? this.memberCount,
      unreadCount: unreadCount ?? this.unreadCount,
      lastMessage: lastMessage ?? this.lastMessage,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}
