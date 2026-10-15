class TrackStep {
  final String key;
  final String label;
  final String state; // done | current | upcoming | cancelled
  final String? at;
  TrackStep({required this.key, required this.label, required this.state, this.at});
  factory TrackStep.fromJson(Map<String, dynamic> j) => TrackStep(
        key: (j['key'] ?? '') as String,
        label: (j['label'] ?? '') as String,
        state: (j['state'] ?? 'upcoming') as String,
        at: j['at'] as String?,
      );
}

class OrderItem {
  final String name;
  final int qty;
  final double price;
  final String? imageUrl;
  final String? shop;
  OrderItem({required this.name, required this.qty, required this.price, this.imageUrl, this.shop});
  factory OrderItem.fromJson(Map<String, dynamic> j) => OrderItem(
        name: (j['name'] ?? 'Item') as String,
        qty: _i(j['qty'], 1),
        price: _d(j['price']),
        imageUrl: j['image_url'] as String?,
        shop: j['tenant_name'] as String?,
      );
  static double _d(dynamic v) => v == null ? 0 : (v is num ? v.toDouble() : double.tryParse('$v') ?? 0);
  static int _i(dynamic v, [int fallback = 0]) =>
      v == null ? fallback : (v is int ? v : (v is num ? v.toInt() : int.tryParse('$v') ?? double.tryParse('$v')?.toInt() ?? fallback));
}

class MarketOrder {
  final int id;
  final String orderNumber;
  final String status;
  final String paymentStatus;
  final String? paymentMethod;
  final String currency;
  final double subtotal;
  final double platformFee;
  final double deliveryFee;
  final double total;
  final List<OrderItem> items;
  final String? address;
  final String? deliveryAgency;
  final Map<String, dynamic>? deliveryAgent;
  final String? trackingNumber;
  final String? createdAt;
  final List<TrackStep> tracking;

  MarketOrder({
    required this.id,
    required this.orderNumber,
    required this.status,
    required this.paymentStatus,
    this.paymentMethod,
    required this.currency,
    required this.subtotal,
    required this.platformFee,
    required this.deliveryFee,
    required this.total,
    required this.items,
    this.address,
    this.deliveryAgency,
    this.deliveryAgent,
    this.trackingNumber,
    this.createdAt,
    this.tracking = const [],
  });

  factory MarketOrder.fromJson(Map<String, dynamic> j) => MarketOrder(
        id: j['id'] as int,
        orderNumber: (j['order_number'] ?? '') as String,
        status: (j['status'] ?? 'pending') as String,
        paymentStatus: (j['payment_status'] ?? 'pending') as String,
        paymentMethod: j['payment_method'] as String?,
        currency: (j['currency'] ?? 'USD') as String,
        subtotal: _d(j['subtotal']),
        platformFee: _d(j['platform_fee']),
        deliveryFee: _d(j['delivery_fee']),
        total: _d(j['total']),
        items: (j['items'] as List?)?.map((e) => OrderItem.fromJson(Map<String, dynamic>.from(e))).toList() ?? const [],
        address: j['address'] as String?,
        deliveryAgency: j['delivery_agency'] as String?,
        deliveryAgent: j['delivery_agent'] is Map ? Map<String, dynamic>.from(j['delivery_agent']) : null,
        trackingNumber: j['tracking_number'] as String?,
        createdAt: j['created_at'] as String?,
        tracking: (j['tracking'] as List?)?.map((e) => TrackStep.fromJson(Map<String, dynamic>.from(e))).toList() ?? const [],
      );

  static double _d(dynamic v) => v == null ? 0 : (v is num ? v.toDouble() : double.tryParse('$v') ?? 0);
}
