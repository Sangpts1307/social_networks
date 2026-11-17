import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../data/models/story_model.dart';

class StoriesRail extends StatelessWidget {
  final List<StoryModel> stories;
  final VoidCallback onCreateStory;
  final ValueChanged<StoryModel> onSelectStory;

  const StoriesRail({
    super.key,
    required this.stories,
    required this.onCreateStory,
    required this.onSelectStory,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      height: 195,
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        itemCount: stories.length + 1,
        separatorBuilder: (_, _) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          if (index == 0) {
            // "Tạo tin" card
            return InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: onCreateStory,
              child: Container(
                width: 110,
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  children: [
                    Expanded(
                      flex: 3,
                      child: Container(
                        decoration: const BoxDecoration(
                          color: Color(0xFFE2E8F0),
                          borderRadius: BorderRadius.vertical(top: Radius.circular(11)),
                        ),
                        child: const Center(
                          child: Icon(Icons.person, size: 40, color: Color(0xFF94A3B8)),
                        ),
                      ),
                    ),
                    Expanded(
                      flex: 2,
                      child: Stack(
                        clipBehavior: Clip.none,
                        alignment: Alignment.center,
                        children: [
                          Positioned(
                            top: -16,
                            child: Container(
                              width: 32,
                              height: 32,
                              decoration: BoxDecoration(
                                color: AppColors.primary,
                                shape: BoxShape.circle,
                                border: Border.all(color: Colors.white, width: 2),
                              ),
                              child: const Icon(Icons.add, color: Colors.white, size: 20),
                            ),
                          ),
                          const Positioned(
                            bottom: 8,
                            child: Text(
                              'Tạo tin',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textPrimary,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            );
          }

          final story = stories[index - 1];
          final hasImage = story.media.isNotEmpty && story.media.first.uri.isNotEmpty;

          return InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: () => onSelectStory(story),
            child: Container(
              width: 110,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                color: Color(
                  int.tryParse(story.background.replaceFirst('#', '0xFF')) ?? 0xFF1877F2,
                ),
                image: hasImage
                    ? DecorationImage(
                        image: NetworkImage(story.media.first.uri),
                        fit: BoxFit.cover,
                      )
                    : null,
                boxShadow: const [
                  BoxShadow(
                    color: Color(0x14000000),
                    blurRadius: 6,
                    offset: Offset(0, 2),
                  ),
                ],
              ),
              child: Stack(
                children: [
                  Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(12),
                      gradient: const LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.black26,
                          Colors.transparent,
                          Colors.black54,
                        ],
                      ),
                    ),
                  ),
                  Positioned(
                    top: 8,
                    left: 8,
                    child: Container(
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(color: AppColors.primary, width: 2),
                      ),
                      child: AppAvatar(
                        name: story.authorName ?? 'Member',
                        avatarUrl: story.authorAvatar,
                        size: 32,
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: 8,
                    left: 8,
                    right: 8,
                    child: Text(
                      story.authorName ?? 'Đồng nghiệp',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        shadows: [Shadow(blurRadius: 4, color: Colors.black)],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
