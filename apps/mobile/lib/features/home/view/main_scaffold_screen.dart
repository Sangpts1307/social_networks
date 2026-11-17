import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../channels/view/channels_view.dart';
import '../../chat/view/chat_list_view.dart';
import '../../feed/view/feed_view.dart';
import '../../menu/view/menu_view.dart';
import '../../notifications/view/notifications_view.dart';

class MainScaffoldScreen extends ConsumerStatefulWidget {
  const MainScaffoldScreen({super.key});

  @override
  ConsumerState<MainScaffoldScreen> createState() => _MainScaffoldScreenState();
}

class _MainScaffoldScreenState extends ConsumerState<MainScaffoldScreen> {
  int _currentIndex = 0;

  void _onTabSelected(int index) {
    setState(() => _currentIndex = index);
  }

  @override
  Widget build(BuildContext context) {
    final List<Widget> pages = [
      const FeedView(),
      ChannelsView(
        onSwitchToFeed: () => setState(() => _currentIndex = 0),
      ),
      const ChatListView(),
      const NotificationsView(),
      MenuView(
        onSwitchToChannels: () => setState(() => _currentIndex = 1),
        onSwitchToSaved: () {
          setState(() => _currentIndex = 0);
          // Can filter by saved
        },
      ),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: pages,
      ),
      bottomNavigationBar: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(
            top: BorderSide(color: AppColors.border, width: 0.5),
          ),
        ),
        child: SafeArea(
          child: NavigationBar(
            selectedIndex: _currentIndex,
            onDestinationSelected: _onTabSelected,
            backgroundColor: Colors.white,
            surfaceTintColor: Colors.transparent,
            indicatorColor: AppColors.primaryLight,
            elevation: 0,
            destinations: const [
              NavigationDestination(
                icon: Icon(Icons.home_outlined),
                selectedIcon: Icon(Icons.home, color: AppColors.primary),
                label: 'Bảng tin',
              ),
              NavigationDestination(
                icon: Icon(Icons.group_outlined),
                selectedIcon: Icon(Icons.group, color: AppColors.primary),
                label: 'Kênh',
              ),
              NavigationDestination(
                icon: Icon(Icons.chat_bubble_outline),
                selectedIcon: Icon(Icons.chat_bubble, color: AppColors.primary),
                label: 'Tin nhắn',
              ),
              NavigationDestination(
                icon: Icon(Icons.notifications_none),
                selectedIcon: Icon(Icons.notifications, color: AppColors.primary),
                label: 'Thông báo',
              ),
              NavigationDestination(
                icon: Icon(Icons.menu),
                selectedIcon: Icon(Icons.menu, color: AppColors.primary),
                label: 'Menu',
              ),
            ],
          ),
        ),
      ),
    );
  }
}
