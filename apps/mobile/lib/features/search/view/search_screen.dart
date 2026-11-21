import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/string_utils.dart';
import '../../../core/widgets/app_avatar.dart';
import '../../../data/models/channel_model.dart';
import '../../../data/models/chat_model.dart';
import '../../../data/models/post_model.dart';
import '../../chat/view/chat_detail_screen.dart';
import '../../chat/view_model/chat_view_model.dart';
import '../../feed/view_model/feed_view_model.dart';
import '../../feed/widgets/post_card.dart';

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> with SingleTickerProviderStateMixin {
  final _searchController = TextEditingController();
  late TabController _tabController;
  String _query = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final allPosts = ref.watch(feedViewModelProvider).posts;
    final allChannels = ref.watch(feedViewModelProvider).channels;
    final allUsers = ref.watch(chatViewModelProvider).directory;

    final matchedPosts = _query.isEmpty
        ? <PostModel>[]
        : allPosts.where((p) => StringUtils.matches('${p.title ?? ''} ${p.bodyPlaintext}', _query)).toList();

    final matchedChannels = _query.isEmpty
        ? <ChannelModel>[]
        : allChannels.where((c) => StringUtils.matches('${c.name} ${c.description ?? ''}', _query)).toList();

    final matchedUsers = _query.isEmpty
        ? <DirectoryUser>[]
        : allUsers.where((u) => StringUtils.matches('${u.displayName} ${u.jobTitle ?? ''}', _query)).toList();

    return Scaffold(
      backgroundColor: AppColors.scaffoldBackground,
      appBar: AppBar(
        titleSpacing: 0,
        title: TextField(
          controller: _searchController,
          autofocus: true,
          onChanged: (v) => setState(() => _query = v.trim()),
          decoration: InputDecoration(
            hintText: 'Tìm kiếm bài viết, kênh, đồng nghiệp...',
            hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 15),
            border: InputBorder.none,
            suffixIcon: _query.isNotEmpty
                ? IconButton(
                    icon: const Icon(Icons.clear, size: 20),
                    onPressed: () {
                      _searchController.clear();
                      setState(() => _query = '');
                    },
                  )
                : null,
          ),
        ),
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.primary,
          unselectedLabelColor: AppColors.textSecondary,
          indicatorColor: AppColors.primary,
          tabs: [
            Tab(text: 'Bài viết (${matchedPosts.length})'),
            Tab(text: 'Kênh (${matchedChannels.length})'),
            Tab(text: 'Đồng nghiệp (${matchedUsers.length})'),
          ],
        ),
      ),
      body: _query.isEmpty
          ? const Center(
              child: Text(
                'Nhập từ khóa để tìm kiếm trong không gian Openhello',
                style: TextStyle(color: AppColors.textSecondary),
              ),
            )
          : TabBarView(
              controller: _tabController,
              children: [
                // Posts Tab
                matchedPosts.isEmpty
                    ? const Center(child: Text('Không tìm thấy bài viết nào phù hợp'))
                    : ListView.builder(
                        itemCount: matchedPosts.length,
                        itemBuilder: (context, index) => PostCard(post: matchedPosts[index]),
                      ),

                // Channels Tab
                matchedChannels.isEmpty
                    ? const Center(child: Text('Không tìm thấy kênh nào phù hợp'))
                    : ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: matchedChannels.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final ch = matchedChannels[index];
                          return Card(
                            elevation: 0,
                            color: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                              side: const BorderSide(color: AppColors.border),
                            ),
                            child: ListTile(
                              leading: const Icon(Icons.tag, color: AppColors.primary),
                              title: Text(ch.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                              subtitle: ch.description != null ? Text(ch.description!) : null,
                              onTap: () {
                                ref.read(feedViewModelProvider.notifier).selectChannel(ch.id);
                                Navigator.of(context).pop();
                              },
                            ),
                          );
                        },
                      ),

                // Members Tab
                matchedUsers.isEmpty
                    ? const Center(child: Text('Không tìm thấy người phù hợp'))
                    : ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: matchedUsers.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final u = matchedUsers[index];
                          return Card(
                            elevation: 0,
                            color: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                              side: const BorderSide(color: AppColors.border),
                            ),
                            child: ListTile(
                              leading: AppAvatar(name: u.displayName, avatarUrl: u.avatarKey),
                              title: Text(u.displayName, style: const TextStyle(fontWeight: FontWeight.bold)),
                              subtitle: u.jobTitle != null ? Text(u.jobTitle!) : null,
                              trailing: IconButton(
                                icon: const Icon(Icons.chat_bubble_outline, color: AppColors.primary),
                                onPressed: () async {
                                  final conv = await ref
                                      .read(chatViewModelProvider.notifier)
                                      .createDirectChat(u);
                                  if (conv != null && context.mounted) {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => ChatDetailScreen(conversation: conv),
                                      ),
                                    );
                                  }
                                },
                              ),
                            ),
                          );
                        },
                      ),
              ],
            ),
    );
  }
}
