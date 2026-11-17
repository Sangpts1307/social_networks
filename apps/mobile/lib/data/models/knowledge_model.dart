class KnowledgeItem {
  final String id;
  final String title;
  final String? summary;
  final String topicName;
  final String? categoryName;

  const KnowledgeItem({
    required this.id,
    required this.title,
    this.summary,
    required this.topicName,
    this.categoryName,
  });

  factory KnowledgeItem.fromJson(Map<String, dynamic> json) {
    return KnowledgeItem(
      id: json['id'] as String? ?? '',
      title: json['title'] as String? ?? '',
      summary: json['summary'] as String?,
      topicName: json['topic_name'] as String? ?? 'General',
      categoryName: json['category_name'] as String?,
    );
  }
}

class PageResponse<T> {
  final List<T> data;
  final String? nextCursor;

  const PageResponse({
    required this.data,
    this.nextCursor,
  });
}
