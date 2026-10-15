import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/product.dart';

class CartLine {
  final int listingId;
  final String name;
  final double price;
  final String? imageUrl;
  final String currency;
  final String? tenantId;
  int qty;
  CartLine({required this.listingId, required this.name, required this.price, this.imageUrl, required this.currency, this.tenantId, this.qty = 1});

  Map<String, dynamic> toJson() => {
        'listing_id': listingId,
        'name': name,
        'price': price,
        'image_url': imageUrl,
        'currency': currency,
        'tenant_id': tenantId,
        'qty': qty,
      };
  factory CartLine.fromJson(Map<String, dynamic> j) => CartLine(
        listingId: j['listing_id'] as int,
        name: j['name'] as String,
        price: (j['price'] as num).toDouble(),
        imageUrl: j['image_url'] as String?,
        currency: (j['currency'] ?? 'USD') as String,
        tenantId: j['tenant_id']?.toString(),
        qty: j['qty'] as int,
      );
}

class CartProvider extends ChangeNotifier {
  static const _key = 'mkt_cart';
  final Map<int, CartLine> _lines = {};

  List<CartLine> get lines => _lines.values.toList();
  int get count => _lines.values.fold(0, (a, b) => a + b.qty);
  double get subtotal => _lines.values.fold(0, (a, b) => a + b.price * b.qty);
  bool get isEmpty => _lines.isEmpty;
  String get currency => _lines.isEmpty ? 'USD' : _lines.values.first.currency;
  String? get firstTenantId => _lines.isEmpty ? null : _lines.values.first.tenantId;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw != null) {
      final list = jsonDecode(raw) as List;
      for (final e in list) {
        final line = CartLine.fromJson(Map<String, dynamic>.from(e));
        _lines[line.listingId] = line;
      }
      notifyListeners();
    }
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, jsonEncode(_lines.values.map((e) => e.toJson()).toList()));
  }

  void add(Product p, {int qty = 1}) {
    final existing = _lines[p.id];
    if (existing != null) {
      existing.qty += qty;
    } else {
      _lines[p.id] = CartLine(
        listingId: p.id,
        name: p.name,
        price: p.effectivePrice,
        imageUrl: p.imageUrl,
        currency: p.currency,
        tenantId: p.tenantId,
        qty: qty,
      );
    }
    _persist();
    notifyListeners();
  }

  void setQty(int listingId, int qty) {
    final line = _lines[listingId];
    if (line == null) return;
    if (qty <= 0) {
      _lines.remove(listingId);
    } else {
      line.qty = qty;
    }
    _persist();
    notifyListeners();
  }

  void remove(int listingId) {
    _lines.remove(listingId);
    _persist();
    notifyListeners();
  }

  void clear() {
    _lines.clear();
    _persist();
    notifyListeners();
  }

  List<Map<String, dynamic>> toApiItems() =>
      _lines.values.map((l) => {'listing_id': l.listingId, 'qty': l.qty}).toList();
}
