import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';
import '../models/order.dart';
import '../state/auth_provider.dart';
import 'login_screen.dart';
import 'order_detail_screen.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});
  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<MarketOrder> _orders = [];
  bool _loading = false;
  bool _loaded = false; // guard so we don't re-fetch on every rebuild

  Future<void> _load() async {
    if (!context.read<AuthProvider>().isAuthenticated) return;
    setState(() { _loading = true; _loaded = true; });
    try {
      final res = await context.read<ApiClient>().get('/orders');
      _orders = (res['data'] as List).map((e) => MarketOrder.fromJson(Map<String, dynamic>.from(e))).toList();
    } catch (_) {} finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.isAuthenticated) {
      return Scaffold(
        appBar: AppBar(title: const Text('My Orders')),
        body: _SignInPrompt(onSignedIn: () { _loaded = false; _load(); }),
      );
    }
    if (!_loaded) WidgetsBinding.instance.addPostFrameCallback((_) { if (mounted && !_loaded) _load(); });

    return Scaffold(
      appBar: AppBar(title: const Text('My Orders')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: _loading && _orders.isEmpty
            ? const Center(child: CircularProgressIndicator())
            : _orders.isEmpty
                ? ListView(children: const [SizedBox(height: 140), Center(child: Text('No orders yet.', style: TextStyle(color: AppColors.muted)))])
                : ListView.separated(
                    padding: const EdgeInsets.all(12),
                    itemCount: _orders.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (_, i) => _tile(_orders[i]),
                  ),
      ),
    );
  }

  Widget _tile(MarketOrder o) => InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => OrderDetailScreen(orderId: o.id))).then((_) => _load()),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
          child: Row(children: [
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(o.orderNumber, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                const SizedBox(height: 3),
                Text('${Fmt.date(o.createdAt)} · ${o.items.length} item(s)', style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
              ]),
            ),
            Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
              Text('${o.currency} ${o.total.toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w800)),
              const SizedBox(height: 4),
              _StatusChip(o.status),
            ]),
            const SizedBox(width: 4),
            const Icon(Icons.chevron_right, color: AppColors.muted),
          ]),
        ),
      );
}

class _StatusChip extends StatelessWidget {
  final String status;
  const _StatusChip(this.status);
  @override
  Widget build(BuildContext context) {
    final map = {
      'pending': const Color(0xFFF59E0B),
      'processing': AppColors.primary,
      'shipped': const Color(0xFF6366F1),
      'delivered': const Color(0xFF22C55E),
      'cancelled': const Color(0xFFEF4444),
    };
    final c = map[status] ?? AppColors.muted;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: c.withValues(alpha: .12), borderRadius: BorderRadius.circular(20)),
      child: Text(status[0].toUpperCase() + status.substring(1), style: TextStyle(color: c, fontSize: 11, fontWeight: FontWeight.w700)),
    );
  }
}

class _SignInPrompt extends StatelessWidget {
  final VoidCallback onSignedIn;
  const _SignInPrompt({required this.onSignedIn});
  @override
  Widget build(BuildContext context) => Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.lock_outline, size: 52, color: AppColors.muted),
          const SizedBox(height: 10),
          const Text('Sign in to view your orders', style: TextStyle(color: AppColors.muted)),
          const SizedBox(height: 14),
          ElevatedButton(
            onPressed: () async {
              final ok = await Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
              if (ok == true) onSignedIn();
            },
            child: const Text('Sign in'),
          ),
        ]),
      );
}
