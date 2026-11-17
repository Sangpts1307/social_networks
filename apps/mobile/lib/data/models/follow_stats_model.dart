class FollowStatsModel {
  final int followingCount;
  final int followersCount;

  const FollowStatsModel({
    this.followingCount = 0,
    this.followersCount = 0,
  });

  factory FollowStatsModel.fromJson(Map<String, dynamic> json) {
    return FollowStatsModel(
      followingCount: json['following_count'] as int? ?? 0,
      followersCount: json['followers_count'] as int? ?? 0,
    );
  }
}
