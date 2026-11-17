class UserModel {
  final String id;
  final String organizationId;
  final String email;
  final String displayName;
  final bool mustChangePassword;
  final List<String> permissions;
  final String? avatarUrl;
  final String? role;

  const UserModel({
    required this.id,
    this.organizationId = '',
    required this.email,
    required this.displayName,
    this.mustChangePassword = false,
    this.permissions = const [],
    this.avatarUrl,
    this.role,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as String? ?? json['_id'] as String? ?? '',
      organizationId: json['organization_id'] as String? ?? json['organizationId'] as String? ?? '',
      email: json['email'] as String? ?? '',
      displayName: json['display_name'] as String? ?? json['displayName'] as String? ?? json['name'] as String? ?? '',
      mustChangePassword: json['must_change_password'] == true || json['must_change_password'] == 1,
      permissions: (json['permissions'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? const [],
      avatarUrl: json['avatar'] as String? ?? json['avatar_url'] as String?,
      role: json['role'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'organization_id': organizationId,
    'email': email,
    'display_name': displayName,
    'must_change_password': mustChangePassword,
    'permissions': permissions,
    'avatar': avatarUrl,
    'role': role,
  };

  UserModel copyWith({
    String? id,
    String? organizationId,
    String? email,
    String? displayName,
    bool? mustChangePassword,
    List<String>? permissions,
    String? avatarUrl,
    String? role,
  }) {
    return UserModel(
      id: id ?? this.id,
      organizationId: organizationId ?? this.organizationId,
      email: email ?? this.email,
      displayName: displayName ?? this.displayName,
      mustChangePassword: mustChangePassword ?? this.mustChangePassword,
      permissions: permissions ?? this.permissions,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      role: role ?? this.role,
    );
  }
}
