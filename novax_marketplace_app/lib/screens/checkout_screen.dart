import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/order.dart';
import '../services/api_client.dart';
import '../state/auth_provider.dart';
import '../state/cart_provider.dart';
import '../theme.dart';
import 'order_detail_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});
  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  late final TextEditingController _name;
  late final TextEditingController _phone;
  late final TextEditingController _address;
  String _payment = 'cod';
  bool _busy = false;
  String? _error;

  // Delivery
  List<Map<String, dynamic>> _agencies = [];
  Map<String, dynamic>? _agency;
  int? _fromZone, _toZone;

  @override
  void initState() {
    super.initState();
    final c = context.read<AuthProvider>().customer;
    _name = TextEditingController(text: c?.name ?? '');
    _phone = TextEditingController(text: c?.phone ?? '');
    _address = TextEditingController(text: c?.address ?? '');
    _loadDelivery();
  }

  Future<void> _loadDelivery() async {
    try {
      final tid = context.read<CartProvider>().firstTenantId;
      final res = await context.read<ApiClient>().get('/delivery/options', query: {if (tid != null) 'tenant_id': tid});
      if (mounted) setState(() => _agencies = (res['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList());
    } catch (_) {/* delivery optional */}
  }

  List<Map<String, dynamic>> get _zones => (_agency?['zones'] as List?)?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];

  double get _deliveryFee {
    if (_agency == null) return 0;
    if (_fromZone != null && _toZone != null) {
      for (final p in (_agency!['prices'] as List? ?? [])) {
        final m = Map<String, dynamic>.from(p);
        if (m['from_zone_id'] == _fromZone && m['to_zone_id'] == _toZone) return (m['price'] as num).toDouble();
      }
    }
    return ((_agency!['base_rate'] ?? 0) as num).toDouble();
  }

  Future<void> _placeOrder() async {
    if (_name.text.trim().isEmpty || _phone.text.trim().isEmpty || _address.text.trim().isEmpty) {
      setState(() => _error = 'Please fill in your name, phone and delivery address.');
      return;
    }
    setState(() { _busy = true; _error = null; });
    try {
      final cart = context.read<CartProvider>();
      final res = await context.read<ApiClient>().post('/orders', {
        'items': cart.toApiItems(),
        'customer_name': _name.text.trim(),
        'customer_phone': _phone.text.trim(),
        'customer_address': _address.text.trim(),
        'payment_method': _payment,
        if (_agency != null) 'delivery_agency_id': _agency!['id'],
        if (_fromZone != null) 'from_zone_id': _fromZone,
        if (_toZone != null) 'to_zone_id': _toZone,
      });
      final order = MarketOrder.fromJson(Map<String, dynamic>.from(res['order']));
      cart.clear();
      if (mounted) {
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => OrderDetailScreen(orderId: order.id, placed: true)));
      }
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = context.watch<CartProvider>();
    return Scaffold(
      appBar: AppBar(title: const Text('Checkout')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        if (_error != null)
          Container(
            padding: const EdgeInsets.all(12),
            margin: const EdgeInsets.only(bottom: 14),
            decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(10)),
            child: Text(_error!, style: const TextStyle(color: Color(0xFF991B1B))),
          ),
        const Text('Delivery details', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
        const SizedBox(height: 12),
        TextField(controller: _name, decoration: const InputDecoration(labelText: 'Full name')),
        const SizedBox(height: 12),
        TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Phone')),
        const SizedBox(height: 12),
        TextField(controller: _address, maxLines: 3, decoration: const InputDecoration(labelText: 'Delivery address')),
        if (_agencies.isNotEmpty) ...[
          const SizedBox(height: 22),
          const Text('Delivery', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
          const SizedBox(height: 10),
          DropdownButtonFormField<Map<String, dynamic>>(
            initialValue: _agency,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'Delivery agency', border: OutlineInputBorder()),
            items: [
              const DropdownMenuItem<Map<String, dynamic>>(value: null, child: Text('No delivery / pickup')),
              ..._agencies.map((a) => DropdownMenuItem(value: a, child: Text('${a['name']}${a['vendor_owned'] == true ? ' (in-house)' : ''}', overflow: TextOverflow.ellipsis))),
            ],
            onChanged: (v) => setState(() { _agency = v; _fromZone = null; _toZone = null; }),
          ),
          if (_agency != null && _zones.isNotEmpty) ...[
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: DropdownButtonFormField<int>(
                initialValue: _fromZone, isExpanded: true,
                decoration: const InputDecoration(labelText: 'From zone', border: OutlineInputBorder()),
                items: _zones.map((z) => DropdownMenuItem(value: z['id'] as int, child: Text('${z['name']}', overflow: TextOverflow.ellipsis))).toList(),
                onChanged: (v) => setState(() => _fromZone = v),
              )),
              const SizedBox(width: 10),
              Expanded(child: DropdownButtonFormField<int>(
                initialValue: _toZone, isExpanded: true,
                decoration: const InputDecoration(labelText: 'To zone', border: OutlineInputBorder()),
                items: _zones.map((z) => DropdownMenuItem(value: z['id'] as int, child: Text('${z['name']}', overflow: TextOverflow.ellipsis))).toList(),
                onChanged: (v) => setState(() => _toZone = v),
              )),
            ]),
          ],
        ],
        const SizedBox(height: 22),
        const Text('Payment method', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
        const SizedBox(height: 8),
        _payTile('cod', 'Cash on delivery', Icons.payments_outlined),
        _payTile('bank_transfer', 'Bank transfer', Icons.account_balance_outlined),
        const SizedBox(height: 20),
        _totals(cart),
        const SizedBox(height: 16),
        ElevatedButton(
          onPressed: _busy ? null : _placeOrder,
          child: _busy
              ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
              : const Text('Place Order'),
        ),
      ]),
    );
  }

  Widget _payTile(String value, String label, IconData icon) => Card(
        margin: const EdgeInsets.only(bottom: 8),
        child: RadioListTile<String>(
          value: value,
          groupValue: _payment,
          onChanged: (v) => setState(() => _payment = v!),
          title: Text(label, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
          secondary: Icon(icon, color: AppColors.primary),
          activeColor: AppColors.primary,
          contentPadding: const EdgeInsets.symmetric(horizontal: 10),
        ),
      );

  Widget _totals(CartProvider cart) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
        child: Column(children: [
          _row('Subtotal', '${cart.currency} ${cart.subtotal.toStringAsFixed(2)}'),
          const SizedBox(height: 4),
          _row('Delivery', _agency == null ? '—' : '${cart.currency} ${_deliveryFee.toStringAsFixed(2)}'),
          const SizedBox(height: 4),
          const Text('Platform fee is calculated at confirmation.', style: TextStyle(fontSize: 11, color: AppColors.muted)),
          const Divider(),
          _row('Est. total', '${cart.currency} ${(cart.subtotal + _deliveryFee).toStringAsFixed(2)}', bold: true),
        ]),
      );

  Widget _row(String a, String b, {bool bold = false}) => Row(children: [
        Text(a, style: TextStyle(color: bold ? AppColors.ink : AppColors.muted, fontWeight: bold ? FontWeight.w800 : FontWeight.w400)),
        const Spacer(),
        Text(b, style: TextStyle(fontWeight: bold ? FontWeight.w800 : FontWeight.w600)),
      ]);
}
