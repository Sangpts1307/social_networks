import 'user_model.dart';

class SessionModel {
  final String accessToken;
  final String? expiresAt;
  final UserModel user;

  const SessionModel({
    required this.accessToken,
    this.expiresAt,
    required this.user,
  });

  factory SessionModel.fromJson(Map<String, dynamic> json) {
    return SessionModel(
      accessToken: json['access_token'] as String? ?? json['token'] as String? ?? '',
      expiresAt: json['expires_at'] as String?,
      user: UserModel.fromJson(
        json['user'] is Map<String, dynamic> ? json['user'] as Map<String, dynamic> : json,
      ),
    );
  }

  Map<String, dynamic> toJson() => {
    'access_token': accessToken,
    'expires_at': expiresAt,
    'user': user.toJson(),
  };
}
