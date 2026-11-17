class DateTimeFormatter {
  const DateTimeFormatter._();

  /// Chuyển đổi DateTime / ISO string sang relative time tiếng Việt
  /// Ví dụ: "Vừa xong", "5 phút trước", "1 giờ trước", "1 ngày trước".
  static String formatRelativeTime(dynamic time) {
    DateTime? dt;
    if (time is DateTime) {
      dt = time;
    } else if (time is String && time.isNotEmpty) {
      dt = DateTime.tryParse(time);
    }
    if (dt == null) return '';

    final localDt = dt.toLocal();
    final now = DateTime.now();
    final difference = now.difference(localDt);

    if (difference.isNegative || difference.inSeconds < 60) {
      return 'Vừa xong';
    }
    if (difference.inMinutes < 60) {
      return '${difference.inMinutes} phút trước';
    }
    if (difference.inHours < 24) {
      return '${difference.inHours} giờ trước';
    }
    if (difference.inDays < 30) {
      return '${difference.inDays} ngày trước';
    }
    if (difference.inDays < 365) {
      return '${difference.inDays ~/ 30} tháng trước';
    }
    return '${difference.inDays ~/ 365} năm trước';
  }

  /// Định dạng giờ dạng "HH:mm · dd/MM/yyyy"
  static String formatDateTime(dynamic time) {
    DateTime? dt;
    if (time is DateTime) {
      dt = time;
    } else if (time is String && time.isNotEmpty) {
      dt = DateTime.tryParse(time);
    }
    if (dt == null) return '';

    final localDt = dt.toLocal();
    final hourStr = localDt.hour.toString().padLeft(2, '0');
    final minStr = localDt.minute.toString().padLeft(2, '0');
    final dayStr = localDt.day.toString().padLeft(2, '0');
    final monthStr = localDt.month.toString().padLeft(2, '0');
    return '$hourStr:$minStr · $dayStr/$monthStr/${localDt.year}';
  }
}
