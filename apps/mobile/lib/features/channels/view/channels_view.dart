import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../data/models/channel_model.dart';
import '../../feed/view_model/feed_view_model.dart';
import '../view_model/channels_view_model.dart';
import 'create_channel_dialog.dart';

class ChannelsView extends ConsumerStatefulWidget {
  final VoidCallback? onSwitchToFeed;

  const ChannelsView({super.key, this.onSwitchToFeed});

  @override
  ConsumerState<ChannelsView> createState() => _ChannelsViewState();
}

class _ChannelsViewState extends ConsumerState<ChannelsView> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _openCreateDialog() {
    showDialog(context: context, builder: (_) => const CreateChannelDialog());
  }

  void _navigateToChannelPosts(ChannelModel channel) {
    ref.read(feedViewModelProvider.notifier).selectChannel(channel.id);
    widget.onSwitchToFeed?.call();
  }

  @override
  Widget build(BuildContext context) {
    final channelsState = ref.watch(channelsViewModelProvider);

    // Toast listener
    ref.listen(channelsViewModelProvider.select((s) => s.toastMessage), (
      prev,
      next,
    ) {
      if (next != null && next.isNotEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(next), duration: const Duration(seconds: 2)),
        );
        ref.read(channelsViewModelProvider.notifier).clearToast();
      }
    });

    return Scaffold(
      backgroundColor: AppColors.scaffoldBackground,
      appBar: AppBar(
        title: const Text('Kênh thảo luận'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add, size: 26),
            tooltip: 'Tạo kênh',
            onPressed: _openCreateDialog,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () =>
            ref.read(channelsViewModelProvider.notifier).loadChannels(),
        child: ListView(
          padding: const EdgeInsets.symmetric(vertical: 12),
          children: [
            // Search Input
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: TextField(
                controller: _searchController,
                onChanged: (v) => ref
                    .read(channelsViewModelProvider.notifier)
                    .setSearchQuery(v),
                decoration: InputDecoration(
                  hintText: 'Tìm kiếm kênh thảo luận...',
                  hintStyle: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 14,
                  ),
                  prefixIcon: const Icon(
                    Icons.search,
                    size: 20,
                    color: AppColors.textSecondary,
                  ),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 18),
                          onPressed: () {
                            _searchController.clear();
                            ref
                                .read(channelsViewModelProvider.notifier)
                                .setSearchQuery('');
                          },
                        )
                      : null,
                  filled: true,
                  fillColor: Colors.white,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 10,
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: const BorderSide(color: AppColors.border),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: const BorderSide(color: AppColors.primary),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Section 1: Kênh đã tham gia
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              child: Text(
                'Kênh đã tham gia (${channelsState.joinedChannels.length})',
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textSecondary,
                  letterSpacing: 0.5,
                ),
              ),
            ),
            if (channelsState.joinedChannels.isEmpty) ...[
              const Padding(
                padding: EdgeInsets.all(16),
                child: Text(
                  'Bạn chưa tham gia kênh nào.',
                  style: TextStyle(color: AppColors.textMuted),
                ),
              ),
            ] else ...[
              ...channelsState.joinedChannels.map(
                (ch) => _buildChannelTile(ch, true),
              ),
            ],

            const SizedBox(height: 16),

            // Section 2: Khám phá kênh mới
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              child: Text(
                'Khám phá kênh mới (${channelsState.exploreChannels.length})',
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textSecondary,
                  letterSpacing: 0.5,
                ),
              ),
            ),
            if (channelsState.exploreChannels.isEmpty) ...[
              const Padding(
                padding: EdgeInsets.all(16),
                child: Text(
                  'Đã tham gia tất cả các kênh!',
                  style: TextStyle(color: AppColors.textMuted),
                ),
              ),
            ] else ...[
              ...channelsState.exploreChannels.map(
                (ch) => _buildChannelTile(ch, false),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildChannelTile(ChannelModel channel, bool isJoined) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: const [
          BoxShadow(
            color: Color(0x06000000),
            blurRadius: 4,
            offset: Offset(0, 1),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: ListTile(
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 6,
          ),
          leading: Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: isJoined
                  ? AppColors.primaryLight
                  : const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Center(
              child: Text(
                '#',
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: isJoined ? AppColors.primary : AppColors.textSecondary,
                ),
              ),
            ),
          ),
          title: Text(
            channel.name,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
          ),
          subtitle: channel.description != null
              ? Text(
                  channel.description!,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13,
                    color: AppColors.textSecondary,
                  ),
                )
              : null,
          trailing: OutlinedButton(
            style: OutlinedButton.styleFrom(
              backgroundColor: isJoined
                  ? Colors.transparent
                  : AppColors.primary,
              foregroundColor: isJoined
                  ? AppColors.textSecondary
                  : Colors.white,
              side: BorderSide(
                color: isJoined ? AppColors.border : AppColors.primary,
              ),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 14),
            ),
            onPressed: () {
              ref
                  .read(channelsViewModelProvider.notifier)
                  .toggleJoin(channel.id);
            },
            child: Text(
              isJoined ? 'Đã tham gia' : 'Tham gia',
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
            ),
          ),
          onTap: () => _navigateToChannelPosts(channel),
        ),
      ),
    );
  }
}
