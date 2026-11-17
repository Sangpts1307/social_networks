class NotificationModel {
  final String id;
  final String? organizationId;
  final String? recipientUserId;
  final String notificationType;
  final String title;
  final String body;
  final dynamic payload;
  final String? readAt;
  final String? seenAt;
  final String? createdAt;

  const NotificationModel({
    required this.id,
    this.organizationId,
    this.recipientUserId,
    this.notificationType = 'announcement',
    required this.title,
    required this.body,
    this.payload,
    this.readAt,
    this.seenAt,
    this.createdAt,
  });

  bool get isRead => readAt != null && readAt!.isNotEmpty;

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'] as String? ?? '',
      organizationId: json['organization_id'] as String?,
      recipientUserId: json['recipient_user_id'] as String?,
      notificationType: json['notification_type'] as String? ?? 'announcement',
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ?? '',
      payload: json['payload_json'] ?? json['payload'],
      readAt: json['read_at'] as String?,
      seenAt: json['seen_at'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'organization_id': organizationId,
    'recipient_user_id': recipientUserId,
    'notification_type': notificationType,
    'title': title,
    'body': body,
    'payload_json': payload,
    'read_at': readAt,
    'seen_at': seenAt,
    'created_at': createdAt,
  };

  NotificationModel copyWith({
    String? id,
    String? organizationId,
    String? recipientUserId,
    String? notificationType,
    String? title,
    String? body,
    dynamic payload,
    String? readAt,
    String? seenAt,
    String? createdAt,
  }) {
    return NotificationModel(
      id: id ?? this.id,
      organizationId: organizationId ?? this.organizationId,
      recipientUserId: recipientUserId ?? this.recipientUserId,
      notificationType: notificationType ?? this.notificationType,
      title: title ?? this.title,
      body: body ?? this.body,
      payload: payload ?? this.payload,
      readAt: readAt ?? this.readAt,
      seenAt: seenAt ?? this.seenAt,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}
