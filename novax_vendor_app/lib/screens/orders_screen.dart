import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';
import '../models/vendor_models.dart';
import 'order_detail_screen.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});
  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<VendorOrder> _orders = [];
  bool _loading = true;
  String _status = '';
  String? _error;

  static const _statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/orders', query: {if (_status.isNotEmpty) 'status': _status});
      _orders = (res['data'] as List).map((e) => VendorOrder.fromJson(Map<String, dynamic>.from(e))).toList();
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  List<String> _allowedStatuses(String current) {
    if (current == 'completed') return ['completed', 'cancelled'];
    if (current == 'cancelled') return ['cancelled'];
    return _statuses;
  }

  Future<void> _setStatus(VendorOrder o) async {
    final options = _allowedStatuses(o.status);
    final picked = await showModalBottomSheet<String>(
      context: context,
      builder: (_) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Padding(padding: EdgeInsets.all(16), child: Text('Update order status', style: TextStyle(fontWeight: FontWeight.w800))),
          ...options.map((s) => ListTile(
                title: Text(s[0].toUpperCase() + s.substring(1)),
                trailing: o.status == s ? const Icon(Icons.check, color: AppColors.primary) : null,
                onTap: () => Navigator.pop(context, s),
              )),
        ]),
      ),
    );
    if (picked != null && picked != o.status) {
      try {
        await context.read<ApiClient>().post('/vendor/orders/${o.id}/status', {'status': picked});
        _load();
      } catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Orders')),
      body: Column(children: [
        SizedBox(
          height: 46,
          child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 12), children: [
            _chip('All', ''),
            ..._statuses.map((s) => _chip(s[0].toUpperCase() + s.substring(1), s)),
          ]),
        ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _error != null && _orders.isEmpty
                      ? ErrorRetry(message: _error!, onRetry: _load)
                      : _orders.isEmpty
                      ? ListView(children: const [SizedBox(height: 120), Center(child: Text('No orders.', style: TextStyle(color: AppColors.muted)))])
                      : ListView.separated(
                          padding: const EdgeInsets.all(12),
                          itemCount: _orders.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (_, i) => _tile(_orders[i]),
                        ),
                ),
        ),
      ]),
    );
  }

  Widget _chip(String label, String value) => Padding(
        padding: const EdgeInsets.only(right: 8, top: 8),
        child: ChoiceChip(
          label: Text(label),
          selected: _status == value,
          onSelected: (_) { setState(() => _status = value); _load(); },
          selectedColor: AppColors.primary,
          labelStyle: TextStyle(color: _status == value ? Colors.white : AppColors.ink, fontSize: 12.5),
        ),
      );

  Widget _tile(VendorOrder o) => InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () async {
          final changed = await Navigator.push(context, MaterialPageRoute(builder: (_) => OrderDetailScreen(orderId: o.id)));
          if (changed == true) _load();
        },
        child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(child: Text(o.orderNumber, style: const TextStyle(fontWeight: FontWeight.w800))),
            Text('${o.currency} ${o.total.toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w800, color: AppColors.primary)),
          ]),
          const SizedBox(height: 3),
          Text('${o.customer ?? ''}${o.phone != null ? ' · ${o.phone}' : ''}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
          Text('${o.createdAt ?? ''}${o.source != null ? ' · ${o.source}' : ''} · ${o.items.length} item(s)', style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
          const SizedBox(height: 10),
          Row(children: [
            _statusPill(o.status),
            const SizedBox(width: 6),
            Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3), decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(20)), child: Text(o.paymentStatus, style: const TextStyle(fontSize: 11, color: AppColors.muted, fontWeight: FontWeight.w600))),
            const Spacer(),
            OutlinedButton.icon(
              onPressed: () => _setStatus(o),
              style: OutlinedButton.styleFrom(visualDensity: VisualDensity.compact, side: const BorderSide(color: AppColors.border)),
              icon: const Icon(Icons.edit, size: 15),
              label: const Text('Status', style: TextStyle(fontSize: 12.5)),
            ),
          ]),
        ]),
        ),
      );

  Widget _statusPill(String status) {
    const map = {
      'pending': Color(0xFFF59E0B), 'processing': AppColors.primary, 'shipped': Color(0xFF6366F1),
      'delivered': Color(0xFF22C55E), 'cancelled': Color(0xFFEF4444), 'completed': Color(0xFF22C55E),
    };
    final c = map[status] ?? AppColors.muted;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
      decoration: BoxDecoration(color: c.withValues(alpha: .12), borderRadius: BorderRadius.circular(20)),
      child: Text(status[0].toUpperCase() + status.substring(1), style: TextStyle(color: c, fontSize: 11.5, fontWeight: FontWeight.w700)),
    );
  }
}
