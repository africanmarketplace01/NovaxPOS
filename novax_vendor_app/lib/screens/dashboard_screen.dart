import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';
import '../models/vendor_models.dart';
import '../state/auth_provider.dart';
import 'reports_screen.dart';
import 'attendance_screen.dart';
import 'setup_screens.dart';
import 'order_detail_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});
  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Dashboard? _d;
  bool _loading = true;
  String? _error;
  int _tab = 0; // 0 sales, 1 orders, 2 platform fees

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/dashboard');
      _d = Dashboard.fromJson(Map<String, dynamic>.from(res));
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String get _cur => _d?.currency ?? '';
  String _m(double v) => '${_cur.isEmpty ? '' : '$_cur '}${v.toStringAsFixed(2)}';

  PopupMenuItem<String> _menuItem(String value, IconData icon, String label, {Color? color}) =>
      PopupMenuItem<String>(
        value: value,
        child: Row(children: [
          Icon(icon, size: 20, color: color ?? AppColors.ink),
          const SizedBox(width: 12),
          Expanded(child: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: color ?? AppColors.ink))),
        ]),
      );

  void _onMenu(BuildContext context, String value) {
    switch (value) {
      case 'refresh':
        _load();
        break;
      case 'attendance':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceScreen()));
        break;
      case 'reports':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const ReportsScreen()));
        break;
      case 'setup':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const SetupScreen()));
        break;
      case 'store':
        final s = context.read<AuthProvider>().store;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Signed in to ${s ?? 'your store'}')));
        break;
      case 'about':
        showAboutDialog(context: context, applicationName: 'NovaxSuites POS — Vendor', applicationVersion: '1.0.0', applicationLegalese: '© BridgeNovaX Technologies');
        break;
      case 'logout':
        context.read<AuthProvider>().logout();
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Scaffold(
      appBar: AppBar(
        title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('Dashboard', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800)),
          if (auth.store != null) Text(auth.store!, style: const TextStyle(fontSize: 11.5, color: AppColors.muted, fontWeight: FontWeight.w500)),
        ]),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 4),
            child: Center(child: Image.asset('assets/logo.png', height: 26, errorBuilder: (_, __, ___) => const SizedBox.shrink())),
          ),
          PopupMenuButton<String>(
            icon: const Icon(Icons.more_vert),
            onSelected: (v) => _onMenu(context, v),
            itemBuilder: (_) => [
              _menuItem('refresh', Icons.refresh, 'Refresh'),
              _menuItem('attendance', Icons.access_time, 'Clock in / out'),
              _menuItem('reports', Icons.bar_chart, 'Reports'),
              const PopupMenuDivider(),
              _menuItem('setup', Icons.settings_outlined, 'Setup'),
              _menuItem('store', Icons.storefront_outlined, auth.store ?? 'My store'),
              _menuItem('about', Icons.info_outline, 'About'),
              const PopupMenuDivider(),
              _menuItem('logout', Icons.logout, 'Sign out', color: AppColors.danger),
            ],
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _d == null)
          ? RefreshIndicator(onRefresh: _load, child: ListView(children: [SizedBox(height: MediaQuery.of(context).size.height * .6, child: ErrorRetry(message: _error!, onRetry: _load))]))
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(padding: const EdgeInsets.all(16), children: [
                Text('Hi ${auth.user?.name.split(' ').first ?? ''} 👋', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.dark)),
                const Text('Last 30 days', style: TextStyle(fontSize: 12, color: AppColors.muted)),
                const SizedBox(height: 12),

                // Revenue / Expense / Profit cards
                Row(children: [
                  Expanded(child: _money('Revenue', _d?.revenue ?? 0, Icons.trending_up, const Color(0xFF22C55E))),
                  const SizedBox(width: 10),
                  Expanded(child: _money('Expense', _d?.expense ?? 0, Icons.trending_down, const Color(0xFFEF4444))),
                ]),
                const SizedBox(height: 10),
                Row(children: [
                  Expanded(child: _money('Profit', _d?.profit ?? 0, Icons.savings_outlined, AppColors.primary)),
                  const SizedBox(width: 10),
                  Expanded(child: _money('Platform fee (mo)', _d?.platformFeeMonth ?? 0, Icons.receipt_long, const Color(0xFF6366F1))),
                ]),
                const SizedBox(height: 10),

                // Quick stats
                Row(children: [
                  Expanded(child: _mini("Today's sales", '${_d?.todaySales ?? 0}', Icons.point_of_sale)),
                  const SizedBox(width: 10),
                  Expanded(child: _mini('Pending orders', '${_d?.pendingOrders ?? 0}', Icons.pending_actions)),
                  const SizedBox(width: 10),
                  Expanded(child: _mini('Low stock', '${_d?.lowStock ?? 0}', Icons.warning_amber)),
                ]),
                const SizedBox(height: 20),

                // Accounts
                if ((_d?.accounts ?? []).isNotEmpty) ...[
                  const Text('Accounts', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                  const SizedBox(height: 8),
                  SizedBox(
                    height: 116,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: _d!.accounts.length,
                      separatorBuilder: (_, __) => const SizedBox(width: 10),
                      itemBuilder: (_, i) => _accountCard(_d!.accounts[i]),
                    ),
                  ),
                  const SizedBox(height: 20),
                ],

                // Cash flow chart
                _chartCard(
                  'Cash flow',
                  'Last 6 months',
                  _d?.cashFlow,
                  {'received': const Color(0xFF22C55E), 'sent': const Color(0xFFEF4444)},
                  {'received': 'Received', 'sent': 'Sent'},
                ),
                const SizedBox(height: 14),

                // Yearly chart
                _chartCard(
                  'Yearly',
                  '${DateTime.now().year} · sales vs purchases',
                  _d?.yearly,
                  {'sales': AppColors.primary, 'purchases': const Color(0xFFF59E0B)},
                  {'sales': 'Sales', 'purchases': 'Purchases'},
                ),
                const SizedBox(height: 22),

                // Recent tabs
                const Text('Recent activity', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                const SizedBox(height: 10),
                _segmented(),
                const SizedBox(height: 10),
                ..._recentList(),
                const SizedBox(height: 12),
              ]),
            ),
    );
  }

  // ── Recent tab content ─────────────────────────────────────────────────────
  Widget _segmented() {
    const labels = ['Sales', 'Orders', 'Platform fees'];
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(10)),
      child: Row(children: List.generate(3, (i) {
        final sel = _tab == i;
        return Expanded(
          child: GestureDetector(
            onTap: () => setState(() => _tab = i),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 150),
              padding: const EdgeInsets.symmetric(vertical: 8),
              alignment: Alignment.center,
              decoration: BoxDecoration(color: sel ? Colors.white : Colors.transparent, borderRadius: BorderRadius.circular(8), boxShadow: sel ? [const BoxShadow(color: Color(0x14000000), blurRadius: 4, offset: Offset(0, 1))] : null),
              child: Text(labels[i], style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: sel ? AppColors.primary : AppColors.muted)),
            ),
          ),
        );
      })),
    );
  }

  List<Widget> _recentList() {
    if (_d == null) return [const SizedBox.shrink()];
    if (_tab == 0) {
      if (_d!.recentSales.isEmpty) return [_empty('No POS sales yet.')];
      return _d!.recentSales.map(_saleTile).toList();
    } else if (_tab == 1) {
      if (_d!.recent.isEmpty) return [_empty('No recent orders.')];
      return _d!.recent.map(_orderTile).toList();
    } else {
      if (_d!.platformFees.isEmpty) return [_empty('No platform fees yet.')];
      return _d!.platformFees.map(_feeTile).toList();
    }
  }

  Widget _empty(String s) => Padding(padding: const EdgeInsets.symmetric(vertical: 8), child: Text(s, style: const TextStyle(color: AppColors.muted)));

  Widget _saleTile(RecentSale s) {
    const map = {1: ('Pending', Color(0xFFF59E0B)), 2: ('Due', Color(0xFFEF4444)), 3: ('Partial', Color(0xFF6366F1)), 4: ('Paid', Color(0xFF22C55E))};
    final st = map[s.status] ?? ('—', AppColors.muted);
    return _rowCard(
      title: s.reference ?? 'Sale #${s.id}',
      subtitle: s.createdAt ?? '',
      trailing: _m(s.total),
      pill: st.$1, pillColor: st.$2,
      onTap: () => _detailSheet('POS sale', st.$1, st.$2, [
        ('Reference', s.reference ?? 'Sale #${s.id}'),
        ('Date', s.createdAt ?? '—'),
        ('Total', _m(s.total)),
        ('Paid', _m(s.paid)),
        ('Balance', _m(s.total - s.paid)),
        ('Payment', st.$1),
      ]),
    );
  }

  Widget _orderTile(RecentOrder o) => _rowCard(
        title: o.orderNumber,
        subtitle: '${o.createdAt ?? ''}${o.source != null ? ' · ${o.source}' : ''}',
        trailing: '${o.currency} ${o.total.toStringAsFixed(2)}',
        pill: o.status, pillColor: _statusColor(o.status),
        // Orders have a full detail screen.
        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => OrderDetailScreen(orderId: o.id))),
      );

  Widget _feeTile(PlatformFee f) => _rowCard(
        title: f.orderNumber,
        subtitle: '${f.createdAt ?? ''} · order ${f.currency} ${f.total.toStringAsFixed(2)}',
        trailing: '${f.currency} ${f.platformFee.toStringAsFixed(2)}',
        pill: 'Fee', pillColor: const Color(0xFF6366F1),
        onTap: () => _detailSheet('Platform fee', 'Fee', const Color(0xFF6366F1), [
          ('Order', f.orderNumber),
          ('Date', f.createdAt ?? '—'),
          ('Order total', '${f.currency} ${f.total.toStringAsFixed(2)}'),
          ('Platform fee', '${f.currency} ${f.platformFee.toStringAsFixed(2)}'),
        ]),
      );

  void _detailSheet(String title, String pill, Color pillColor, List<(String, String)> rows) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(18))),
      builder: (_) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: const Color(0xFFE2E8F0), borderRadius: BorderRadius.circular(2)))),
            const SizedBox(height: 14),
            Row(children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
              const Spacer(),
              Container(padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3), decoration: BoxDecoration(color: pillColor.withValues(alpha: .12), borderRadius: BorderRadius.circular(20)), child: Text(pill, style: TextStyle(color: pillColor, fontSize: 11.5, fontWeight: FontWeight.w700))),
            ]),
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
        ),
      ),
    );
  }

  Color _statusColor(String s) {
    const m = {'pending': Color(0xFFF59E0B), 'processing': AppColors.primary, 'shipped': Color(0xFF6366F1), 'delivered': Color(0xFF22C55E), 'completed': Color(0xFF22C55E), 'cancelled': Color(0xFFEF4444)};
    return m[s] ?? AppColors.muted;
  }

  Widget _rowCard({required String title, required String subtitle, required String trailing, String? pill, Color? pillColor, VoidCallback? onTap}) => InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
        child: Row(children: [
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
            const SizedBox(height: 2),
            Text(subtitle, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
          ])),
          const SizedBox(width: 8),
          Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
            Text(trailing, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13)),
            if (pill != null) ...[
              const SizedBox(height: 3),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                decoration: BoxDecoration(color: (pillColor ?? AppColors.muted).withValues(alpha: .12), borderRadius: BorderRadius.circular(20)),
                child: Text(pill[0].toUpperCase() + pill.substring(1), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: pillColor ?? AppColors.muted)),
              ),
            ],
          ]),
        ]),
      ),
      );

  // ── Cards ───────────────────────────────────────────────────────────────
  Widget _money(String label, double value, IconData icon, Color color) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Container(padding: const EdgeInsets.all(6), decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(8)), child: Icon(icon, color: color, size: 18)),
          ]),
          const SizedBox(height: 10),
          FittedBox(fit: BoxFit.scaleDown, alignment: Alignment.centerLeft, child: Text(_m(value), style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w900, color: AppColors.dark))),
          Text(label, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
        ]),
      );

  Widget _mini(String label, String value, IconData icon) => Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, color: AppColors.muted, size: 18),
          const SizedBox(height: 6),
          Text(value, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: AppColors.dark)),
          Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 10, color: AppColors.muted)),
        ]),
      );

  Widget _accountCard(AccountBalance a) => Container(
        width: 170,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          gradient: LinearGradient(colors: [AppColors.primary, AppColors.primary.withValues(alpha: .78)], begin: Alignment.topLeft, end: Alignment.bottomRight),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Row(children: [
            const Icon(Icons.account_balance_wallet, color: Colors.white70, size: 18),
            const Spacer(),
            if (a.isDefault) Container(padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1), decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(10)), child: const Text('Default', style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w700))),
          ]),
          Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            FittedBox(fit: BoxFit.scaleDown, alignment: Alignment.centerLeft, child: Text(_m(a.balance), style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w900))),
            Text(a.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white70, fontSize: 11.5)),
          ]),
        ]),
      );

  Widget _chartCard(String title, String subtitle, ChartSeries? data, Map<String, Color> colors, Map<String, String> labels) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
            Text(subtitle, style: const TextStyle(fontSize: 11, color: AppColors.muted)),
          ]),
          const Spacer(),
          Wrap(spacing: 10, children: colors.entries.map((e) => Row(mainAxisSize: MainAxisSize.min, children: [
            Container(width: 9, height: 9, decoration: BoxDecoration(color: e.value, borderRadius: BorderRadius.circular(2))),
            const SizedBox(width: 4),
            Text(labels[e.key] ?? e.key, style: const TextStyle(fontSize: 10.5, color: AppColors.muted)),
          ])).toList()),
        ]),
        const SizedBox(height: 14),
        (data == null || data.labels.isEmpty)
            ? const SizedBox(height: 120, child: Center(child: Text('No data.', style: TextStyle(color: AppColors.muted))))
            : _BarChart(data: data, colors: colors, seriesLabels: labels, currency: _cur),
      ]),
    );
  }
}

/// Lightweight interactive grouped bar chart (no external chart dependency).
/// Tap a month to reveal that month's values for each series.
class _BarChart extends StatefulWidget {
  final ChartSeries data;
  final Map<String, Color> colors;
  final Map<String, String> seriesLabels;
  final String currency;
  const _BarChart({required this.data, required this.colors, required this.seriesLabels, required this.currency});
  @override
  State<_BarChart> createState() => _BarChartState();
}

class _BarChartState extends State<_BarChart> {
  int? _sel;

  @override
  Widget build(BuildContext context) {
    final data = widget.data;
    final keys = data.series.keys.toList();
    final hasData = data.maxValue > 0;
    final max = hasData ? data.maxValue : 1.0;
    final cur = widget.currency.isEmpty ? '' : '${widget.currency} ';

    return Column(children: [
      // Value readout for the selected month (or a hint).
      SizedBox(
        height: 38,
        child: _sel == null
            ? Center(child: Text(hasData ? 'Tap a month to see its values' : 'No activity in this period yet', style: const TextStyle(color: AppColors.muted, fontSize: 12)))
            : Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(8), border: Border.all(color: AppColors.border)),
                child: Row(children: [
                  Text(data.labels[_sel!], style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12)),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Wrap(spacing: 12, children: keys.map((k) {
                      final v = data.series[k]!.length > _sel! ? data.series[k]![_sel!] : 0.0;
                      return Row(mainAxisSize: MainAxisSize.min, children: [
                        Container(width: 8, height: 8, decoration: BoxDecoration(color: widget.colors[k], borderRadius: BorderRadius.circular(2))),
                        const SizedBox(width: 4),
                        Text('${widget.seriesLabels[k] ?? k}: $cur${v.toStringAsFixed(0)}', style: const TextStyle(fontSize: 11)),
                      ]);
                    }).toList()),
                  ),
                ]),
              ),
      ),
      const SizedBox(height: 6),
      SizedBox(
        height: 130,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: List.generate(data.labels.length, (i) {
            final selected = _sel == i;
            return Expanded(
              child: GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: () => setState(() => _sel = selected ? null : i),
                child: Column(
                  mainAxisSize: MainAxisSize.max,
                  children: [
                    Expanded(
                      child: LayoutBuilder(
                        builder: (context, c) {
                          final avail = c.maxHeight;
                          return Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: keys.map((k) {
                              final v = data.series[k]!.length > i ? data.series[k]![i] : 0.0;
                              var h = (v / max) * avail;
                              if (h < 2) h = 2;
                              if (h > avail) h = avail;
                              return Container(
                                width: 7,
                                height: h,
                                margin: const EdgeInsets.symmetric(horizontal: 1.5),
                                decoration: BoxDecoration(
                                  color: (widget.colors[k] ?? AppColors.primary).withValues(alpha: selected || _sel == null ? 1 : .35),
                                  borderRadius: const BorderRadius.vertical(top: Radius.circular(2)),
                                ),
                              );
                            }).toList(),
                          );
                        },
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(data.labels[i], style: TextStyle(fontSize: 9, fontWeight: selected ? FontWeight.w800 : FontWeight.w400, color: selected ? AppColors.primary : AppColors.muted)),
                  ],
                ),
              ),
            );
          }),
        ),
      ),
    ]);
  }
}
