class MediaAttachment {
  final String id;
  final String uri;
  final String type; // 'image' | 'video'
  final String? fileName;
  final int? duration;

  const MediaAttachment({
    required this.id,
    required this.uri,
    this.type = 'image',
    this.fileName,
    this.duration,
  });

  factory MediaAttachment.fromJson(Map<String, dynamic> json) {
    return MediaAttachment(
      id: json['id'] as String? ?? '',
      uri: json['uri'] as String? ?? '',
      type: json['type'] as String? ?? 'image',
      fileName: json['fileName'] as String?,
      duration: json['duration'] as int?,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'uri': uri,
    'type': type,
    'fileName': fileName,
    'duration': duration,
  };
}
