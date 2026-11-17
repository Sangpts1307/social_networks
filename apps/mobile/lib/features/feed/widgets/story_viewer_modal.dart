import 'package:flutter/material.dart';
import '../../../core/utils/date_time_formatter.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../data/models/story_model.dart';

class StoryViewerModal extends StatefulWidget {
  final StoryModel story;
  final VoidCallback onClose;

  const StoryViewerModal({
    super.key,
    required this.story,
    required this.onClose,
  });

  @override
  State<StoryViewerModal> createState() => _StoryViewerModalState();
}

class _StoryViewerModalState extends State<StoryViewerModal> with SingleTickerProviderStateMixin {
  late AnimationController _animController;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 5),
    );
    _animController.forward().whenComplete(() {
      if (mounted) widget.onClose();
    });
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final story = widget.story;
    final hasImage = story.media.isNotEmpty && story.media.first.uri.isNotEmpty;
    final bgColor = Color(int.tryParse(story.background.replaceFirst('#', '0xFF')) ?? 0xFF1877F2);

    return Scaffold(
      backgroundColor: Colors.black,
      body: SafeArea(
        child: Stack(
          children: [
            // Background Image or Solid Color
            Positioned.fill(
              child: hasImage
                  ? Image.network(
                      story.media.first.uri,
                      fit: BoxFit.contain,
                      errorBuilder: (_, _, _) => Container(color: bgColor),
                    )
                  : Container(
                      color: bgColor,
                      alignment: Alignment.center,
                      padding: const EdgeInsets.symmetric(horizontal: 32),
                      child: Text(
                        story.body,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
            ),

            // Text overlay if image present
            if (hasImage && story.body.isNotEmpty) ...[
              Positioned(
                bottom: 40,
                left: 20,
                right: 20,
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.black54,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    story.body,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
            ],

            // Top Progress bar
            Positioned(
              top: 8,
              left: 12,
              right: 12,
              child: AnimatedBuilder(
                animation: _animController,
                builder: (context, _) {
                  return LinearProgressIndicator(
                    value: _animController.value,
                    backgroundColor: Colors.white24,
                    valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                    minHeight: 3,
                  );
                },
              ),
            ),

            // Top header bar (Author info & close button)
            Positioned(
              top: 20,
              left: 16,
              right: 16,
              child: Row(
                children: [
                  AppAvatar(
                    name: story.authorName ?? 'Member',
                    avatarUrl: story.authorAvatar,
                    size: 36,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          story.authorName ?? 'Đồng nghiệp',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          DateTimeFormatter.formatRelativeTime(story.createdAt),
                          style: const TextStyle(color: Colors.white70, fontSize: 11),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: widget.onClose,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
