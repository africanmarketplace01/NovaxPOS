/// Small formatting helpers shared across apps.
class Fmt {
  static String money(num value, [String currency = '']) =>
      (currency.isEmpty ? '' : '$currency ') + value.toStringAsFixed(2);

  static String cap(String s) => s.isEmpty ? s : s[0].toUpperCase() + s.substring(1);

  static double toDouble(dynamic v) =>
      v == null ? 0 : (v is num ? v.toDouble() : double.tryParse('$v') ?? 0);

  static const _months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  /// Parse an ISO-8601 string to local DateTime, or null if not parseable.
  static DateTime? _parse(String? s) {
    if (s == null || s.isEmpty) return null;
    final d = DateTime.tryParse(s);
    return d?.toLocal();
  }

  static String _clock(DateTime d) {
    final h = d.hour % 12 == 0 ? 12 : d.hour % 12;
    final m = d.minute.toString().padLeft(2, '0');
    return '$h:$m ${d.hour < 12 ? 'AM' : 'PM'}';
  }

  /// "Jul 11, 2026" — falls back to the original string if not an ISO date.
  static String date(String? s) {
    final d = _parse(s);
    if (d == null) return s ?? '';
    return '${_months[d.month - 1]} ${d.day}, ${d.year}';
  }

  /// Chat-friendly timestamp: time-only for today, "Jul 11, 3:24 PM" this year,
  /// full date otherwise. Falls back to the original string if not ISO.
  static String chatTime(String? s) {
    final d = _parse(s);
    if (d == null) return s ?? '';
    final now = DateTime.now();
    final sameDay = d.year == now.year && d.month == now.month && d.day == now.day;
    if (sameDay) return _clock(d);
    if (d.year == now.year) return '${_months[d.month - 1]} ${d.day}, ${_clock(d)}';
    return '${_months[d.month - 1]} ${d.day}, ${d.year}, ${_clock(d)}';
  }

  /// "Jul 11, 2026 · 3:24 PM" — full date+time, falls back to original string.
  static String dateTime(String? s) {
    final d = _parse(s);
    if (d == null) return s ?? '';
    return '${_months[d.month - 1]} ${d.day}, ${d.year} · ${_clock(d)}';
  }
}
