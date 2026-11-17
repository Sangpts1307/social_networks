import 'package:flutter/material.dart';

abstract class AppColors {
  /// Primary brand blue (#1877F2)
  static const Color primary = Color(0xFF1877F2);
  static const Color primaryDark = Color(0xFF0C63D4);
  static const Color primaryLight = Color(0xFFE7F3FF);

  /// Background neutral (#F0F2F5)
  static const Color scaffoldBackground = Color(0xFFF0F2F5);
  static const Color cardBackground = Color(0xFFFFFFFF);

  /// Typography
  static const Color textPrimary = Color(0xFF0F172A);
  static const Color textSecondary = Color(0xFF64748B);
  static const Color textMuted = Color(0xFF94A3B8);

  /// Borders & Dividers
  static const Color border = Color(0xFFE2E8F0);
  static const Color divider = Color(0xFFF1F5F9);
  static const Color inputBackground = Color(0xFFF8FAFC);

  /// Status & Feedback
  static const Color success = Color(0xFF10B981);
  static const Color error = Color(0xFFEF4444);
  static const Color warning = Color(0xFFF59E0B);
  static const Color noticeBg = Color(0xFFFEF2F2);
  static const Color noticeBorder = Color(0xFFFCA5A5);

  /// Reactions
  static const Color reactionLike = Color(0xFF1877F2);
  static const Color reactionLove = Color(0xFFEF4444);
  static const Color reactionSad = Color(0xFFF59E0B);
  static const Color reactionAngry = Color(0xFFEA580C);

  /// Navigation
  static const Color navActive = Color(0xFF1877F2);
  static const Color navInactive = Color(0xFF64748B);
}
