import 'package:flutter/material.dart';
import 'api_client.dart';

/// Brand palette — mirrors the SaaS theme (theme_primary / primary_light /
/// accent / lime / dark / cyan). These are the DEFAULTS; the live values can be
/// fetched from the backend `/theme` endpoint via [fetchBrandColors].
class AppColors {
  static const primary = Color(0xFF0066CC);
  static const primaryLight = Color(0xFF3391FF);
  static const accent = Color(0xFF22C55E);
  static const cyan = Color(0xFF00B4D8);
  static const lime = Color(0xFF65A30D);
  static const indigo = Color(0xFF6366F1);
  static const dark = Color(0xFF001A33);
  static const bg = Color(0xFFF4F6FB);
  static const border = Color(0xFFE4E8EF);
  static const ink = Color(0xFF1E293B);
  static const muted = Color(0xFF94A3B8);
  static const danger = Color(0xFFEF4444);
  static const warn = Color(0xFFF59E0B);
}

/// The full set of SaaS brand colours consumed by [buildTheme].
class BrandColors {
  final Color primary;
  final Color primaryLight;
  final Color accent;
  final Color lime;
  final Color dark;
  final Color cyan;

  const BrandColors({
    required this.primary,
    required this.primaryLight,
    required this.accent,
    required this.lime,
    required this.dark,
    required this.cyan,
  });

  /// Matches the SaaS default palette (SettingsController defaults).
  static const fallback = BrandColors(
    primary: Color(0xFF0066CC),
    primaryLight: Color(0xFF00A3FF),
    accent: Color(0xFF66FF33),
    lime: Color(0xFF8DFF00),
    dark: Color(0xFF003366),
    cyan: Color(0xFF00D4FF),
  );

  factory BrandColors.fromJson(Map<String, dynamic> j) {
    final c = (j['colors'] is Map) ? Map<String, dynamic>.from(j['colors']) : j;
    return BrandColors(
      primary: _hex(c['primary'], fallback.primary),
      primaryLight: _hex(c['primary_light'], fallback.primaryLight),
      accent: _hex(c['accent'], fallback.accent),
      lime: _hex(c['lime'], fallback.lime),
      dark: _hex(c['dark'], fallback.dark),
      cyan: _hex(c['cyan'], fallback.cyan),
    );
  }

  static Color _hex(dynamic value, Color fallbackColor) {
    if (value is! String) return fallbackColor;
    var s = value.trim().replaceFirst('#', '');
    if (s.length == 6) s = 'FF$s';
    if (s.length != 8) return fallbackColor;
    final n = int.tryParse(s, radix: 16);
    return n == null ? fallbackColor : Color(n);
  }
}

/// Fetch the live SaaS brand colours from the backend. Falls back to the
/// default palette on any error, so the app always themes correctly.
Future<BrandColors> fetchBrandColors(ApiClient api) async {
  try {
    final res = await api.get('/theme');
    if (res is Map) return BrandColors.fromJson(Map<String, dynamic>.from(res));
  } catch (_) {}
  return BrandColors.fallback;
}

/// One theme for both apps, wired to use the FULL SaaS palette (not just the
/// primary). `brand` lets a white-label / tenant build override every colour.
ThemeData buildTheme({BrandColors brand = BrandColors.fallback}) {
  final base = ThemeData(useMaterial3: true);

  final scheme = ColorScheme.fromSeed(
    seedColor: brand.primary,
    primary: brand.primary,
    onPrimary: Colors.white,
    primaryContainer: brand.primaryLight,
    secondary: brand.accent,
    onSecondary: brand.dark,
    tertiary: brand.cyan,
    surfaceTint: brand.primary,
  );

  return base.copyWith(
    scaffoldBackgroundColor: AppColors.bg,
    colorScheme: scheme,
    primaryColor: brand.primary,
    appBarTheme: AppBarTheme(
      backgroundColor: Colors.white,
      foregroundColor: brand.dark,
      elevation: 0,
      surfaceTintColor: Colors.white,
      iconTheme: IconThemeData(color: brand.dark),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: brand.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(foregroundColor: brand.primary),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(foregroundColor: brand.primary),
    ),
    floatingActionButtonTheme: FloatingActionButtonThemeData(
      backgroundColor: brand.primary, foregroundColor: Colors.white),
    progressIndicatorTheme: ProgressIndicatorThemeData(color: brand.primary),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white,
      indicatorColor: brand.primary.withValues(alpha: .14),
      iconTheme: WidgetStateProperty.resolveWith((states) => IconThemeData(
          color: states.contains(WidgetState.selected) ? brand.primary : AppColors.muted)),
      labelTextStyle: WidgetStateProperty.resolveWith((states) => TextStyle(
          fontSize: 11.5,
          fontWeight: FontWeight.w600,
          color: states.contains(WidgetState.selected) ? brand.primary : AppColors.muted)),
    ),
    chipTheme: base.chipTheme.copyWith(
      selectedColor: brand.primary,
      secondarySelectedColor: brand.primary,
      checkmarkColor: Colors.white,
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true, fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.border)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.border)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: brand.primary, width: 1.5)),
    ),
  );
}
