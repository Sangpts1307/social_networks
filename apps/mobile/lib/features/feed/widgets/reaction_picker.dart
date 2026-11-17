import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';

class ReactionPicker extends StatelessWidget {
  final ValueChanged<String> onSelected;

  const ReactionPicker({super.key, required this.onSelected});

  static const List<Map<String, dynamic>> reactions = [
    {'type': 'like', 'label': 'Thích', 'emoji': '👍', 'color': AppColors.reactionLike},
    {'type': 'love', 'label': 'Yêu thích', 'emoji': '❤️', 'color': AppColors.reactionLove},
    {'type': 'sad', 'label': 'Buồn', 'emoji': '😢', 'color': AppColors.reactionSad},
    {'type': 'angry', 'label': 'Phẫn nộ', 'emoji': '😡', 'color': AppColors.reactionAngry},
  ];

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(30),
        boxShadow: const [
          BoxShadow(
            color: Color(0x26000000),
            blurRadius: 16,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: reactions.map((r) {
          final type = r['type'] as String;
          final emoji = r['emoji'] as String;
          return Padding(
            padding: const EdgeInsets.symmetric(horizontal: 6),
            child: InkWell(
              borderRadius: BorderRadius.circular(20),
              onTap: () => onSelected(type),
              child: Padding(
                padding: const EdgeInsets.all(4),
                child: Text(
                  emoji,
                  style: const TextStyle(fontSize: 28),
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}
