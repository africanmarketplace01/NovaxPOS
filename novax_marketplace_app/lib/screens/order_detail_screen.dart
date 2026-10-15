import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';
import '../models/order.dart';

class OrderDetailScreen extends StatefulWidget {
  final int orderId;
  final bool placed;
  const OrderDetailScreen({super.key, required this.orderId, this.placed = false});
  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  MarketOrder? _o;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/orders/${widget.orderId}');
      setState(() { _o = MarketOrder.fromJson(Map<String, dynamic>.from(res['order'])); _loading = false; });
    } catch (e) {
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.placed ? 'Order Confirmed' : 'Order')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : _content(_o!),
    );
  }

  Widget _content(MarketOrder o) => RefreshIndicator(
        onRefresh: _load,
        child: ListView(padding: const EdgeInsets.all(16), children: [
          if (widget.placed)
            Container(
              padding: const EdgeInsets.all(14),
              margin: const EdgeInsets.only(bottom: 14),
              decoration: BoxDecoration(color: const Color(0xFFF0FDF4), borderRadius: BorderRadius.circular(12)),
              child: const Row(children: [
                Icon(Icons.check_circle, color: Color(0xFF22C55E)),
                SizedBox(width: 10),
                Expanded(child: Text('Your order has been placed! Track its progress below.', style: TextStyle(color: Color(0xFF166534), fontWeight: FontWeight.w600))),
              ]),
            ),
          Text(o.orderNumber, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
          const SizedBox(height: 4),
          Text('Placed ${Fmt.dateTime(o.createdAt)}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
          const SizedBox(height: 18),

          _card('Order Tracking', _tracking(o)),
          const SizedBox(height: 12),
          _card('Items', Column(children: o.items.map(_itemRow).toList())),
          const SizedBox(height: 12),
          _card('Summary', Column(children: [
            _row('Subtotal', '${o.currency} ${o.subtotal.toStringAsFixed(2)}'),
            _row('Platform fee', '${o.currency} ${o.platformFee.toStringAsFixed(2)}'),
            _row('Delivery', '${o.currency} ${o.deliveryFee.toStringAsFixed(2)}'),
            const Divider(),
            _row('Total', '${o.currency} ${o.total.toStringAsFixed(2)}', bold: true),
          ])),
          if (o.address != null) ...[
            const SizedBox(height: 12),
            _card('Delivery', Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(o.address!, style: const TextStyle(color: Color(0xFF475569))),
              if (o.deliveryAgency != null) Padding(padding: const EdgeInsets.only(top: 6), child: Text('Via ${o.deliveryAgency}', style: const TextStyle(color: AppColors.muted, fontSize: 12.5))),
              if (o.trackingNumber != null) Padding(padding: const EdgeInsets.only(top: 4), child: Text('Tracking #: ${o.trackingNumber}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5))),
              if (o.deliveryAgent != null) ...[
                const SizedBox(height: 10),
                _agentCard(o.deliveryAgent!),
              ],
            ])),
          ],
        ]),
      );

  Widget _tracking(MarketOrder o) {
    if (o.tracking.isEmpty) {
      return const Text('No tracking updates yet.', style: TextStyle(color: AppColors.muted));
    }
    return Column(children: List.generate(o.tracking.length, (i) {
      final s = o.tracking[i];
      final done = s.state == 'done';
      final current = s.state == 'current';
      final cancelled = s.state == 'cancelled';
      final color = cancelled ? const Color(0xFFEF4444) : (done || current ? AppColors.primary : const Color(0xFFCBD5E1));
      return IntrinsicHeight(
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Column(children: [
            Container(
              width: 26, height: 26,
              decoration: BoxDecoration(shape: BoxShape.circle, color: color),
              child: Icon(cancelled ? Icons.close : (done ? Icons.check : Icons.circle), size: 14, color: Colors.white),
            ),
            if (i < o.tracking.length - 1)
              Expanded(child: Container(width: 2, color: done ? AppColors.primary : const Color(0xFFE2E8F0))),
          ]),
          const SizedBox(width: 12),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(bottom: 18),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(s.label, style: TextStyle(fontWeight: FontWeight.w700, color: s.state == 'upcoming' ? AppColors.muted : AppColors.ink, fontSize: 14)),
                if (s.at != null) Text(s.at!, style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
                if (current) const Text('In progress', style: TextStyle(color: AppColors.primary, fontSize: 11.5, fontWeight: FontWeight.w600)),
              ]),
            ),
          ),
        ]),
      );
    }));
  }

  Widget _itemRow(OrderItem it) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Row(children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(it.name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              Text('Qty ${it.qty}${it.shop != null ? ' · ${it.shop}' : ''}', style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
            ]),
          ),
          Text('${_o!.currency} ${(it.price * it.qty).toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
        ]),
      );

  Widget _agentCard(Map<String, dynamic> a) => Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(10), border: Border.all(color: AppColors.border)),
        child: Row(children: [
          CircleAvatar(radius: 20, backgroundColor: const Color(0xFFEEF2F7), backgroundImage: a['photo_url'] != null ? NetworkImage('${a['photo_url']}') : null, child: a['photo_url'] == null ? const Icon(Icons.person, color: AppColors.muted, size: 20) : null),
          const SizedBox(width: 10),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('Your delivery agent', style: TextStyle(fontSize: 10.5, color: AppColors.muted, fontWeight: FontWeight.w600)),
            Text('${a['name'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
            if (a['bike'] != null) Text('🏍 ${a['bike']}', style: const TextStyle(color: AppColors.primary, fontSize: 11.5)),
          ])),
          if (a['phone'] != null)
            Text('${a['phone']}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
        ]),
      );

  Widget _card(String title, Widget child) => Container(
        width: double.infinity,
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
          const SizedBox(height: 12),
          child,
        ]),
      );

  Widget _row(String a, String b, {bool bold = false}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Row(children: [
          Text(a, style: TextStyle(color: bold ? AppColors.ink : AppColors.muted, fontWeight: bold ? FontWeight.w800 : FontWeight.w400)),
          const Spacer(),
          Text(b, style: TextStyle(fontWeight: bold ? FontWeight.w800 : FontWeight.w600, color: bold ? AppColors.primary : AppColors.ink)),
        ]),
      );
}
