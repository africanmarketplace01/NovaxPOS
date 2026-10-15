import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:novax_core/novax_core.dart';
import '../models/vendor_models.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});
  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  Map<String, dynamic>? _r;
  bool _loading = true;
  String? _error;

  List<IdName> _warehouses = [];
  IdName? _warehouse;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/reports',
          query: {if (_warehouse != null) 'warehouse_id': _warehouse!.id});
      _r = Map<String, dynamic>.from(res);
      _warehouses = (res['warehouses'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final top = (_r?['top_products'] as List?) ?? [];
    return Scaffold(
      appBar: AppBar(title: const Text('Reports')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _r == null)
          ? RefreshIndicator(onRefresh: _load, child: ErrorRetry(message: _error!, onRetry: _load))
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(padding: const EdgeInsets.all(16), children: [
                _warehouseFilter(),
                const SizedBox(height: 12),
                Row(children: [
                  Expanded(child: _stat('Stock value', ((_r?['stock_value'] ?? 0) as num).toDouble().toStringAsFixed(2), Icons.account_balance_wallet, AppColors.primary)),
                  const SizedBox(width: 10),
                  Expanded(child: _stat('Out of stock', '${_r?['out_of_stock'] ?? 0}', Icons.remove_shopping_cart, const Color(0xFFEF4444))),
                ]),
                const SizedBox(height: 20),
                Row(children: [
                  const Text('Top selling products', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                  const Spacer(),
                  if (_warehouse != null)
                    Text('· ${_warehouse!.name}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                ]),
                const SizedBox(height: 8),
                if (top.isEmpty)
                  const Padding(padding: EdgeInsets.only(top: 8), child: Text('No sales data yet.', style: TextStyle(color: AppColors.muted)))
                else
                  ...top.asMap().entries.map((e) => _topTile(e.key, Map<String, dynamic>.from(e.value))),
              ]),
            ),
    );
  }

  Widget _warehouseFilter() {
    return Align(
      alignment: Alignment.centerLeft,
      child: PopupMenuButton<IdName?>(
        itemBuilder: (_) => [
          const PopupMenuItem<IdName?>(value: null, child: Text('All warehouses')),
          ..._warehouses.map((w) => PopupMenuItem<IdName?>(value: w, child: Text(w.name))),
        ],
        onSelected: (v) { setState(() => _warehouse = v); _load(); },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: _warehouse != null ? AppColors.primary.withValues(alpha: .10) : Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: _warehouse != null ? AppColors.primary : AppColors.border),
          ),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.warehouse_outlined, size: 16, color: _warehouse != null ? AppColors.primary : AppColors.muted),
            const SizedBox(width: 6),
            Text(_warehouse?.name ?? 'All warehouses', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: _warehouse != null ? AppColors.primary : AppColors.ink)),
            const Icon(Icons.arrow_drop_down, size: 18, color: AppColors.muted),
          ]),
        ),
      ),
    );
  }

  Widget _topTile(int i, Map<String, dynamic> p) {
    final img = p['image'] as String?;
    final sold = (p['sold'] as num?)?.toStringAsFixed(0) ?? '0';
    final revenue = (p['revenue'] as num?)?.toDouble() ?? 0;
    final sub = [if (p['code'] != null) '#${p['code']}', if (p['category'] != null) p['category'], if (p['brand'] != null) p['brand']]
        .where((s) => s != null).join(' · ');
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
      child: Row(children: [
        SizedBox(
          width: 22,
          child: Text('${i + 1}', textAlign: TextAlign.center, style: TextStyle(color: i < 3 ? AppColors.primary : AppColors.muted, fontWeight: FontWeight.w800, fontSize: 13)),
        ),
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: SizedBox(
            width: 46, height: 46,
            child: img != null
                ? CachedNetworkImage(imageUrl: img, fit: BoxFit.cover, errorWidget: (_, __, ___) => const _Ph())
                : const _Ph(),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('${p['name']}', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
          if (sub.isNotEmpty) Text(sub, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.muted, fontSize: 11)),
          const SizedBox(height: 2),
          Text('Revenue ${revenue.toStringAsFixed(2)}', style: const TextStyle(color: AppColors.primary, fontSize: 11.5, fontWeight: FontWeight.w700)),
        ])),
        const SizedBox(width: 8),
        Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
          Text(sold, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16, color: AppColors.dark)),
          const Text('sold', style: TextStyle(color: AppColors.muted, fontSize: 10)),
        ]),
      ]),
    );
  }

  Widget _stat(String label, String value, IconData icon, Color color) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 10),
          Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppColors.dark)),
          Text(label, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
        ]),
      );
}

class _Ph extends StatelessWidget {
  const _Ph();
  @override
  Widget build(BuildContext context) => Container(color: const Color(0xFFF1F5F9), child: const Icon(Icons.inventory_2_outlined, color: Color(0xFFCBD5E1), size: 20));
}
