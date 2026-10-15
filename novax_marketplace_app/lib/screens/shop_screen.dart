import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/product.dart';
import '../services/api_client.dart';
import '../state/cart_provider.dart';
import '../theme.dart';
import '../widgets/product_card.dart';
import 'product_screen.dart';

/// Products from a single vendor store.
class ShopScreen extends StatefulWidget {
  final String tenantId;
  final String name;
  const ShopScreen({super.key, required this.tenantId, required this.name});
  @override
  State<ShopScreen> createState() => _ShopScreenState();
}

class _ShopScreenState extends State<ShopScreen> {
  final _scroll = ScrollController();
  final List<Product> _products = [];
  bool _loading = false;
  bool _end = false;
  int _page = 1;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load(reset: true);
    _scroll.addListener(() {
      if (_scroll.position.pixels > _scroll.position.maxScrollExtent - 400) _load();
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading || (_end && !reset)) return;
    setState(() => _loading = true);
    if (reset) { _page = 1; _end = false; _products.clear(); _error = null; }
    try {
      final res = await context.read<ApiClient>().get('/shops/${widget.tenantId}', query: {'page': _page, 'per_page': 20});
      final data = (res['data'] as List).map((e) => Product.fromJson(Map<String, dynamic>.from(e))).toList();
      final meta = res['meta'] as Map;
      setState(() {
        _products.addAll(data);
        _end = (meta['current_page'] as int) >= (meta['last_page'] as int);
        _page++;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _addToCart(Product p) {
    context.read<CartProvider>().add(p);
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${p.name} added to cart'), duration: const Duration(milliseconds: 900)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.name, maxLines: 1, overflow: TextOverflow.ellipsis)),
      body: _products.isEmpty && _loading
          ? const Center(child: CircularProgressIndicator())
          : _products.isEmpty
              ? Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(_error ?? 'No products from this shop yet.', textAlign: TextAlign.center, style: const TextStyle(color: AppColors.muted))))
              : RefreshIndicator(
                  onRefresh: () => _load(reset: true),
                  child: GridView.builder(
                    controller: _scroll,
                    padding: const EdgeInsets.fromLTRB(12, 12, 12, 20),
                    gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                      maxCrossAxisExtent: 200, mainAxisExtent: 266, crossAxisSpacing: 10, mainAxisSpacing: 10),
                    itemCount: _products.length,
                    itemBuilder: (_, i) {
                      final p = _products[i];
                      return ProductCard(
                        product: p,
                        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ProductScreen(productId: p.id))),
                        onAdd: () => _addToCart(p),
                      );
                    },
                  ),
                ),
    );
  }
}
