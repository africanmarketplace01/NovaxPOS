class Product {
  final int id;
  final String name;
  final String? slug;
  final String? category;
  final String? brand;
  final double price;
  final double? promoPrice;
  final String currency;
  final bool inStock;
  final int stockQty;
  final String? imageUrl;
  final String? tenantId;
  final String? shop;

  // Detail-only
  final String? description;
  final List<String> gallery;
  final double ratingAvg;
  final int ratingCount;

  Product({
    required this.id,
    required this.name,
    this.slug,
    this.category,
    this.brand,
    required this.price,
    this.promoPrice,
    required this.currency,
    required this.inStock,
    required this.stockQty,
    this.imageUrl,
    this.tenantId,
    this.shop,
    this.description,
    this.gallery = const [],
    this.ratingAvg = 0,
    this.ratingCount = 0,
  });

  double get effectivePrice => promoPrice ?? price;
  bool get onSale => promoPrice != null && promoPrice! < price;

  factory Product.fromJson(Map<String, dynamic> j) {
    final rating = (j['rating'] is Map) ? j['rating'] as Map : const {};
    return Product(
      id: j['id'] as int,
      name: (j['name'] ?? '') as String,
      slug: j['slug'] as String?,
      category: j['category'] as String?,
      brand: j['brand'] as String?,
      price: _d(j['price']),
      promoPrice: j['promo_price'] == null ? null : _d(j['promo_price']),
      currency: (j['currency'] ?? 'USD') as String,
      inStock: (j['in_stock'] ?? false) as bool,
      stockQty: _i(j['stock_qty']),
      imageUrl: j['image_url'] as String?,
      tenantId: j['tenant_id']?.toString(),
      shop: j['shop'] as String?,
      description: j['description'] as String?,
      gallery: (j['gallery'] as List?)?.map((e) => '$e').toList() ?? const [],
      ratingAvg: _d(rating['average']),
      ratingCount: _i(rating['count']),
    );
  }

  static double _d(dynamic v) =>
      v == null ? 0 : (v is num ? v.toDouble() : double.tryParse('$v') ?? 0);

  static int _i(dynamic v) =>
      v == null ? 0 : (v is int ? v : (v is num ? v.toInt() : int.tryParse('$v') ?? double.tryParse('$v')?.toInt() ?? 0));
}

class Review {
  final String name;
  final int rating;
  final String? title;
  final String? comment;
  final bool verified;
  final String? date;
  Review({required this.name, required this.rating, this.title, this.comment, this.verified = false, this.date});
  factory Review.fromJson(Map<String, dynamic> j) => Review(
        name: (j['name'] ?? 'Anonymous') as String,
        rating: (j['rating'] ?? 0) as int,
        title: j['title'] as String?,
        comment: j['comment'] as String?,
        verified: (j['verified'] ?? false) as bool,
        date: j['date'] as String?,
      );
}
