import 'media_attachment_model.dart';

class PostModel {
  final String id;
  final String channelId;
  final String authorUserId;
  final String? authorName;
  final String? authorAvatar;
  final String? authorRole;
  final String? title;
  final String bodyPlaintext;
  final String? publishedAt;
  final int reactionCount;
  final int commentCount;
  final String? myReaction; // 'like' | 'love' | 'sad' | 'angry'
  final bool isSaved;
  final bool isPinned;
  final String? pinExpiresAt;
  final String postType; // 'update' | 'news' | 'announcement'
  final String? imageUrl;
  final List<MediaAttachment> media;
  final String? editedAt;

  const PostModel({
    required this.id,
    required this.channelId,
    required this.authorUserId,
    this.authorName,
    this.authorAvatar,
    this.authorRole,
    this.title,
    required this.bodyPlaintext,
    this.publishedAt,
    this.reactionCount = 0,
    this.commentCount = 0,
    this.myReaction,
    this.isSaved = false,
    this.isPinned = false,
    this.pinExpiresAt,
    this.postType = 'update',
    this.imageUrl,
    this.media = const [],
    this.editedAt,
  });

  factory PostModel.fromJson(Map<String, dynamic> json) {
    var rawMedia = <MediaAttachment>[];
    if (json['demo_media'] is List) {
      rawMedia = (json['demo_media'] as List)
          .map((m) => MediaAttachment.fromJson(m as Map<String, dynamic>))
          .toList();
    } else if (json['media'] is List) {
      rawMedia = (json['media'] as List)
          .map((m) => MediaAttachment.fromJson(m as Map<String, dynamic>))
          .toList();
    }

    return PostModel(
      id: json['id'] as String? ?? json['_id'] as String? ?? '',
      channelId: json['channel_id'] as String? ?? json['channelId'] as String? ?? 'general',
      authorUserId: json['author_user_id'] as String? ?? json['authorId'] as String? ?? '',
      authorName: json['author_name'] as String?,
      authorAvatar: json['author_avatar'] as String?,
      authorRole: json['author_role'] as String?,
      title: json['title'] as String?,
      bodyPlaintext: json['body_plaintext'] as String? ?? json['body'] as String? ?? '',
      publishedAt: json['published_at'] as String? ?? json['created_at'] as String?,
      reactionCount: json['reaction_count'] is int ? json['reaction_count'] as int : int.tryParse(json['reaction_count']?.toString() ?? '0') ?? 0,
      commentCount: json['comment_count'] is int ? json['comment_count'] as int : int.tryParse(json['comment_count']?.toString() ?? '0') ?? 0,
      myReaction: json['my_reaction'] as String?,
      isSaved: json['is_saved'] == true || json['is_saved'] == 1 || json['is_saved'] == '1',
      isPinned: json['is_pinned'] == true || json['is_pinned'] == 1 || json['is_pinned'] == '1',
      pinExpiresAt: json['pin_expires_at'] as String?,
      postType: json['post_type'] as String? ?? 'update',
      imageUrl: json['image_url'] as String? ?? json['demo_image_url'] as String?,
      media: rawMedia,
      editedAt: json['edited_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'channel_id': channelId,
    'author_user_id': authorUserId,
    'title': title,
    'body_plaintext': bodyPlaintext,
    'published_at': publishedAt,
    'reaction_count': reactionCount,
    'comment_count': commentCount,
    'my_reaction': myReaction,
    'is_saved': isSaved,
    'is_pinned': isPinned,
    'pin_expires_at': pinExpiresAt,
    'post_type': postType,
    'image_url': imageUrl,
    'demo_media': media.map((m) => m.toJson()).toList(),
    'edited_at': editedAt,
  };

  PostModel copyWith({
    String? id,
    String? channelId,
    String? authorUserId,
    String? authorName,
    String? authorAvatar,
    String? authorRole,
    String? title,
    String? bodyPlaintext,
    String? publishedAt,
    int? reactionCount,
    int? commentCount,
    String? myReaction,
    bool clearReaction = false,
    bool? isSaved,
    bool? isPinned,
    String? pinExpiresAt,
    String? postType,
    String? imageUrl,
    List<MediaAttachment>? media,
    String? editedAt,
  }) {
    return PostModel(
      id: id ?? this.id,
      channelId: channelId ?? this.channelId,
      authorUserId: authorUserId ?? this.authorUserId,
      authorName: authorName ?? this.authorName,
      authorAvatar: authorAvatar ?? this.authorAvatar,
      authorRole: authorRole ?? this.authorRole,
      title: title ?? this.title,
      bodyPlaintext: bodyPlaintext ?? this.bodyPlaintext,
      publishedAt: publishedAt ?? this.publishedAt,
      reactionCount: reactionCount ?? this.reactionCount,
      commentCount: commentCount ?? this.commentCount,
      myReaction: clearReaction ? null : (myReaction ?? this.myReaction),
      isSaved: isSaved ?? this.isSaved,
      isPinned: isPinned ?? this.isPinned,
      pinExpiresAt: pinExpiresAt ?? this.pinExpiresAt,
      postType: postType ?? this.postType,
      imageUrl: imageUrl ?? this.imageUrl,
      media: media ?? this.media,
      editedAt: editedAt ?? this.editedAt,
    );
  }
}
