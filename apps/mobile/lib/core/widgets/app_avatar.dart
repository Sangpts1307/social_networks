import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

class AppAvatar extends StatelessWidget {
  final String? avatarUrl;
  final String name;
  final double size;
  final Color? backgroundColor;

  const AppAvatar({
    super.key,
    this.avatarUrl,
    required this.name,
    this.size = 40,
    this.backgroundColor,
  });

  String _getInitials(String name) {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return 'U';
    if (parts.length == 1) return parts[0][0].toUpperCase();
    return '${parts[0][0]}${parts[parts.length - 1][0]}'.toUpperCase();
  }

  Color _getDeterministicColor(String name) {
    final colors = [
      AppColors.primary,
      const Color(0xFF0891B2), // cyan
      const Color(0xFFD97706), // amber
      const Color(0xFFDB2777), // pink
      const Color(0xFF7C3AED), // purple
      const Color(0xFF059669), // emerald
    ];
    var hash = 0;
    for (var i = 0; i < name.length; i++) {
      hash = name.codeUnitAt(i) + ((hash << 5) - hash);
    }
    return colors[hash.abs() % colors.length];
  }

  @override
  Widget build(BuildContext context) {
    final bg = backgroundColor ?? _getDeterministicColor(name);
    final initials = _getInitials(name);

    if (avatarUrl != null && avatarUrl!.isNotEmpty) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(size / 2),
        child: Image.network(
          avatarUrl!,
          width: size,
          height: size,
          fit: BoxFit.cover,
          errorBuilder: (context, error, stackTrace) => _buildFallback(bg, initials),
        ),
      );
    }

    return _buildFallback(bg, initials);
  }

  Widget _buildFallback(Color bg, String initials) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: bg,
        shape: BoxShape.circle,
      ),
      alignment: Alignment.center,
      child: Text(
        initials,
        style: TextStyle(
          color: Colors.white,
          fontSize: size * 0.4,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }
}
