import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

class NoticeBanner extends StatelessWidget {
  final String message;
  final VoidCallback? onRetry;
  final VoidCallback? onClose;
  final bool isError;

  const NoticeBanner({
    super.key,
    required this.message,
    this.onRetry,
    this.onClose,
    this.isError = true,
  });

  @override
  Widget build(BuildContext context) {
    if (message.isEmpty) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isError ? AppColors.noticeBg : const Color(0xFFEFF6FF),
        border: Border.all(
          color: isError ? AppColors.noticeBorder : const Color(0xFFBFDBFE),
        ),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        children: [
          Icon(
            isError ? Icons.error_outline : Icons.info_outline,
            color: isError ? AppColors.error : AppColors.primary,
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                fontSize: 13,
                color: isError ? const Color(0xFF991B1B) : const Color(0xFF1E40AF),
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          if (onRetry != null) ...[
            TextButton(
              onPressed: onRetry,
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: const Text('Thử lại', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            ),
          ],
          if (onClose != null) ...[
            IconButton(
              icon: const Icon(Icons.close, size: 16),
              onPressed: onClose,
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
            ),
          ],
        ],
      ),
    );
  }
}
