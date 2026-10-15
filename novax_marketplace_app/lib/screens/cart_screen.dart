import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../state/auth_provider.dart';
import '../state/cart_provider.dart';
import '../theme.dart';
import 'checkout_screen.dart';
import 'login_screen.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  Future<void> _checkout(BuildContext context) async {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      await Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
      // Re-check after returning — don't rely on the login screen's return value.
      if (!context.mounted || !context.read<AuthProvider>().isAuthenticated) return;
    }
    if (context.mounted) {
      Navigator.push(context, MaterialPageRoute(builder: (_) => const CheckoutScreen()));
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = context.watch<CartProvider>();
    return Scaffold(
      appBar: AppBar(title: const Text('Cart')),
      body: cart.isEmpty
          ? const _Empty()
          : Column(children: [
              Expanded(
                child: ListView.separated(
                  padding: const EdgeInsets.all(12),
                  itemCount: cart.lines.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (_, i) {
                    final l = cart.lines[i];
                    return Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
                      child: Row(children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(9),
                          child: SizedBox(
                            width: 56, height: 56,
                            child: l.imageUrl != null
                                ? CachedNetworkImage(imageUrl: l.imageUrl!, fit: BoxFit.cover)
                                : Container(color: const Color(0xFFF1F5F9), child: const Icon(Icons.image_outlined, color: Color(0xFFCBD5E1))),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(l.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                            const SizedBox(height: 4),
                            Text('${l.currency} ${l.price.toStringAsFixed(2)}', style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w800)),
                          ]),
                        ),
                        _QtyStepper(
                          qty: l.qty,
                          onChanged: (q) => context.read<CartProvider>().setQty(l.listingId, q),
                        ),
                      ]),
                    );
                  },
                ),
              ),
              _summary(context, cart),
            ]),
    );
  }

  Widget _summary(BuildContext context, CartProvider cart) => SafeArea(
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: AppColors.border))),
          child: Column(children: [
            Row(children: [
              const Text('Subtotal', style: TextStyle(color: AppColors.muted)),
              const Spacer(),
              Text('${cart.currency} ${cart.subtotal.toStringAsFixed(2)}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            ]),
            const SizedBox(height: 10),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(onPressed: () => _checkout(context), child: const Text('Proceed to Checkout')),
            ),
          ]),
        ),
      );
}

class _QtyStepper extends StatelessWidget {
  final int qty;
  final ValueChanged<int> onChanged;
  const _QtyStepper({required this.qty, required this.onChanged});
  @override
  Widget build(BuildContext context) => Container(
        decoration: BoxDecoration(border: Border.all(color: AppColors.border), borderRadius: BorderRadius.circular(9)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          IconButton(iconSize: 18, visualDensity: VisualDensity.compact, onPressed: () => onChanged(qty - 1), icon: const Icon(Icons.remove)),
          Text('$qty', style: const TextStyle(fontWeight: FontWeight.w700)),
          IconButton(iconSize: 18, visualDensity: VisualDensity.compact, onPressed: () => onChanged(qty + 1), icon: const Icon(Icons.add)),
        ]),
      );
}

class _Empty extends StatelessWidget {
  const _Empty();
  @override
  Widget build(BuildContext context) => const Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.shopping_cart_outlined, size: 56, color: AppColors.muted),
          SizedBox(height: 10),
          Text('Your cart is empty', style: TextStyle(color: AppColors.muted)),
        ]),
      );
}
