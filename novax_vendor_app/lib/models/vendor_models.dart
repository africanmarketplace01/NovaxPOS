double _d(dynamic v) => v == null ? 0 : (v is num ? v.toDouble() : double.tryParse('$v') ?? 0);

class Dashboard {
  final String currency;
  final int todaySales;
  final double todayRevenue;
  final double monthRevenue;
  final double revenue;
  final double expense;
  final double purchase;
  final double profit;
  final int lowStock;
  final int pendingOrders;
  final double platformFeeMonth;
  final List<AccountBalance> accounts;
  final ChartSeries cashFlow;
  final ChartSeries yearly;
  final List<RecentSale> recentSales;
  final List<RecentOrder> recent; // store orders
  final List<PlatformFee> platformFees;
  Dashboard({
    required this.currency, required this.todaySales, required this.todayRevenue, required this.monthRevenue,
    required this.revenue, required this.expense, required this.purchase, required this.profit,
    required this.lowStock, required this.pendingOrders, required this.platformFeeMonth,
    required this.accounts, required this.cashFlow, required this.yearly,
    required this.recentSales, required this.recent, required this.platformFees,
  });
  factory Dashboard.fromJson(Map<String, dynamic> j) => Dashboard(
        currency: (j['currency'] ?? '') as String,
        todaySales: _i2(j['today_sales']),
        todayRevenue: _d(j['today_revenue']),
        monthRevenue: _d(j['month_revenue']),
        revenue: _d(j['revenue']),
        expense: _d(j['expense']),
        purchase: _d(j['purchase']),
        profit: _d(j['profit']),
        lowStock: _i2(j['low_stock']),
        pendingOrders: _i2(j['pending_orders']),
        platformFeeMonth: _d(j['platform_fee_month']),
        accounts: (j['accounts'] as List?)?.map((e) => AccountBalance.fromJson(Map<String, dynamic>.from(e))).toList() ?? [],
        cashFlow: ChartSeries.fromJson(j['cash_flow'], ['received', 'sent']),
        yearly: ChartSeries.fromJson(j['yearly'], ['sales', 'purchases']),
        recentSales: (j['recent_sales'] as List?)?.map((e) => RecentSale.fromJson(Map<String, dynamic>.from(e))).toList() ?? [],
        recent: (j['recent_orders'] as List?)?.map((e) => RecentOrder.fromJson(Map<String, dynamic>.from(e))).toList() ?? [],
        platformFees: (j['platform_fees'] as List?)?.map((e) => PlatformFee.fromJson(Map<String, dynamic>.from(e))).toList() ?? [],
      );
}

class AccountBalance {
  final int id;
  final String name;
  final String? accountNo;
  final bool isDefault;
  final double balance;
  AccountBalance({required this.id, required this.name, this.accountNo, required this.isDefault, required this.balance});
  factory AccountBalance.fromJson(Map<String, dynamic> j) => AccountBalance(
        id: _i2(j['id']), name: (j['name'] ?? '') as String, accountNo: j['account_no'] as String?,
        isDefault: (j['is_default'] ?? false) as bool, balance: _d(j['balance']));
}

/// Multi-series chart data with month labels and named numeric series.
class ChartSeries {
  final List<String> labels;
  final Map<String, List<double>> series;
  ChartSeries({required this.labels, required this.series});
  factory ChartSeries.fromJson(dynamic j, List<String> keys) {
    if (j is! Map) return ChartSeries(labels: const [], series: const {});
    final labels = (j['months'] as List?)?.map((e) => '$e').toList() ?? <String>[];
    final series = <String, List<double>>{};
    for (final k in keys) {
      series[k] = (j[k] as List?)?.map((e) => _d(e)).toList() ?? <double>[];
    }
    return ChartSeries(labels: labels, series: series);
  }
  double get maxValue {
    double m = 0;
    for (final s in series.values) {
      for (final v in s) { if (v > m) m = v; }
    }
    return m;
  }
}

class RecentSale {
  final int id;
  final String? reference;
  final double total;
  final double paid;
  final int status; // 1 pending, 2 due, 3 partial, 4 paid
  final String? createdAt;
  RecentSale({required this.id, this.reference, required this.total, required this.paid, required this.status, this.createdAt});
  factory RecentSale.fromJson(Map<String, dynamic> j) => RecentSale(
        id: _i2(j['id']), reference: j['reference'] as String?, total: _d(j['total']), paid: _d(j['paid']),
        status: _i2(j['status'], 1), createdAt: j['created_at'] as String?);
}

class PlatformFee {
  final int id;
  final String orderNumber;
  final double platformFee;
  final double total;
  final String currency;
  final String? createdAt;
  PlatformFee({required this.id, required this.orderNumber, required this.platformFee, required this.total, required this.currency, this.createdAt});
  factory PlatformFee.fromJson(Map<String, dynamic> j) => PlatformFee(
        id: _i2(j['id']), orderNumber: (j['order_number'] ?? '') as String, platformFee: _d(j['platform_fee']),
        total: _d(j['total']), currency: (j['currency'] ?? '') as String, createdAt: j['created_at'] as String?);
}

class RecentOrder {
  final int id;
  final String orderNumber;
  final double total;
  final String currency;
  final String status;
  final String? source;
  final String? createdAt;
  RecentOrder({required this.id, required this.orderNumber, required this.total, required this.currency, required this.status, this.source, this.createdAt});
  factory RecentOrder.fromJson(Map<String, dynamic> j) => RecentOrder(
        id: j['id'] as int, orderNumber: (j['order_number'] ?? '') as String, total: _d(j['total']),
        currency: (j['currency'] ?? 'USD') as String, status: (j['status'] ?? 'pending') as String,
        source: j['source'] as String?, createdAt: j['created_at'] as String?);
}

class VendorOrder {
  final int id;
  final String orderNumber;
  final String? customer;
  final String? phone;
  final double total;
  final String currency;
  final String status;
  final String paymentStatus;
  final String? source;
  final List items;
  final String? createdAt;
  VendorOrder({required this.id, required this.orderNumber, this.customer, this.phone, required this.total, required this.currency, required this.status, required this.paymentStatus, this.source, required this.items, this.createdAt});
  factory VendorOrder.fromJson(Map<String, dynamic> j) => VendorOrder(
        id: j['id'] as int, orderNumber: (j['order_number'] ?? '') as String, customer: j['customer'] as String?, phone: j['phone'] as String?,
        total: _d(j['total']), currency: (j['currency'] ?? 'USD') as String, status: (j['status'] ?? 'pending') as String,
        paymentStatus: (j['payment_status'] ?? 'pending') as String, source: j['source'] as String?, items: (j['items'] as List?) ?? [], createdAt: j['created_at'] as String?);
}

class VendorProduct {
  final int id;
  final String name;
  final String? code;
  final double price;
  final double cost;
  final double qty;
  final double stock;
  final double? warehouseQty;
  final double alertQty;
  final String? image;
  final String? category;
  final String? brand;
  final bool low;
  final bool inStock;
  bool listed;
  VendorProduct({
    required this.id, required this.name, this.code, required this.price, this.cost = 0,
    required this.qty, this.stock = 0, this.warehouseQty, required this.alertQty, this.image,
    this.category, this.brand, required this.low, this.inStock = true, this.listed = false,
  });
  factory VendorProduct.fromJson(Map<String, dynamic> j) => VendorProduct(
        id: j['id'] as int, name: (j['name'] ?? '') as String, code: j['code'] as String?, price: _d(j['price']),
        cost: _d(j['cost']), qty: _d(j['qty']), stock: _d(j['stock']),
        warehouseQty: j['warehouse_qty'] == null ? null : _d(j['warehouse_qty']),
        alertQty: _d(j['alert_quantity']), image: j['image'] as String?,
        category: j['category'] as String?, brand: j['brand'] as String?,
        low: (j['low'] ?? false) as bool, inStock: (j['in_stock'] ?? true) as bool, listed: (j['listed'] ?? false) as bool);
}

class VendorRequest {
  final int id;
  final String title;
  final String description;
  final String? category;
  final String? budget;
  final int quantity;
  final String? imageUrl;
  final String? videoUrl;
  final bool answered;
  final int? threadId;
  final int unread;
  final String? createdAt;
  final String? createdAgo;
  VendorRequest({required this.id, required this.title, required this.description, this.category, this.budget, required this.quantity, this.imageUrl, this.videoUrl, required this.answered, this.threadId, this.unread = 0, this.createdAt, this.createdAgo});
  factory VendorRequest.fromJson(Map<String, dynamic> j) => VendorRequest(
        id: j['id'] as int, title: (j['title'] ?? '') as String, description: (j['description'] ?? '') as String, category: j['category'] as String?,
        budget: j['budget'] as String?, quantity: (j['quantity'] ?? 1) as int, imageUrl: j['image_url'] as String?, videoUrl: j['video_url'] as String?,
        answered: (j['answered'] ?? false) as bool, threadId: j['thread_id'] == null ? null : _i2(j['thread_id']), unread: _i2(j['unread']),
        createdAt: j['created_at'] as String?, createdAgo: j['created_ago'] as String?);
}

/// A vendor's marketplace listing used as a product reference in sourcing.
class ProductRef {
  final int id;
  final int? productId;
  final String name;
  final String priceLabel;
  final String? imageUrl;
  final String? slug;
  ProductRef({required this.id, this.productId, required this.name, required this.priceLabel, this.imageUrl, this.slug});
  factory ProductRef.fromJson(Map j) => ProductRef(
        id: _i2(j['id']), productId: j['product_id'] == null ? null : _i2(j['product_id']),
        name: (j['name'] ?? '') as String, priceLabel: (j['price_label'] ?? j['price'] ?? '') as String,
        imageUrl: j['image_url'] as String?, slug: j['slug'] as String?);
}

class VMessage {
  final int id;
  final bool mine;
  final String? sender;
  final String? body;
  final String? time;
  final String? listingName;
  final String? listingPrice;
  final String? listingImage;
  final String? listingSlug;
  VMessage({required this.id, required this.mine, this.sender, this.body, this.time, this.listingName, this.listingPrice, this.listingImage, this.listingSlug});
  factory VMessage.fromJson(Map<String, dynamic> j) {
    final l = j['listing'] is Map ? j['listing'] as Map : null;
    return VMessage(
      id: j['id'] as int, mine: (j['mine'] ?? false) as bool, sender: j['sender'] as String?,
      body: j['body'] as String?, time: j['time'] as String?,
      listingName: l?['name'] as String?, listingPrice: l?['price'] as String?,
      listingImage: l?['image_url'] as String?, listingSlug: l?['slug'] as String?,
    );
  }
}

// ── POS models ───────────────────────────────────────────────────────────────
int _i2(dynamic v, [int fb = 0]) => v == null ? fb : (v is int ? v : (v is num ? v.toInt() : int.tryParse('$v') ?? fb));

/// Generic {id, name} option (warehouse, category, brand, biller, account).
class IdName {
  final int id;
  final String name;
  final bool isDefault;
  IdName(this.id, this.name, {this.isDefault = false});
  factory IdName.fromJson(Map j) =>
      IdName(_i2(j['id']), (j['name'] ?? j['company_name'] ?? '') as String, isDefault: (j['is_default'] ?? false) == true || j['is_default'] == 1);
}

class TaxOpt {
  final int id;
  final String name;
  final double rate;
  TaxOpt(this.id, this.name, this.rate);
  factory TaxOpt.fromJson(Map j) => TaxOpt(_i2(j['id']), (j['name'] ?? '') as String, _d(j['rate']));
}

class PosProduct {
  final int id;
  final String name;
  final String? code;
  final double price;
  final double? promoPrice;
  final double taxRate;
  final String? image;
  final String? category;
  final String? brand;
  final bool featured;
  final double stock;
  final double? warehouseQty;

  PosProduct({
    required this.id,
    required this.name,
    this.code,
    required this.price,
    this.promoPrice,
    this.taxRate = 0,
    this.image,
    this.category,
    this.brand,
    this.featured = false,
    this.stock = 0,
    this.warehouseQty,
  });

  double get effectivePrice => promoPrice ?? price;
  bool get onSale => promoPrice != null && promoPrice! < price;
  double get available => warehouseQty ?? stock;

  factory PosProduct.fromJson(Map<String, dynamic> j) => PosProduct(
        id: _i2(j['id']),
        name: (j['name'] ?? '') as String,
        code: j['code'] as String?,
        price: _d(j['price']),
        promoPrice: j['promo_price'] == null ? null : _d(j['promo_price']),
        taxRate: _d(j['tax_rate']),
        image: j['image'] as String?,
        category: j['category'] as String?,
        brand: j['brand'] as String?,
        featured: (j['featured'] ?? false) == true,
        stock: _d(j['stock']),
        warehouseQty: j['warehouse_qty'] == null ? null : _d(j['warehouse_qty']),
      );
}

class PosCustomer {
  final int id;
  final String name;
  final String? phone;
  final String? email;
  final String? address;
  PosCustomer({required this.id, required this.name, this.phone, this.email, this.address});
  factory PosCustomer.fromJson(Map<String, dynamic> j) => PosCustomer(
        id: _i2(j['id']),
        name: (j['name'] ?? '') as String,
        phone: j['phone'] as String?,
        email: j['email'] as String?,
        address: j['address'] as String?,
      );
}
