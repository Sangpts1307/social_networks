class ChannelModel {
  final String id;
  final String name;
  final String? description;
  final String membershipPolicy; // 'open' | 'managed'
  final String visibility; // 'organization' | 'members'
  final String? ownerUserId;

  const ChannelModel({
    required this.id,
    required this.name,
    this.description,
    this.membershipPolicy = 'open',
    this.visibility = 'organization',
    this.ownerUserId,
  });

  factory ChannelModel.fromJson(Map<String, dynamic> json) {
    return ChannelModel(
      id: json['id'] as String? ?? json['code'] as String? ?? '',
      name: json['name'] as String? ?? '',
      description: json['description'] as String?,
      membershipPolicy: json['membership_policy'] as String? ?? 'open',
      visibility: json['visibility'] as String? ?? 'organization',
      ownerUserId: json['owner_user_id'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'description': description,
    'membership_policy': membershipPolicy,
    'visibility': visibility,
    'owner_user_id': ownerUserId,
  };
}

class ChannelMemberModel {
  final String id;
  final String userId;
  final String memberRole; // 'owner' | 'moderator' | 'member'
  final String joinedAt;

  const ChannelMemberModel({
    required this.id,
    required this.userId,
    this.memberRole = 'member',
    required this.joinedAt,
  });

  factory ChannelMemberModel.fromJson(Map<String, dynamic> json) {
    return ChannelMemberModel(
      id: json['id'] as String? ?? '',
      userId: json['user_id'] as String? ?? '',
      memberRole: json['member_role'] as String? ?? 'member',
      joinedAt: json['joined_at'] as String? ?? '',
    );
  }
}
