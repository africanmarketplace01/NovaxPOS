import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/product.dart';
import '../services/api_client.dart';
import '../state/cart_provider.dart';
import '../theme.dart';

class ProductScreen extends StatefulWidget {
  final int productId;
  const ProductScreen({super.key, required this.productId});
  @override
  State<ProductScreen> createState() => _ProductScreenState();
}

class _ProductScreenState extends State<ProductScreen> {
  Product? _p;
  List<Review> _reviews = [];
  bool _loading = true;
  String? _error;
  int _img = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/products/${widget.productId}');
      setState(() {
        _p = Product.fromJson(Map<String, dynamic>.from(res['product']));
        _reviews = (res['reviews'] as List?)?.map((e) => Review.fromJson(Map<String, dynamic>.from(e))).toList() ?? [];
        _loading = false;
      });
    } catch (e) {
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Product')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      const Icon(Icons.wifi_off_rounded, size: 44, color: AppColors.muted),
                      const SizedBox(height: 10),
                      const Text("Couldn't load this product", style: TextStyle(fontWeight: FontWeight.w700)),
                      const SizedBox(height: 4),
                      Text(_error!, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                      const SizedBox(height: 14),
                      OutlinedButton.icon(onPressed: () { setState(() { _loading = true; _error = null; }); _load(); }, icon: const Icon(Icons.refresh, size: 18), label: const Text('Retry')),
                    ]),
                  ),
                )
              : _content(_p!),
      bottomNavigationBar: _p == null ? null : _buyBar(_p!),
    );
  }

  Widget _content(Product p) {
    final images = p.gallery.isNotEmpty ? p.gallery : (p.imageUrl != null ? [p.imageUrl!] : <String>[]);
    return ListView(padding: const EdgeInsets.only(bottom: 20), children: [
      AspectRatio(
        aspectRatio: 1.1,
        child: images.isEmpty
            ? Container(color: const Color(0xFFF1F5F9), child: const Icon(Icons.image_outlined, size: 60, color: Color(0xFFCBD5E1)))
            : CachedNetworkImage(imageUrl: images[_img], fit: BoxFit.contain),
      ),
      if (images.length > 1)
        SizedBox(
          height: 70,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            itemCount: images.length,
            separatorBuilder: (_, __) => const SizedBox(width: 8),
            itemBuilder: (_, i) => GestureDetector(
              onTap: () => setState(() => _img = i),
              child: Container(
                width: 54, height: 54,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(9),
                  border: Border.all(color: i == _img ? AppColors.primary : AppColors.border, width: 2),
                ),
                child: ClipRRect(borderRadius: BorderRadius.circular(7), child: CachedNetworkImage(imageUrl: images[i], fit: BoxFit.cover)),
              ),
            ),
          ),
        ),
      Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          if (p.shop != null)
            Text(p.shop!, style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w700, fontSize: 12.5)),
          const SizedBox(height: 6),
          Text(p.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.dark)),
          const SizedBox(height: 8),
          if (p.ratingCount > 0)
            Row(children: [
              _Stars(p.ratingAvg),
              const SizedBox(width: 6),
              Text('${p.ratingAvg} (${p.ratingCount})', style: const TextStyle(color: AppColors.muted, fontSize: 12.5)),
            ]),
          const SizedBox(height: 10),
          Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
            Text('${p.currency} ${p.effectivePrice.toStringAsFixed(2)}',
                style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: p.onSale ? const Color(0xFFEF4444) : AppColors.primary)),
            if (p.onSale) ...[
              const SizedBox(width: 8),
              Padding(
                padding: const EdgeInsets.only(bottom: 3),
                child: Text(p.price.toStringAsFixed(2), style: const TextStyle(color: AppColors.muted, decoration: TextDecoration.lineThrough)),
              ),
            ],
          ]),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
                color: p.inStock ? const Color(0xFFF0FDF4) : const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(20)),
            child: Text(p.inStock ? 'In stock (${p.stockQty})' : 'Out of stock',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: p.inStock ? const Color(0xFF16A34A) : AppColors.muted)),
          ),
          const Divider(height: 30),
          const Text('Description', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
          const SizedBox(height: 8),
          Text(_strip(p.description) ?? 'No description provided.', style: const TextStyle(color: Color(0xFF475569), height: 1.6, fontSize: 13.5)),
          const Divider(height: 30),
          Row(children: [
            const Text('Ratings & Reviews', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
            const Spacer(),
            if (p.ratingCount > 0) Text('${p.ratingAvg} ★  ·  ${p.ratingCount}', style: const TextStyle(color: AppColors.muted, fontSize: 12.5)),
          ]),
          const SizedBox(height: 10),
          if (_reviews.isEmpty)
            const Text('No reviews yet.', style: TextStyle(color: AppColors.muted, fontSize: 13))
          else
            ..._reviews.map((r) => _reviewTile(r)),
        ]),
      ),
    ]);
  }

  Widget _reviewTile(Review r) => Padding(
        padding: const EdgeInsets.only(bottom: 14),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            CircleAvatar(radius: 15, backgroundColor: AppColors.primary, child: Text(r.name.isNotEmpty ? r.name[0].toUpperCase() : '?', style: const TextStyle(color: Colors.white, fontSize: 13))),
            const SizedBox(width: 8),
            Expanded(child: Text(r.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13))),
            _Stars(r.rating.toDouble(), size: 13),
          ]),
          if (r.title != null && r.title!.isNotEmpty) Padding(padding: const EdgeInsets.only(top: 4), child: Text(r.title!, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13))),
          if (r.comment != null) Padding(padding: const EdgeInsets.only(top: 2), child: Text(r.comment!, style: const TextStyle(color: Color(0xFF475569), fontSize: 12.5))),
        ]),
      );

  Widget _buyBar(Product p) => SafeArea(
        child: Container(
          padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
          decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: AppColors.border))),
          child: ElevatedButton.icon(
            onPressed: p.inStock
                ? () {
                    context.read<CartProvider>().add(p);
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Added to cart'), duration: Duration(milliseconds: 900)));
                  }
                : null,
            icon: const Icon(Icons.add_shopping_cart),
            label: Text(p.inStock ? 'Add to Cart' : 'Out of stock'),
          ),
        ),
      );

  static String? _strip(String? html) =>
      html?.replaceAll(RegExp(r'<[^>]*>'), '').replaceAll('&nbsp;', ' ').trim();
}

class _Stars extends StatelessWidget {
  final double value;
  final double size;
  const _Stars(this.value, {this.size = 15});
  @override
  Widget build(BuildContext context) => Row(
        mainAxisSize: MainAxisSize.min,
        children: List.generate(5, (i) => Icon(i < value.round() ? Icons.star : Icons.star_border, size: size, color: const Color(0xFFF59E0B))),
      );
}
