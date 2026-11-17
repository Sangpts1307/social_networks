import 'media_attachment_model.dart';

class StoryModel {
  final String id;
  final String authorUserId;
  final String? authorName;
  final String? authorAvatar;
  final String body;
  final List<MediaAttachment> media;
  final String background;
  final String createdAt;
  final String expiresAt;

  const StoryModel({
    required this.id,
    required this.authorUserId,
    this.authorName,
    this.authorAvatar,
    required this.body,
    this.media = const [],
    this.background = '#1877F2',
    required this.createdAt,
    required this.expiresAt,
  });

  factory StoryModel.fromJson(Map<String, dynamic> json) {
    var rawMedia = <MediaAttachment>[];
    if (json['media'] is List) {
      rawMedia = (json['media'] as List)
          .map((m) => MediaAttachment.fromJson(m as Map<String, dynamic>))
          .toList();
    }

    return StoryModel(
      id: json['id'] as String? ?? '',
      authorUserId: json['author_user_id'] as String? ?? '',
      authorName: json['author_name'] as String?,
      authorAvatar: json['author_avatar'] as String?,
      body: json['body'] as String? ?? '',
      media: rawMedia,
      background: json['background'] as String? ?? '#1877F2',
      createdAt: json['created_at'] as String? ?? '',
      expiresAt: json['expires_at'] as String? ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'author_user_id': authorUserId,
    'body': body,
    'media': media.map((m) => m.toJson()).toList(),
    'background': background,
    'created_at': createdAt,
    'expires_at': expiresAt,
  };
}
