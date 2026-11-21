import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/string_utils.dart';
import '../../../data/models/knowledge_model.dart';
import '../../../data/repositories/learning_repository.dart';

class LibraryScreen extends ConsumerStatefulWidget {
  const LibraryScreen({super.key});

  @override
  ConsumerState<LibraryScreen> createState() => _LibraryScreenState();
}

class _LibraryScreenState extends ConsumerState<LibraryScreen> {
  List<KnowledgeItem> _items = [];
  bool _loading = true;
  String _search = '';

  @override
  void initState() {
    super.initState();
    _loadLibrary();
  }

  void _loadLibrary() async {
    final res = await ref.read(learningRepositoryProvider).getLibrary();
    if (mounted) {
      setState(() {
        _loading = false;
        res.when(
          success: (data) => _items = data,
          failure: (_) => _items = [],
        );
      });
    }
  }

  void _openDetail(KnowledgeItem item) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(item.title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                '${item.topicName}${item.categoryName != null ? ' · ${item.categoryName}' : ''}',
                style: const TextStyle(color: AppColors.primary, fontSize: 12, fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(height: 14),
            Text(
              item.summary ?? 'Không có bản tóm tắt cho tài liệu này.',
              style: const TextStyle(fontSize: 14, height: 1.4, color: AppColors.textPrimary),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Đóng'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _search.isEmpty
        ? _items
        : _items.where((it) => StringUtils.matches(it.title, _search) || StringUtils.matches(it.topicName, _search)).toList();

    return Scaffold(
      backgroundColor: AppColors.scaffoldBackground,
      appBar: AppBar(
        title: const Text('Thư viện tri thức'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Container(
                  color: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  child: TextField(
                    onChanged: (v) => setState(() => _search = v),
                    decoration: InputDecoration(
                      hintText: 'Tìm kiếm tài liệu, bài học...',
                      hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 14),
                      prefixIcon: const Icon(Icons.search, size: 20, color: AppColors.textSecondary),
                      filled: true,
                      fillColor: const Color(0xFFF1F5F9),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(20),
                        borderSide: BorderSide.none,
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: filtered.isEmpty
                      ? const Center(
                          child: Text(
                            'Chưa có tài liệu phù hợp',
                            style: TextStyle(color: AppColors.textSecondary),
                          ),
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.all(16),
                          itemCount: filtered.length,
                          separatorBuilder: (_, _) => const SizedBox(height: 10),
                          itemBuilder: (context, index) {
                            final item = filtered[index];

                            return Card(
                              elevation: 0,
                              color: Colors.white,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                                side: const BorderSide(color: AppColors.border),
                              ),
                              child: InkWell(
                                borderRadius: BorderRadius.circular(12),
                                onTap: () => _openDetail(item),
                                child: Padding(
                                  padding: const EdgeInsets.all(16),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                            decoration: BoxDecoration(
                                              color: AppColors.primaryLight,
                                              borderRadius: BorderRadius.circular(6),
                                            ),
                                            child: Text(
                                              item.topicName,
                                              style: const TextStyle(
                                                color: AppColors.primary,
                                                fontSize: 11,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                          ),
                                          if (item.categoryName != null) ...[
                                            const SizedBox(width: 8),
                                            Text(
                                              item.categoryName!,
                                              style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                                            ),
                                          ],
                                        ],
                                      ),
                                      const SizedBox(height: 8),
                                      Text(
                                        item.title,
                                        style: const TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 15,
                                          color: AppColors.textPrimary,
                                        ),
                                      ),
                                      if (item.summary != null) ...[
                                        const SizedBox(height: 6),
                                        Text(
                                          item.summary!,
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            fontSize: 13,
                                            color: AppColors.textSecondary,
                                            height: 1.35,
                                          ),
                                        ),
                                      ],
                                    ],
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                ),
              ],
            ),
    );
  }
}
