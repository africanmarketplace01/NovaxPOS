import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:novax_core/novax_core.dart';
import '../models/vendor_models.dart';
import '../services/api_client.dart';
import '../theme.dart';

class PosScreen extends StatefulWidget {
  const PosScreen({super.key});
  @override
  State<PosScreen> createState() => _PosScreenState();
}

class _PosLine {
  final PosProduct p;
  double qty;
  _PosLine(this.p) : qty = 1;
  double get unit => p.effectivePrice;
  double get subtotal => unit * qty;
  double get tax => unit * qty * p.taxRate / 100;
}

class _PosScreenState extends State<PosScreen> {
  final _search = TextEditingController();
  final Map<int, _PosLine> _cart = {};
  List<PosProduct> _products = [];

  // init data
  String _currency = '';
  List<IdName> _warehouses = [], _billers = [], _accounts = [], _categories = [], _brands = [];
  List<String> _payMethods = ['Cash'];
  PosCustomer? _walkin;

  // selections / filters
  int? _warehouseId, _billerId, _accountId, _categoryId, _brandId;
  bool _featured = false;
  String _q = '';

  bool _bootLoading = true, _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _boot();
  }

  Future<void> _boot() async {
    setState(() { _bootLoading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/pos/init');
      _currency = (res['currency'] ?? '') as String;
      List<IdName> list(String k) => ((res[k] as List?) ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
      _warehouses = list('warehouses');
      _billers = list('billers');
      _accounts = list('accounts');
      _categories = list('categories');
      _brands = list('brands');
      _payMethods = ((res['payment_methods'] as List?) ?? const ['Cash']).map((e) => '$e').toList();
      final w = res['walkin_customer'];
      if (w != null) _walkin = PosCustomer(id: (w['id'] ?? 0) as int, name: (w['name'] ?? 'Walk-in Customer') as String, phone: w['phone_number'] as String?);
      _warehouseId ??= _warehouses.isNotEmpty ? _warehouses.first.id : null;
      _billerId ??= _billers.isNotEmpty ? _billers.first.id : null;
      if (_accounts.isNotEmpty) {
        _accountId ??= _accounts.firstWhere((a) => a.isDefault, orElse: () => _accounts.first).id;
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _bootLoading = false);
    }
    await _loadProducts();
  }

  Future<void> _loadProducts() async {
    setState(() { _loading = true; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/pos/products', query: {
        if (_warehouseId != null) 'warehouse_id': _warehouseId,
        if (_q.isNotEmpty) 'q': _q,
        if (_categoryId != null) 'category_id': _categoryId,
        if (_brandId != null) 'brand_id': _brandId,
        if (_featured) 'featured': 1,
        'per_page': 50,
      });
      _products = (res['data'] as List).map((e) => PosProduct.fromJson(Map<String, dynamic>.from(e))).toList();
      _error = null;
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // Group products by category (or brand when brand-filtered) for a grouped list.
  Map<String, List<PosProduct>> get _grouped {
    final byBrand = _brandId != null;
    final m = <String, List<PosProduct>>{};
    for (final p in _products) {
      final key = (byBrand ? p.brand : p.category) ?? 'Others';
      (m[key] ??= []).add(p);
    }
    return m;
  }

  double get _sub => _cart.values.fold(0.0, (a, b) => a + b.subtotal);
  double get _tax => _cart.values.fold(0.0, (a, b) => a + b.tax);
  int get _count => _cart.values.fold(0, (a, b) => a + b.qty.round());

  void _add(PosProduct p) {
    setState(() {
      final l = _cart[p.id];
      if (l != null) { l.qty += 1; } else { _cart[p.id] = _PosLine(p); }
    });
  }

  void _setQty(int id, double qty) => setState(() { if (qty <= 0) { _cart.remove(id); } else { _cart[id]?.qty = qty; } });

  Future<void> _scan() async {
    final code = await Navigator.push<String>(context, MaterialPageRoute(builder: (_) => const _ScanPage()));
    if (code == null || code.isEmpty) return;
    try {
      final res = await context.read<ApiClient>().get('/vendor/pos/barcode/$code',
          query: {if (_warehouseId != null) 'warehouse_id': _warehouseId});
      final p = PosProduct.fromJson(Map<String, dynamic>.from(res['product']));
      _add(p);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${p.name} added'), duration: const Duration(milliseconds: 900)));
    } catch (_) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('No product for barcode "$code"')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('POS Terminal', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
          if (_currentWarehouseName != null)
            Text(_currentWarehouseName!, style: const TextStyle(fontSize: 11.5, color: AppColors.muted, fontWeight: FontWeight.w500)),
        ]),
        actions: [
          _warehouseAppBarPicker(),
          IconButton(tooltip: 'Scan barcode', onPressed: _scan, icon: const Icon(Icons.qr_code_scanner)),
          if (_cart.isNotEmpty) IconButton(tooltip: 'Clear cart', onPressed: () => setState(_cart.clear), icon: const Icon(Icons.delete_sweep_outlined)),
        ],
      ),
      body: _bootLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(children: [
              _controls(),
              Expanded(
                child: _loading
                    ? const Center(child: CircularProgressIndicator())
                    : _error != null && _products.isEmpty
                        ? ErrorRetry(message: _error!, onRetry: _loadProducts)
                        : _products.isEmpty
                            ? const Center(child: Text('No products found.', style: TextStyle(color: AppColors.muted)))
                            : _productList(),
              ),
            ]),
      bottomNavigationBar: _cart.isEmpty ? null : _chargeBar(),
    );
  }

  String? get _currentWarehouseName {
    if (_warehouseId == null) return null;
    for (final w in _warehouses) { if (w.id == _warehouseId) return w.name; }
    return null;
  }

  Widget _controls() => Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 8, 12, 4),
          child: TextField(
            controller: _search,
            textInputAction: TextInputAction.search,
            onSubmitted: (v) { _q = v.trim(); _loadProducts(); },
            decoration: InputDecoration(
              hintText: 'Search products or code…',
              isDense: true,
              prefixIcon: const Icon(Icons.search),
              suffixIcon: _q.isNotEmpty ? IconButton(icon: const Icon(Icons.clear), onPressed: () { _search.clear(); _q = ''; _loadProducts(); }) : null,
            ),
          ),
        ),
        SizedBox(
          height: 46,
          child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6), children: [
            _filterChip('All', _categoryId == null && _brandId == null && !_featured, () { setState(() { _categoryId = null; _brandId = null; _featured = false; }); _loadProducts(); }),
            _filterChip('★ Featured', _featured, () { setState(() { _featured = !_featured; }); _loadProducts(); }),
            _dropChip('Category', _categoryId, _categories, (id) { setState(() => _categoryId = id); _loadProducts(); }),
            _dropChip('Brand', _brandId, _brands, (id) { setState(() => _brandId = id); _loadProducts(); }),
          ]),
        ),
      ]);

  Widget _warehouseAppBarPicker() {
    if (_warehouses.isEmpty) return const SizedBox.shrink();
    return PopupMenuButton<int>(
      tooltip: 'Select branch / warehouse',
      onSelected: (v) { setState(() => _warehouseId = v); _loadProducts(); },
      itemBuilder: (_) => _warehouses.map((w) => PopupMenuItem<int>(
        value: w.id,
        child: Row(children: [
          Icon(Icons.warehouse_outlined, size: 18, color: w.id == _warehouseId ? AppColors.primary : AppColors.muted),
          const SizedBox(width: 10),
          Expanded(child: Text(w.name, style: TextStyle(color: w.id == _warehouseId ? AppColors.primary : AppColors.ink, fontWeight: w.id == _warehouseId ? FontWeight.w700 : FontWeight.w400))),
          if (w.id == _warehouseId) const Icon(Icons.check, size: 16, color: AppColors.primary),
        ]),
      )).toList(),
      child: const Padding(
        padding: EdgeInsets.symmetric(horizontal: 6),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.storefront_outlined),
          Icon(Icons.arrow_drop_down, size: 20),
        ]),
      ),
    );
  }

  // Consistent-height filter chips (both the toggle chips and the dropdown chips).
  static const _chipPad = EdgeInsets.symmetric(horizontal: 12, vertical: 6);

  Widget _chipShell({required bool active, required Widget child, VoidCallback? onTap}) {
    final box = Container(
      height: 34,
      padding: _chipPad,
      margin: const EdgeInsets.only(right: 8),
      decoration: BoxDecoration(
        color: active ? AppColors.primary : Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: active ? AppColors.primary : AppColors.border),
      ),
      alignment: Alignment.center,
      child: child,
    );
    if (onTap != null) return InkWell(borderRadius: BorderRadius.circular(20), onTap: onTap, child: box);
    return box;
  }

  Widget _filterChip(String label, bool sel, VoidCallback onTap) => _chipShell(
        active: sel,
        onTap: onTap,
        child: Text(label, style: TextStyle(color: sel ? Colors.white : AppColors.ink, fontSize: 12.5, fontWeight: FontWeight.w600)),
      );

  Widget _dropChip(String label, int? value, List<IdName> opts, ValueChanged<int?> onPick) {
    final sel = opts.firstWhere((o) => o.id == value, orElse: () => IdName(-1, ''));
    final active = value != null;
    return PopupMenuButton<int?>(
      onSelected: onPick,
      padding: EdgeInsets.zero,
      itemBuilder: (_) => [
        PopupMenuItem<int?>(value: null, child: Text('All ${label}s')),
        ...opts.map((o) => PopupMenuItem<int?>(value: o.id, child: Text(o.name))),
      ],
      child: _chipShell(
        active: active,
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Text(active ? sel.name : label, style: TextStyle(color: active ? Colors.white : AppColors.ink, fontSize: 12.5, fontWeight: FontWeight.w600)),
          Icon(Icons.arrow_drop_down, size: 18, color: active ? Colors.white : AppColors.muted),
        ]),
      ),
    );
  }

  Widget _productList() {
    final groups = _grouped;
    final keys = groups.keys.toList()..sort();
    return RefreshIndicator(
      onRefresh: _loadProducts,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
        itemCount: keys.length,
        itemBuilder: (_, gi) {
          final k = keys[gi];
          final items = groups[k]!;
          return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(2, 10, 2, 6),
              child: Row(children: [
                Text(k, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5, color: AppColors.dark)),
                const SizedBox(width: 6),
                Text('(${items.length})', style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
              ]),
            ),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(maxCrossAxisExtent: 170, mainAxisExtent: 168, crossAxisSpacing: 8, mainAxisSpacing: 8),
              itemCount: items.length,
              itemBuilder: (_, i) => _productCard(items[i]),
            ),
          ]);
        },
      ),
    );
  }

  Widget _productCard(PosProduct p) {
    final inCart = _cart.containsKey(p.id);
    final avail = p.available;
    return InkWell(
      onTap: () => _add(p),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: inCart ? AppColors.primary : AppColors.border, width: inCart ? 1.5 : 1)),
        clipBehavior: Clip.antiAlias,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Stack(children: [
            SizedBox(
              height: 92,
              width: double.infinity,
              child: p.image != null
                  ? CachedNetworkImage(imageUrl: p.image!, fit: BoxFit.cover, placeholder: (_, __) => Container(color: const Color(0xFFF1F5F9)), errorWidget: (_, __, ___) => const _Ph())
                  : const _Ph(),
            ),
            if (inCart)
              Positioned(top: 4, right: 4, child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 1),
                decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(20)),
                child: Text('${_cart[p.id]!.qty.round()}', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800)))),
            if (p.onSale)
              Positioned(top: 4, left: 4, child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                decoration: BoxDecoration(color: AppColors.danger, borderRadius: BorderRadius.circular(6)),
                child: const Text('SALE', style: TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.w800)))),
          ]),
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 6, 8, 8),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(p.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 11.5, height: 1.15)),
              const SizedBox(height: 3),
              Row(children: [
                Text('$_currency ${p.effectivePrice.toStringAsFixed(2)}', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 12.5, color: p.onSale ? AppColors.danger : AppColors.primary)),
                if (p.onSale) ...[
                  const SizedBox(width: 4),
                  Text(p.price.toStringAsFixed(2), style: const TextStyle(fontSize: 9.5, color: AppColors.muted, decoration: TextDecoration.lineThrough)),
                ],
                const Spacer(),
                Text(avail > 0 ? avail.toStringAsFixed(0) : '0', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: avail > 0 ? AppColors.lime : AppColors.danger)),
              ]),
            ]),
          ),
        ]),
      ),
    );
  }

  Widget _chargeBar() => SafeArea(
        child: Container(
          margin: const EdgeInsets.all(12),
          padding: const EdgeInsets.fromLTRB(16, 12, 12, 12),
          decoration: BoxDecoration(color: AppColors.dark, borderRadius: BorderRadius.circular(16)),
          child: Row(children: [
            Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
              Text('$_count item${_count == 1 ? '' : 's'}', style: const TextStyle(color: Colors.white70, fontSize: 11.5)),
              Text('$_currency ${(_sub + _tax).toStringAsFixed(2)}', style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w900)),
            ]),
            const Spacer(),
            ElevatedButton.icon(
              onPressed: _openCheckout,
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.accent, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 12)),
              icon: const Icon(Icons.point_of_sale),
              label: const Text('Charge', style: TextStyle(fontWeight: FontWeight.w800)),
            ),
          ]),
        ),
      );

  void _openCheckout() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => _CheckoutSheet(
        lines: _cart.values.toList(),
        currency: _currency,
        warehouseId: _warehouseId,
        billers: _billers,
        accounts: _accounts,
        payMethods: _payMethods,
        walkin: _walkin,
        defaultBillerId: _billerId,
        defaultAccountId: _accountId,
        onQty: _setQty,
        onDone: (result) {
          Navigator.pop(context);
          setState(_cart.clear);
          _receipt(result);
          _loadProducts();
        },
      ),
    );
  }

  void _receipt(Map<String, dynamic> r) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: Row(children: [
          Container(padding: const EdgeInsets.all(6), decoration: const BoxDecoration(color: AppColors.accent, shape: BoxShape.circle), child: const Icon(Icons.check, color: Colors.white, size: 18)),
          const SizedBox(width: 10),
          const Text('Sale complete'),
        ]),
        content: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Ref: ${r['reference'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          _kv('Total', '$_currency ${(r['total'] ?? 0).toStringAsFixed(2)}'),
          _kv('Paid', '$_currency ${(r['paid'] ?? 0).toStringAsFixed(2)}'),
          _kv('Change', '$_currency ${(r['change'] ?? 0).toStringAsFixed(2)}', color: AppColors.cyan),
        ]),
        actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('New sale'))],
      ),
    );
  }

  Widget _kv(String k, String v, {Color? color}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Row(children: [Text(k, style: const TextStyle(color: AppColors.muted)), const Spacer(), Text(v, style: TextStyle(fontWeight: FontWeight.w800, color: color ?? AppColors.ink))]),
      );
}

class _Ph extends StatelessWidget {
  const _Ph();
  @override
  Widget build(BuildContext context) => Container(color: const Color(0xFFF1F5F9), child: const Icon(Icons.inventory_2_outlined, color: Color(0xFFCBD5E1), size: 28));
}

// ── Barcode scanner ──────────────────────────────────────────────────────────
class _ScanPage extends StatefulWidget {
  const _ScanPage();
  @override
  State<_ScanPage> createState() => _ScanPageState();
}

class _ScanPageState extends State<_ScanPage> {
  bool _done = false;
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Scan barcode')),
      body: MobileScanner(
        onDetect: (capture) {
          if (_done) return;
          final code = capture.barcodes.isNotEmpty ? capture.barcodes.first.rawValue : null;
          if (code != null && code.isNotEmpty) { _done = true; Navigator.pop(context, code); }
        },
      ),
    );
  }
}

// ── Checkout ─────────────────────────────────────────────────────────────────
class _CheckoutSheet extends StatefulWidget {
  final List<_PosLine> lines;
  final String currency;
  final int? warehouseId;
  final List<IdName> billers;
  final List<IdName> accounts;
  final List<String> payMethods;
  final PosCustomer? walkin;
  final int? defaultBillerId;
  final int? defaultAccountId;
  final void Function(int id, double qty) onQty;
  final void Function(Map<String, dynamic> result) onDone;
  const _CheckoutSheet({
    required this.lines,
    required this.currency,
    required this.warehouseId,
    required this.billers,
    required this.accounts,
    required this.payMethods,
    required this.walkin,
    required this.defaultBillerId,
    required this.defaultAccountId,
    required this.onQty,
    required this.onDone,
  });
  @override
  State<_CheckoutSheet> createState() => _CheckoutSheetState();
}

class _CheckoutSheetState extends State<_CheckoutSheet> {
  final _paid = TextEditingController();
  final _discount = TextEditingController();
  String _discType = 'Flat'; // Flat | Percentage
  late String _method;
  PosCustomer? _customer;
  int? _billerId, _accountId;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _method = widget.payMethods.isNotEmpty ? widget.payMethods.first : 'Cash';
    _customer = widget.walkin;
    _billerId = widget.defaultBillerId;
    _accountId = widget.defaultAccountId;
  }

  double get _sub => widget.lines.fold(0.0, (a, b) => a + b.subtotal);
  double get _tax => widget.lines.fold(0.0, (a, b) => a + b.tax);
  double get _discValue => double.tryParse(_discount.text.trim()) ?? 0;
  // Flat = amount off; Percentage = % of product subtotal (web parity).
  double get _orderDiscount => _discType == 'Percentage' ? (_sub * _discValue / 100) : _discValue;
  double get _grand {
    final g = _sub + _tax - _orderDiscount;
    return g < 0 ? 0.0 : g;
  }

  Future<void> _pickCustomer() async {
    final c = await showModalBottomSheet<PosCustomer>(
      context: context, isScrollControlled: true, backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => const _CustomerPickerSheet(),
    );
    if (c != null) setState(() => _customer = c);
  }

  Future<void> _complete() async {
    if (widget.lines.isEmpty) return;
    setState(() { _busy = true; _error = null; });
    try {
      final paid = _paid.text.trim().isEmpty ? _grand : (double.tryParse(_paid.text.trim()) ?? _grand);
      final res = await context.read<ApiClient>().post('/vendor/pos/sale', {
        'items': widget.lines.map((l) => {'product_id': l.p.id, 'qty': l.qty, 'price': l.p.effectivePrice, 'tax_rate': l.p.taxRate}).toList(),
        if (_customer != null) 'customer_id': _customer!.id,
        if (_customer != null) 'customer_name': _customer!.name,
        if (_billerId != null) 'biller_id': _billerId,
        if (widget.warehouseId != null) 'warehouse_id': widget.warehouseId,
        if (_accountId != null) 'account_id': _accountId,
        if (_discValue > 0) ...{
          'order_discount_type': _discType,
          'order_discount_value': _discValue,
          'order_discount': _orderDiscount,
        },
        'payment_method': _method,
        'paid': paid,
      });
      widget.onDone(Map<String, dynamic>.from(res));
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    final maxH = MediaQuery.of(context).size.height * 0.9;
    return ConstrainedBox(
      constraints: BoxConstraints(maxHeight: maxH),
      child: Padding(
      padding: EdgeInsets.fromLTRB(16, 14, 16, 16 + bottom),
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
        Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.border, borderRadius: BorderRadius.circular(2)))),
        const SizedBox(height: 12),
        const Text('Checkout', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.dark)),
        const SizedBox(height: 10),
        Flexible(
          child: ListView(shrinkWrap: true, children: [
            ...widget.lines.map((l) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(children: [
                    Expanded(child: Text(l.p.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600))),
                    _Stepper(qty: l.qty.round(), onChanged: (q) => setState(() => widget.onQty(l.p.id, q.toDouble()))),
                    SizedBox(width: 74, child: Text('${widget.currency} ${l.subtotal.toStringAsFixed(2)}', textAlign: TextAlign.right, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5))),
                  ]),
                )),
            const Divider(),
            _pickerRow(Icons.person_outline, 'Customer', _customer?.name ?? 'Walk-in Customer', _pickCustomer),
            if (widget.billers.isNotEmpty) _dropdownRow('Biller', _billerId, widget.billers, (v) => setState(() => _billerId = v)),
            if (widget.accounts.isNotEmpty) _dropdownRow('Account', _accountId, widget.accounts, (v) => setState(() => _accountId = v)),
            const SizedBox(height: 8),
            Wrap(spacing: 8, children: widget.payMethods.map((m) => ChoiceChip(
                  label: Text(m),
                  selected: _method == m,
                  onSelected: (_) => setState(() => _method = m),
                  showCheckmark: false,
                  selectedColor: AppColors.primary,
                  labelStyle: TextStyle(color: _method == m ? Colors.white : AppColors.ink, fontSize: 12.5, fontWeight: FontWeight.w600),
                )).toList()),
            const SizedBox(height: 10),
            Row(children: [
              SizedBox(
                width: 96,
                child: DropdownButtonFormField<String>(
                  initialValue: _discType,
                  isDense: true,
                  decoration: const InputDecoration(labelText: 'Disc.', isDense: true),
                  items: const [
                    DropdownMenuItem(value: 'Flat', child: Text('Flat')),
                    DropdownMenuItem(value: 'Percentage', child: Text('%')),
                  ],
                  onChanged: (v) => setState(() => _discType = v ?? 'Flat'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(child: TextField(controller: _discount, keyboardType: TextInputType.number, onChanged: (_) => setState(() {}), decoration: InputDecoration(labelText: _discType == 'Percentage' ? 'Discount %' : 'Discount amount', isDense: true))),
            ]),
            const SizedBox(height: 10),
            TextField(controller: _paid, keyboardType: TextInputType.number, onChanged: (_) => setState(() {}), decoration: InputDecoration(labelText: 'Amount paid', hintText: _grand.toStringAsFixed(2), isDense: true)),
          ]),
        ),
        const SizedBox(height: 8),
        _totalRow('Subtotal', _sub),
        if (_tax > 0) _totalRow('Tax', _tax),
        if (_orderDiscount > 0) _totalRow('Discount', -_orderDiscount),
        Row(children: [
          const Text('Total', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
          const Spacer(),
          Text('${widget.currency} ${_grand.toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 20, color: AppColors.primary)),
        ]),
        if (_error != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error!, style: const TextStyle(color: AppColors.danger, fontSize: 12.5))),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: _busy ? null : _complete,
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.accent, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
            icon: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.check_circle_outline),
            label: Text(_busy ? 'Processing…' : 'Complete Sale', style: const TextStyle(fontWeight: FontWeight.w800)),
          ),
        ),
      ]),
      ),
    );
  }

  Widget _pickerRow(IconData icon, String label, String value, VoidCallback onTap) => InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Row(children: [
            Icon(icon, size: 20, color: AppColors.primary),
            const SizedBox(width: 10),
            Text('$label: ', style: const TextStyle(color: AppColors.muted, fontSize: 13)),
            Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5), overflow: TextOverflow.ellipsis)),
            const Icon(Icons.chevron_right, color: AppColors.muted),
          ]),
        ),
      );

  Widget _dropdownRow(String label, int? value, List<IdName> opts, ValueChanged<int?> onChanged) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Row(children: [
          SizedBox(width: 80, child: Text(label, style: const TextStyle(color: AppColors.muted, fontSize: 13))),
          Expanded(
            child: DropdownButtonHideUnderline(
              child: DropdownButton<int>(
                value: opts.any((o) => o.id == value) ? value : null,
                isExpanded: true,
                isDense: true,
                items: opts.map((o) => DropdownMenuItem(value: o.id, child: Text(o.name, style: const TextStyle(fontSize: 13.5)))).toList(),
                onChanged: onChanged,
              ),
            ),
          ),
        ]),
      );

  Widget _totalRow(String k, double v) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 1),
        child: Row(children: [Text(k, style: const TextStyle(color: AppColors.muted, fontSize: 12.5)), const Spacer(), Text('${widget.currency} ${v.toStringAsFixed(2)}', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600))]),
      );
}

class _Stepper extends StatelessWidget {
  final int qty;
  final ValueChanged<int> onChanged;
  const _Stepper({required this.qty, required this.onChanged});
  @override
  Widget build(BuildContext context) => Container(
        decoration: BoxDecoration(border: Border.all(color: AppColors.border), borderRadius: BorderRadius.circular(8)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          InkWell(onTap: () => onChanged(qty - 1), child: const Padding(padding: EdgeInsets.all(4), child: Icon(Icons.remove, size: 16))),
          Text('$qty', style: const TextStyle(fontWeight: FontWeight.w700)),
          InkWell(onTap: () => onChanged(qty + 1), child: const Padding(padding: EdgeInsets.all(4), child: Icon(Icons.add, size: 16))),
        ]),
      );
}

// ── Customer picker (search existing + quick add) ────────────────────────────
class _CustomerPickerSheet extends StatefulWidget {
  const _CustomerPickerSheet();
  @override
  State<_CustomerPickerSheet> createState() => _CustomerPickerSheetState();
}

class _CustomerPickerSheetState extends State<_CustomerPickerSheet> {
  final _search = TextEditingController();
  List<PosCustomer> _list = [];
  bool _loading = true;
  bool _adding = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load([String q = '']) async {
    setState(() => _loading = true);
    try {
      final res = await context.read<ApiClient>().get('/vendor/customers', query: {if (q.isNotEmpty) 'q': q});
      _list = (res['data'] as List).map((e) => PosCustomer.fromJson(Map<String, dynamic>.from(e))).toList();
    } catch (_) {} finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 14, 16, 16 + bottom),
      child: _adding
          ? _AddCustomerForm(onCreated: (c) => Navigator.pop(context, c), onCancel: () => setState(() => _adding = false))
          : Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
              Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.border, borderRadius: BorderRadius.circular(2)))),
              const SizedBox(height: 12),
              Row(children: [
                const Text('Select customer', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800)),
                const Spacer(),
                TextButton.icon(onPressed: () => setState(() => _adding = true), icon: const Icon(Icons.person_add_alt, size: 18), label: const Text('New')),
              ]),
              const SizedBox(height: 8),
              TextField(
                controller: _search,
                textInputAction: TextInputAction.search,
                onSubmitted: (v) => _load(v.trim()),
                decoration: const InputDecoration(hintText: 'Search name / phone…', isDense: true, prefixIcon: Icon(Icons.search)),
              ),
              const SizedBox(height: 8),
              SizedBox(
                height: 320,
                child: _loading
                    ? const Center(child: CircularProgressIndicator())
                    : _list.isEmpty
                        ? const Center(child: Text('No customers.', style: TextStyle(color: AppColors.muted)))
                        : ListView.separated(
                            itemCount: _list.length,
                            separatorBuilder: (_, __) => const Divider(height: 1),
                            itemBuilder: (_, i) {
                              final c = _list[i];
                              return ListTile(
                                dense: true,
                                leading: CircleAvatar(radius: 16, backgroundColor: AppColors.primary, child: Text(c.name.isNotEmpty ? c.name[0].toUpperCase() : '?', style: const TextStyle(color: Colors.white, fontSize: 13))),
                                title: Text(c.name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                                subtitle: c.phone != null ? Text(c.phone!, style: const TextStyle(fontSize: 12)) : null,
                                onTap: () => Navigator.pop(context, c),
                              );
                            },
                          ),
              ),
            ]),
    );
  }
}

class _AddCustomerForm extends StatefulWidget {
  final void Function(PosCustomer) onCreated;
  final VoidCallback onCancel;
  const _AddCustomerForm({required this.onCreated, required this.onCancel});
  @override
  State<_AddCustomerForm> createState() => _AddCustomerFormState();
}

class _AddCustomerFormState extends State<_AddCustomerForm> {
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _address = TextEditingController();
  bool _busy = false;
  String? _error;

  Future<void> _save() async {
    if (_name.text.trim().isEmpty) { setState(() => _error = 'Name is required.'); return; }
    setState(() { _busy = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().post('/vendor/customers', {
        'name': _name.text.trim(),
        if (_phone.text.trim().isNotEmpty) 'phone': _phone.text.trim(),
        if (_email.text.trim().isNotEmpty) 'email': _email.text.trim(),
        if (_address.text.trim().isNotEmpty) 'address': _address.text.trim(),
      });
      widget.onCreated(PosCustomer.fromJson(Map<String, dynamic>.from(res['customer'])));
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
        Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.border, borderRadius: BorderRadius.circular(2)))),
        const SizedBox(height: 12),
        Row(children: [
          IconButton(onPressed: widget.onCancel, icon: const Icon(Icons.arrow_back)),
          const Text('New customer', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800)),
        ]),
        TextField(controller: _name, decoration: const InputDecoration(labelText: 'Name *', isDense: true)),
        const SizedBox(height: 10),
        TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Phone', isDense: true)),
        const SizedBox(height: 10),
        TextField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email', isDense: true)),
        const SizedBox(height: 10),
        TextField(controller: _address, decoration: const InputDecoration(labelText: 'Address', isDense: true)),
        if (_error != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error!, style: const TextStyle(color: AppColors.danger, fontSize: 12.5))),
        const SizedBox(height: 14),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _busy ? null : _save,
            child: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Save customer'),
          ),
        ),
      ]);
}
