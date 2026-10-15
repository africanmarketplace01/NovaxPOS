import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import '../models/product.dart';
import '../theme.dart';

class ProductCard extends StatelessWidget {
  final Product product;
  final VoidCallback onTap;
  final VoidCallback? onAdd;
  const ProductCard({super.key, required this.product, required this.onTap, this.onAdd});

  @override
  Widget build(BuildContext context) {
    final p = product;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
            child: SizedBox(
              height: 118,
              width: double.infinity,
              child: p.imageUrl != null
                  ? CachedNetworkImage(
                      imageUrl: p.imageUrl!,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => Container(color: const Color(0xFFF1F5F9)),
                      errorWidget: (_, __, ___) => const _Ph(),
                    )
                  : const _Ph(),
            ),
          ),
          // Flexible content so the button is always pinned and never overflows.
          Expanded(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(10, 7, 10, 9),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                if (p.shop != null)
                  Text(p.shop!.toUpperCase(),
                      maxLines: 1, overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: AppColors.primary, letterSpacing: .3)),
                const SizedBox(height: 2),
                Flexible(
                  child: Text(p.name, maxLines: 2, overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppColors.ink, height: 1.2)),
                ),
                const SizedBox(height: 4),
                Row(children: [
                  Flexible(
                    child: Text('${p.currency} ${p.effectivePrice.toStringAsFixed(2)}',
                        maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: p.onSale ? const Color(0xFFEF4444) : AppColors.primary)),
                  ),
                  if (p.onSale) ...[
                    const SizedBox(width: 5),
                    Text(p.price.toStringAsFixed(2),
                        style: const TextStyle(fontSize: 10.5, color: AppColors.muted, decoration: TextDecoration.lineThrough)),
                  ],
                ]),
                const Spacer(),
                SizedBox(
                  width: double.infinity,
                  height: 32,
                  child: ElevatedButton(
                    onPressed: p.inStock ? onAdd : null,
                    style: ElevatedButton.styleFrom(padding: EdgeInsets.zero, textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    child: Text(p.inStock ? 'Add to Cart' : 'Out of stock', maxLines: 1, overflow: TextOverflow.ellipsis),
                  ),
                ),
              ]),
            ),
          ),
        ]),
      ),
    );
  }
}

class _Ph extends StatelessWidget {
  const _Ph();
  @override
  Widget build(BuildContext context) =>
      Container(color: const Color(0xFFF1F5F9), child: const Icon(Icons.image_outlined, color: Color(0xFFCBD5E1), size: 34));
}
