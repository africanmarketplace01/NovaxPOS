import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:novax_core/novax_core.dart';

class OrderDetailScreen extends StatefulWidget {
  final int orderId;
  const OrderDetailScreen({super.key, required this.orderId});
  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  Map<String, dynamic>? _o;
  bool _loading = true;
  String? _error;
  bool _changed = false;

  static const _statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/orders/${widget.orderId}');
      _o = Map<String, dynamic>.from(res['order']);
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String get _cur => (_o?['currency'] ?? '') as String;
  String _m(dynamic v) => '$_cur ${((v ?? 0) as num).toStringAsFixed(2)}';

  // Allowed transitions. A completed order can only be cancelled (or stay completed);
  // a cancelled order stays cancelled.
  List<String> _allowedStatuses(String? current) {
    if (current == 'completed') return ['completed', 'cancelled'];
    if (current == 'cancelled') return ['cancelled'];
    return _statuses;
  }

  Future<void> _updateStatus() async {
    final current = _o?['status'] as String?;
    final options = _allowedStatuses(current);
    final picked = await showModalBottomSheet<String>(
      context: context,
      builder: (_) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Padding(padding: EdgeInsets.all(16), child: Text('Update order status', style: TextStyle(fontWeight: FontWeight.w800))),
          ...options.map((s) => ListTile(
                title: Text(s[0].toUpperCase() + s.substring(1)),
                trailing: current == s ? const Icon(Icons.check, color: AppColors.primary) : null,
                onTap: () => Navigator.pop(context, s),
              )),
        ]),
      ),
    );
    if (picked == null || picked == current) return;
    try {
      await context.read<ApiClient>().post('/vendor/orders/${widget.orderId}/status', {'status': picked});
      _changed = true;
      await _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: true,
      onPopInvokedWithResult: (didPop, _) {},
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Order'),
          leading: IconButton(icon: const Icon(Icons.arrow_back), onPressed: () => Navigator.pop(context, _changed)),
        ),
        body: _loading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? ErrorRetry(message: _error!, onRetry: _load)
                : _content(_o!),
        bottomNavigationBar: (_loading || _o == null) ? null : _bottomBar(),
      ),
    );
  }

  Widget _content(Map<String, dynamic> o) {
    final items = (o['items'] as List?) ?? [];
    final cust = (o['customer'] as Map?) ?? {};
    final delivery = (o['delivery'] as Map?) ?? {};
    final isMkt = (o['source'] ?? '') == 'marketplace';

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(padding: const EdgeInsets.all(16), children: [
        Row(children: [
          Expanded(child: Text(o['order_number'] ?? '', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18))),
          StatusPill((o['status'] ?? 'pending') as String),
        ]),
        const SizedBox(height: 4),
        Row(children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(color: isMkt ? const Color(0xFFEEF2FF) : const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(6)),
            child: Text(isMkt ? 'Marketplace' : 'Online store', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: isMkt ? const Color(0xFF4F46E5) : const Color(0xFF475569))),
          ),
          const SizedBox(width: 8),
          Text(o['created_at'] ?? '', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
        ]),
        const SizedBox(height: 16),

        _card('Customer', Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(cust['name'] ?? '—', style: const TextStyle(fontWeight: FontWeight.w700)),
          if (cust['phone'] != null) Text(cust['phone'], style: const TextStyle(color: AppColors.muted, fontSize: 13)),
          if (cust['email'] != null) Text(cust['email'], style: const TextStyle(color: AppColors.muted, fontSize: 13)),
        ])),
        const SizedBox(height: 12),
        _card('Delivery', Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(cust['address'] ?? 'No address provided', style: const TextStyle(color: Color(0xFF475569))),
          const SizedBox(height: 4),
          Text('Agency: ${delivery['agency'] ?? 'Not assigned'}', style: const TextStyle(fontSize: 13, color: AppColors.muted)),
          if ((delivery['estimated'] ?? '') != '') Text('Est: ${delivery['estimated']}', style: const TextStyle(fontSize: 13, color: AppColors.muted)),
          if (o['note'] != null) Padding(padding: const EdgeInsets.only(top: 4), child: Text('Note: ${o['note']}', style: const TextStyle(fontSize: 12.5, fontStyle: FontStyle.italic, color: AppColors.muted))),
          const SizedBox(height: 10),
          _agentBlock(delivery),
          if ((o['status'] ?? '') == 'completed') ...[
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: _deliveryNote,
                icon: const Icon(Icons.receipt_long_outlined, size: 18),
                label: const Text('Generate delivery note'),
              ),
            ),
          ],
        ])),
        const SizedBox(height: 12),
        _card('Payment', Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Method: ${o['payment_method'] ?? '—'}', style: const TextStyle(fontSize: 13.5)),
          Text('Status: ${o['payment_status'] ?? 'pending'}', style: const TextStyle(fontSize: 13.5)),
          if (o['marketplace_order_number'] != null) Text('Ref: ${o['marketplace_order_number']}', style: const TextStyle(fontSize: 13, color: AppColors.muted)),
        ])),
        const SizedBox(height: 12),
        _card('Items (${items.length})', Column(children: items.map<Widget>((it) => _itemRow(Map<String, dynamic>.from(it))).toList())),
        const SizedBox(height: 12),
        _card('Summary', Column(children: [
          _row('Subtotal', _m(o['subtotal'])),
          if ((o['tax_amount'] ?? 0) > 0) _row('Tax', _m(o['tax_amount'])),
          if ((o['delivery_fee'] ?? 0) > 0) _row('Delivery', _m(o['delivery_fee'])),
          if ((o['platform_fee'] ?? 0) > 0) _row('Platform fee', _m(o['platform_fee'])),
          const Divider(),
          _row('Total', _m(o['total']), bold: true),
        ])),
        const SizedBox(height: 20),
      ]),
    );
  }

  Widget _itemRow(Map<String, dynamic> it) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Row(children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: SizedBox(
              width: 44, height: 44,
              child: it['image'] != null
                  ? CachedNetworkImage(imageUrl: it['image'], fit: BoxFit.cover, errorWidget: (_, __, ___) => const _Ph())
                  : const _Ph(),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(it['name'] ?? 'Item', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
            Text('Qty ${(it['qty'] as num?)?.toStringAsFixed(0) ?? '1'} × ${_m(it['price'])}', style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
          ])),
          Text(_m(it['subtotal']), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
        ]),
      );

  Widget _agentBlock(Map delivery) {
    final agent = delivery['agent'] as Map?;
    if (agent != null) {
      return Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(10), border: Border.all(color: AppColors.border)),
        child: Row(children: [
          CircleAvatar(radius: 20, backgroundColor: const Color(0xFFEEF2F7), backgroundImage: agent['photo_url'] != null ? NetworkImage('${agent['photo_url']}') : null, child: agent['photo_url'] == null ? const Icon(Icons.person, color: AppColors.muted, size: 20) : null),
          const SizedBox(width: 10),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('Delivery agent', style: TextStyle(fontSize: 10.5, color: AppColors.muted, fontWeight: FontWeight.w600)),
            Text('${agent['name']}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
            if (agent['phone'] != null) Text('${agent['phone']}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
            if (agent['bike'] != null) Text('🏍 ${agent['bike']}', style: const TextStyle(color: AppColors.primary, fontSize: 11.5)),
          ])),
          TextButton(onPressed: _assignAgent, child: const Text('Change')),
        ]),
      );
    }
    if (delivery['agency_id'] != null) {
      return SizedBox(
        width: double.infinity,
        child: OutlinedButton.icon(onPressed: _assignAgent, icon: const Icon(Icons.person_pin_circle_outlined, size: 18), label: const Text('Assign delivery agent')),
      );
    }
    return const SizedBox.shrink();
  }

  Future<void> _assignAgent() async {
    List agents;
    try {
      final res = await context.read<ApiClient>().get('/vendor/orders/${widget.orderId}/agents');
      agents = (res['data'] as List?) ?? [];
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      return;
    }
    if (agents.isEmpty) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('No agents in this delivery agency yet. Add agents under Setup → Delivery.')));
      return;
    }
    final picked = await showModalBottomSheet<int>(
      context: context,
      builder: (_) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Padding(padding: EdgeInsets.all(16), child: Text('Assign to agent', style: TextStyle(fontWeight: FontWeight.w800))),
          ...agents.map((a) {
            final m = Map<String, dynamic>.from(a);
            return ListTile(
              leading: CircleAvatar(backgroundColor: const Color(0xFFEEF2F7), backgroundImage: m['photo_url'] != null ? NetworkImage('${m['photo_url']}') : null, child: m['photo_url'] == null ? const Icon(Icons.person, size: 18, color: AppColors.muted) : null),
              title: Text('${m['name']}'),
              subtitle: m['phone'] != null ? Text('${m['phone']}') : null,
              onTap: () => Navigator.pop(context, m['id'] as int),
            );
          }),
          ListTile(leading: const Icon(Icons.person_off_outlined), title: const Text('Unassign'), onTap: () => Navigator.pop(context, -1)),
        ]),
      ),
    );
    if (picked == null) return;
    try {
      await context.read<ApiClient>().post('/vendor/orders/${widget.orderId}/assign-agent', {'agent_id': picked == -1 ? null : picked});
      _changed = true;
      await _load();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(picked == -1 ? 'Agent unassigned.' : 'Agent assigned.')));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _deliveryNote() async {
    try {
      final res = await context.read<ApiClient>().post('/vendor/orders/${widget.orderId}/delivery-note');
      final ref = res['reference'];
      final existed = res['existing'] == true;
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(existed ? 'Delivery note already exists: $ref' : 'Delivery note created: $ref')));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _markPaid() async {
    try {
      await context.read<ApiClient>().post('/vendor/orders/${widget.orderId}/mark-paid');
      _changed = true;
      await _load();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Marked as paid.')));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Widget _bottomBar() {
    final paid = '${_o?['payment_status'] ?? ''}'.toLowerCase() == 'paid';
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(children: [
          if (!paid) ...[
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _markPaid,
                style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 12), foregroundColor: const Color(0xFF16A34A), side: const BorderSide(color: Color(0xFF86EFAC))),
                icon: const Icon(Icons.payments_outlined, size: 18),
                label: const Text('Mark as paid', style: TextStyle(fontWeight: FontWeight.w700)),
              ),
            ),
            const SizedBox(width: 10),
          ],
          Expanded(
            child: ElevatedButton.icon(
              onPressed: _updateStatus,
              style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 12)),
              icon: const Icon(Icons.edit_outlined, size: 18),
              label: const Text('Update status', style: TextStyle(fontWeight: FontWeight.w700)),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _card(String title, Widget child) => Container(
        width: double.infinity,
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
          const SizedBox(height: 10),
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

class _Ph extends StatelessWidget {
  const _Ph();
  @override
  Widget build(BuildContext context) => Container(color: const Color(0xFFF1F5F9), child: const Icon(Icons.inventory_2_outlined, color: Color(0xFFCBD5E1), size: 20));
}
