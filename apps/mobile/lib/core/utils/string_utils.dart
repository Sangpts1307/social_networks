class StringUtils {
  const StringUtils._();

  static const _vietnameseMap = {
    'a': 'áàảãạăắằẳẵặâấầẩẫậ',
    'A': 'ÁÀẢÃẠĂẮẰẲẴẶÂẤẦẨẪẬ',
    'd': 'đ',
    'D': 'Đ',
    'e': 'éèẻẽẹêếềểễệ',
    'E': 'ÉÈẺẼẸÊẾỀỂỄỆ',
    'i': 'íìỉĩị',
    'I': 'ÍÌỈĨỊ',
    'o': 'óòỏõọôốồổỗộơớờởỡợ',
    'O': 'ÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢ',
    'u': 'úùủũụưứừửữự',
    'U': 'ÚÙỦŨỤƯỨỪỬỮỰ',
    'y': 'ýỳỷỹỵ',
    'Y': 'ÝỲỶỸỴ',
  };

  /// Chuyển chuỗi tiếng Việt có dấu thành không dấu và chữ thường
  static String normalize(String value) {
    var result = value.trim().toLowerCase();
    for (final entry in _vietnameseMap.entries) {
      for (var i = 0; i < entry.value.length; i++) {
        result = result.replaceAll(entry.value[i].toLowerCase(), entry.key.toLowerCase());
      }
    }
    return result;
  }

  /// Kiểm tra chuỗi [target] có chứa tất cả các từ trong query [search] không
  static bool matches(String target, String search) {
    final normTarget = normalize(target);
    final normSearch = normalize(search);
    final words = normSearch.split(RegExp(r'\s+')).where((w) => w.isNotEmpty);
    if (words.isEmpty) return true;
    return words.every((word) => normTarget.contains(word));
  }
}
