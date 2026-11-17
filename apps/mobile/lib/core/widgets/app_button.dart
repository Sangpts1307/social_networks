import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

enum ButtonVariant { primary, secondary, danger, outline }

class AppButton extends StatelessWidget {
  final String title;
  final VoidCallback? onPressed;
  final bool busy;
  final bool disabled;
  final ButtonVariant variant;
  final Widget? icon;
  final double? width;
  final double height;

  const AppButton({
    super.key,
    required this.title,
    required this.onPressed,
    this.busy = false,
    this.disabled = false,
    this.variant = ButtonVariant.primary,
    this.icon,
    this.width,
    this.height = 48,
  });

  @override
  Widget build(BuildContext context) {
    final effectiveDisabled = disabled || busy || onPressed == null;

    Color bg;
    Color fg;
    BorderSide borderSide = BorderSide.none;

    switch (variant) {
      case ButtonVariant.primary:
        bg = effectiveDisabled ? AppColors.primary.withValues(alpha: 0.5) : AppColors.primary;
        fg = Colors.white;
        break;
      case ButtonVariant.secondary:
        bg = effectiveDisabled ? const Color(0xFFF1F5F9) : const Color(0xFFE2E8F0);
        fg = AppColors.textPrimary;
        break;
      case ButtonVariant.danger:
        bg = effectiveDisabled ? AppColors.error.withValues(alpha: 0.5) : AppColors.error;
        fg = Colors.white;
        break;
      case ButtonVariant.outline:
        bg = Colors.transparent;
        fg = AppColors.primary;
        borderSide = BorderSide(color: effectiveDisabled ? AppColors.border : AppColors.primary);
        break;
    }

    return SizedBox(
      width: width ?? double.infinity,
      height: height,
      child: ElevatedButton(
        style: ElevatedButton.styleFrom(
          backgroundColor: bg,
          foregroundColor: fg,
          elevation: 0,
          side: borderSide,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 16),
        ),
        onPressed: effectiveDisabled ? null : onPressed,
        child: busy
            ? SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  valueColor: AlwaysStoppedAnimation<Color>(fg),
                ),
              )
            : Row(
                mainAxisSize: MainAxisSize.min,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  if (icon != null) ...[
                    icon!,
                    const SizedBox(width: 8),
                  ],
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      color: fg,
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}
