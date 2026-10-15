import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});
  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabs = TabController(length: 3, vsync: this);
  Map<String, dynamic>? _s;
  bool _loading = true;
  bool _busy = false;
  String? _error;
  late String _month; // YYYY-MM

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _month = '${now.year}-${now.month.toString().padLeft(2, '0')}';
    _load();
  }

  @override
  void dispose() { _tabs.dispose(); super.dispose(); }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/attendance', query: {'month': _month});
      _s = Map<String, dynamic>.from(res);
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _shiftMonth(int delta) {
    final parts = _month.split('-');
    var y = int.parse(parts[0]), m = int.parse(parts[1]) + delta;
    if (m < 1) { m = 12; y--; } else if (m > 12) { m = 1; y++; }
    _month = '$y-${m.toString().padLeft(2, '0')}';
    _load();
  }

  Future<void> _clock() async {
    setState(() => _busy = true);
    try {
      final res = await context.read<ApiClient>().post('/vendor/attendance/clock');
      final inNow = (res['clocked_in'] ?? false) as bool;
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(inNow ? 'Clocked in.' : 'Clocked out.')));
      await _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final clockedIn = (_s?['clocked_in'] ?? false) as bool;
    final since = _s?['since'] as String?;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Attendance'),
        bottom: TabBar(controller: _tabs, tabs: const [Tab(text: 'Recent'), Tab(text: 'Report'), Tab(text: 'Shift')]),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _s == null)
          ? ErrorRetry(message: _error!, onRetry: _load)
          : Column(children: [
              // Clock in/out card
              Padding(
                padding: const EdgeInsets.all(12),
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(colors: clockedIn ? [const Color(0xFF22C55E), const Color(0xFF16A34A)] : [AppColors.primary, AppColors.primary.withValues(alpha: .8)], begin: Alignment.topLeft, end: Alignment.bottomRight),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Row(children: [
                    Icon(clockedIn ? Icons.timer : Icons.timer_off_outlined, color: Colors.white, size: 30),
                    const SizedBox(width: 12),
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(clockedIn ? 'Clocked in' : 'Clocked out', style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w800)),
                      if (clockedIn && since != null) Text('Since $since', style: const TextStyle(color: Colors.white70, fontSize: 12)),
                    ])),
                    SizedBox(
                      height: 40,
                      child: ElevatedButton(
                        onPressed: _busy ? null : _clock,
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.white, foregroundColor: clockedIn ? const Color(0xFFEF4444) : AppColors.primary),
                        child: _busy
                            ? const SizedBox(height: 16, width: 16, child: CircularProgressIndicator(strokeWidth: 2))
                            : Text(clockedIn ? 'Clock out' : 'Clock in', style: const TextStyle(fontWeight: FontWeight.w800)),
                      ),
                    ),
                  ]),
                ),
              ),
              Expanded(
                child: TabBarView(controller: _tabs, children: [_recentTab(), _reportTab(), _shiftTab()]),
              ),
            ]),
    );
  }

  // ── Recent ─────────────────────────────────────────────────────────────────
  Widget _recentTab() {
    final recent = (_s?['recent'] as List?) ?? [];
    if (recent.isEmpty) return const Center(child: Text('No attendance records yet.', style: TextStyle(color: AppColors.muted)));
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(12),
        itemCount: recent.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (_, i) {
          final r = Map<String, dynamic>.from(recent[i]);
          return Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
            child: Row(children: [
              const Icon(Icons.event_available, size: 18, color: AppColors.primary),
              const SizedBox(width: 10),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('${r['date'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                const SizedBox(height: 2),
                Row(children: [
                  _timeChip('In', r['checkin'] as String?, const Color(0xFF22C55E)),
                  const SizedBox(width: 8),
                  _timeChip('Out', r['checkout'] as String?, const Color(0xFFEF4444)),
                ]),
              ])),
            ]),
          );
        },
      ),
    );
  }

  Widget _timeChip(String label, String? time, Color c) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(color: c.withValues(alpha: .10), borderRadius: BorderRadius.circular(20)),
        child: Text('$label: ${time ?? '—'}', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: c)),
      );

  // ── Report (calendar) ───────────────────────────────────────────────────────
  Widget _reportTab() {
    final report = Map<String, dynamic>.from(_s?['report'] ?? {});
    final label = '${report['label'] ?? ''}';
    final days = (report['days_in_month'] ?? 30) as int;
    final firstWeekday = (report['first_weekday'] ?? 0) as int; // 0=Sun
    final status = Map<String, dynamic>.from(report['status'] ?? {});
    const wk = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

    final cells = <Widget>[];
    for (var i = 0; i < firstWeekday; i++) {
      cells.add(const SizedBox());
    }
    for (var d = 1; d <= days; d++) {
      final st = status['$d'];
      Color? bg; Color fg = AppColors.ink;
      if (st == 'present') { bg = const Color(0xFF22C55E); fg = Colors.white; }
      else if (st == 'absent') { bg = const Color(0xFFEF4444); fg = Colors.white; }
      cells.add(Container(
        margin: const EdgeInsets.all(2),
        decoration: BoxDecoration(color: bg ?? const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(8)),
        alignment: Alignment.center,
        child: Text('$d', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: bg == null ? fg : Colors.white)),
      ));
    }

    return ListView(padding: const EdgeInsets.all(12), children: [
      Row(children: [
        IconButton(onPressed: () => _shiftMonth(-1), icon: const Icon(Icons.chevron_left)),
        Expanded(child: Text(label, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15))),
        IconButton(onPressed: () => _shiftMonth(1), icon: const Icon(Icons.chevron_right)),
      ]),
      const SizedBox(height: 6),
      Row(children: wk.map((d) => Expanded(child: Center(child: Text(d, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.muted))))).toList()),
      const SizedBox(height: 4),
      GridView.count(
        crossAxisCount: 7,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        children: cells,
      ),
      const SizedBox(height: 16),
      Row(mainAxisAlignment: MainAxisAlignment.center, children: [
        _legend('Present', const Color(0xFF22C55E)),
        const SizedBox(width: 16),
        _legend('Absent', const Color(0xFFEF4444)),
        const SizedBox(width: 16),
        _legend('Upcoming', const Color(0xFFF1F5F9)),
      ]),
    ]);
  }

  Widget _legend(String label, Color c) => Row(mainAxisSize: MainAxisSize.min, children: [
        Container(width: 14, height: 14, decoration: BoxDecoration(color: c, borderRadius: BorderRadius.circular(4))),
        const SizedBox(width: 5),
        Text(label, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
      ]);

  // ── Shift ───────────────────────────────────────────────────────────────────
  Widget _shiftTab() {
    final shift = _s?['shift'] as Map?;
    final hrm = _s?['hrm'] as Map?;
    return ListView(padding: const EdgeInsets.all(16), children: [
      if (shift != null) ...[
        _card('Your shift', Icons.badge_outlined, [
          ('Shift', '${shift['name'] ?? '—'}'),
          ('Clock in', '${shift['start'] ?? '—'}'),
          ('Clock out', '${shift['end'] ?? '—'}'),
          if (shift['grace_in'] != null) ('Grace in', '${shift['grace_in']} min'),
          if (shift['grace_out'] != null) ('Grace out', '${shift['grace_out']} min'),
        ]),
        const SizedBox(height: 10),
        const Text('Please clock in and out according to your assigned shift times.', style: TextStyle(color: AppColors.muted, fontSize: 12.5)),
      ] else if (hrm != null) ...[
        _card('Default attendance hours', Icons.schedule, [
          ('Clock in', '${hrm['checkin'] ?? '—'}'),
          ('Clock out', '${hrm['checkout'] ?? '—'}'),
        ]),
        const SizedBox(height: 10),
        const Text('No shift is assigned to you. Follow the default attendance hours above.', style: TextStyle(color: AppColors.muted, fontSize: 12.5)),
      ] else
        const Padding(padding: EdgeInsets.only(top: 40), child: Center(child: Text('No shift or attendance hours configured.', style: TextStyle(color: AppColors.muted)))),
    ]);
  }

  Widget _card(String title, IconData icon, List<(String, String)> rows) => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [Icon(icon, size: 18, color: AppColors.primary), const SizedBox(width: 8), Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15))]),
          const SizedBox(height: 12),
          ...rows.map((r) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 5),
                child: Row(children: [
                  Text(r.$1, style: const TextStyle(color: AppColors.muted, fontSize: 13)),
                  const Spacer(),
                  Text(r.$2, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                ]),
              )),
        ]),
      );
}
