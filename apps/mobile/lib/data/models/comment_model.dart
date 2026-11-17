class CommentModel {
  final String id;
  final String postId;
  final String? parentCommentId;
  final String authorUserId;
  final String? authorName;
  final String? authorAvatar;
  final String bodyPlaintext;
  final String createdAt;
  final String? editedAt;

  const CommentModel({
    required this.id,
    required this.postId,
    this.parentCommentId,
    required this.authorUserId,
    this.authorName,
    this.authorAvatar,
    required this.bodyPlaintext,
    required this.createdAt,
    this.editedAt,
  });

  factory CommentModel.fromJson(Map<String, dynamic> json) {
    return CommentModel(
      id: json['id'] as String? ?? json['_id'] as String? ?? '',
      postId: json['post_id'] as String? ?? json['postId'] as String? ?? '',
      parentCommentId: json['parent_comment_id'] as String? ?? json['parentCommentId'] as String?,
      authorUserId: json['author_user_id'] as String? ?? json['authorId'] as String? ?? '',
      authorName: json['author_name'] as String?,
      authorAvatar: json['author_avatar'] as String?,
      bodyPlaintext: json['body_plaintext'] as String? ?? json['body'] as String? ?? '',
      createdAt: json['created_at'] as String? ?? json['createdAt'] as String? ?? '',
      editedAt: json['edited_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'post_id': postId,
    'parent_comment_id': parentCommentId,
    'author_user_id': authorUserId,
    'body_plaintext': bodyPlaintext,
    'created_at': createdAt,
    'edited_at': editedAt,
  };
}
