import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:image_picker/image_picker.dart';
import 'package:novax_core/novax_core.dart';
import '../models/vendor_models.dart';

class ProductsScreen extends StatefulWidget {
  const ProductsScreen({super.key});
  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  final _search = TextEditingController();
  List<VendorProduct> _items = [];
  bool _loading = true;
  bool _lowOnly = false;
  String _q = '';
  String? _error;

  // Meta / filters
  List<IdName> _warehouses = [];
  List<IdName> _categories = [];
  IdName? _warehouse;
  IdName? _category;
  bool _marketEnabled = false;
  Map<String, dynamic>? _meta;

  @override
  void initState() {
    super.initState();
    _loadMeta();
    _load();
  }

  Future<void> _loadMeta() async {
    try {
      final res = await context.read<ApiClient>().get('/vendor/products/meta');
      _meta = Map<String, dynamic>.from(res);
      _marketEnabled = (res['marketplace_enabled'] ?? false) as bool;
      _warehouses = (res['warehouses'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
      _categories = (res['categories'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
      if (mounted) setState(() {});
    } catch (_) {/* filters are optional */}
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/products', query: {
        'q': _q,
        if (_lowOnly) 'low_stock': 1,
        if (_warehouse != null) 'warehouse_id': _warehouse!.id,
        if (_category != null) 'category_id': _category!.id,
      });
      _items = (res['data'] as List).map((e) => VendorProduct.fromJson(Map<String, dynamic>.from(e))).toList();
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _toggleMarket(VendorProduct p) async {
    if (!_marketEnabled && !p.listed) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Enable marketplace listing for your store first (Online Store settings).')));
      return;
    }
    final target = !p.listed;
    setState(() => p.listed = target); // optimistic
    try {
      await context.read<ApiClient>().post('/vendor/products/${p.id}/${target ? 'list-market' : 'unlist-market'}');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(target ? 'Listed on global market.' : 'Removed from global market.')));
      }
    } catch (e) {
      if (mounted) setState(() => p.listed = !target); // rollback
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _addProduct() async {
    if (_meta == null) await _loadMeta();
    final created = await Navigator.push<bool>(context,
        MaterialPageRoute(builder: (_) => _AddProductPage(meta: _meta ?? const {})));
    if (created == true) _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Products & Stock')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _addProduct,
        icon: const Icon(Icons.add),
        label: const Text('Add product'),
      ),
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 8, 12, 4),
          child: Row(children: [
            Expanded(
              child: TextField(
                controller: _search,
                onSubmitted: (v) { _q = v.trim(); _load(); },
                decoration: const InputDecoration(hintText: 'Search products…', prefixIcon: Icon(Icons.search), contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
              ),
            ),
            const SizedBox(width: 8),
            FilterChip(label: const Text('Low'), selected: _lowOnly, onSelected: (v) { setState(() => _lowOnly = v); _load(); }, selectedColor: const Color(0xFFFEE2E2)),
          ]),
        ),
        // Warehouse + category filters
        SizedBox(
          height: 44,
          child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 12), children: [
            _filterMenu<IdName?>(
              icon: Icons.warehouse_outlined,
              label: _warehouse?.name ?? 'All warehouses',
              value: _warehouse,
              items: [const PopupMenuItem<IdName?>(value: null, child: Text('All warehouses')),
                ..._warehouses.map((w) => PopupMenuItem<IdName?>(value: w, child: Text(w.name)))],
              onSelected: (v) { setState(() => _warehouse = v); _load(); },
            ),
            const SizedBox(width: 8),
            _filterMenu<IdName?>(
              icon: Icons.category_outlined,
              label: _category?.name ?? 'All categories',
              value: _category,
              items: [const PopupMenuItem<IdName?>(value: null, child: Text('All categories')),
                ..._categories.map((c) => PopupMenuItem<IdName?>(value: c, child: Text(c.name)))],
              onSelected: (v) { setState(() => _category = v); _load(); },
            ),
          ]),
        ),
        const SizedBox(height: 4),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: () async { await _loadMeta(); await _load(); },
                  child: _error != null && _items.isEmpty
                      ? ErrorRetry(message: _error!, onRetry: _load)
                      : _items.isEmpty
                      ? ListView(children: const [SizedBox(height: 120), Center(child: Text('No products.', style: TextStyle(color: AppColors.muted)))])
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(12, 4, 12, 90),
                          itemCount: _items.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (_, i) => _tile(_items[i]),
                        ),
                ),
        ),
      ]),
    );
  }

  Widget _filterMenu<T>({required IconData icon, required String label, required T value, required List<PopupMenuEntry<T>> items, required ValueChanged<T> onSelected}) {
    final active = value != null;
    return PopupMenuButton<T>(
      itemBuilder: (_) => items,
      onSelected: onSelected,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: active ? AppColors.primary.withValues(alpha: .10) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: active ? AppColors.primary : AppColors.border),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 16, color: active ? AppColors.primary : AppColors.muted),
          const SizedBox(width: 6),
          Text(label, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: active ? AppColors.primary : AppColors.ink)),
          const Icon(Icons.arrow_drop_down, size: 18, color: AppColors.muted),
        ]),
      ),
    );
  }

  Widget _tile(VendorProduct p) {
    final showWh = _warehouse != null && p.warehouseQty != null;
    final stock = showWh ? p.warehouseQty! : p.stock;
    final out = stock <= 0;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
      child: Column(children: [
        Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: SizedBox(
              width: 54, height: 54,
              child: p.image != null
                  ? CachedNetworkImage(imageUrl: p.image!, fit: BoxFit.cover, errorWidget: (_, __, ___) => const _Ph())
                  : const _Ph(),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(p.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              const SizedBox(height: 2),
              Text([if (p.code != null) '#${p.code}', if (p.category != null) p.category, if (p.brand != null) p.brand].join(' · '),
                  style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
              const SizedBox(height: 4),
              Text(p.price.toStringAsFixed(2), style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14, color: AppColors.primary)),
            ]),
          ),
          Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
            Text(stock.toStringAsFixed(0), style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17, color: out ? const Color(0xFFEF4444) : AppColors.dark)),
            Text(showWh ? 'in warehouse' : 'total stock', style: const TextStyle(fontSize: 9.5, color: AppColors.muted)),
          ]),
        ]),
        const SizedBox(height: 10),
        Row(children: [
          _badge(out ? 'Out of stock' : 'In stock', out ? const Color(0xFFEF4444) : const Color(0xFF22C55E)),
          if (p.low && !out) ...[const SizedBox(width: 6), _badge('Low', const Color(0xFFF59E0B))],
          const Spacer(),
          Text(p.listed ? 'On market' : 'Off market', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: p.listed ? AppColors.primary : AppColors.muted)),
          Switch(
            value: p.listed,
            onChanged: (_) => _toggleMarket(p),
            activeThumbColor: AppColors.primary,
            materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
        ]),
      ]),
    );
  }

  Widget _badge(String text, Color c) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(color: c.withValues(alpha: .12), borderRadius: BorderRadius.circular(20)),
        child: Text(text, style: TextStyle(color: c, fontSize: 11, fontWeight: FontWeight.w700)),
      );
}

class _Ph extends StatelessWidget {
  const _Ph();
  @override
  Widget build(BuildContext context) => Container(color: const Color(0xFFF1F5F9), child: const Icon(Icons.inventory_2_outlined, color: Color(0xFFCBD5E1), size: 22));
}

// ── Add product ─────────────────────────────────────────────────────────────
class _AddProductPage extends StatefulWidget {
  final Map<String, dynamic> meta;
  const _AddProductPage({required this.meta});
  @override
  State<_AddProductPage> createState() => _AddProductPageState();
}

class _AddProductPageState extends State<_AddProductPage> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _code = TextEditingController();
  final _price = TextEditingController();
  final _cost = TextEditingController();
  final _qty = TextEditingController(text: '0');
  final _alert = TextEditingController(text: '0');
  final _details = TextEditingController();

  List<IdName> _categories = [];
  List<IdName> _brands = [];
  List<IdName> _units = [];
  List<IdName> _warehouses = [];
  List<TaxOpt> _taxes = [];
  IdName? _category, _brand, _unit, _warehouse;
  TaxOpt? _tax;

  final List<File> _images = [];
  String _type = 'standard'; // standard | digital | service
  bool get _digital => _type == 'digital' || _type == 'service';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final m = widget.meta;
    _categories = (m['categories'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
    _brands = (m['brands'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
    _units = (m['units'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
    _warehouses = (m['warehouses'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
    _taxes = (m['taxes'] as List? ?? []).map((e) => TaxOpt.fromJson(Map.from(e))).toList();
    if (_units.isNotEmpty) _unit = _units.first;
    if (_warehouses.isNotEmpty) _warehouse = _warehouses.first;
  }

  Future<void> _pick() async {
    final xs = await ImagePicker().pickMultiImage(maxWidth: 1400, imageQuality: 85);
    if (xs.isNotEmpty) {
      setState(() {
        for (final x in xs) {
          if (_images.length < 6) _images.add(File(x.path));
        }
      });
    }
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final fields = <String, String>{
        'name': _name.text.trim(),
        'type': _type,
        if (_code.text.trim().isNotEmpty) 'code': _code.text.trim(),
        'price': _price.text.trim(),
        if (!_digital && _cost.text.trim().isNotEmpty) 'cost': _cost.text.trim(),
        if (!_digital) 'qty': _qty.text.trim().isEmpty ? '0' : _qty.text.trim(),
        if (!_digital) 'alert_quantity': _alert.text.trim().isEmpty ? '0' : _alert.text.trim(),
        if (_category != null) 'category_id': '${_category!.id}',
        if (_brand != null) 'brand_id': '${_brand!.id}',
        if (!_digital && _unit != null) 'unit_id': '${_unit!.id}',
        if (_tax != null) 'tax_id': '${_tax!.id}',
        if (!_digital && _warehouse != null) 'warehouse_id': '${_warehouse!.id}',
        if (_details.text.trim().isNotEmpty) 'product_details': _details.text.trim(),
      };
      final files = <String, String>{
        for (var i = 0; i < _images.length; i++) 'images[$i]': _images[i].path,
      };
      await context.read<ApiClient>().postMultipart('/vendor/products', fields: fields, files: files);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Product created.')));
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Add product')),
      body: Form(
        key: _form,
        child: ListView(padding: const EdgeInsets.all(16), children: [
          // Product type
          _TypeSelector(value: _type, onChanged: (t) => setState(() => _type = t)),
          const SizedBox(height: 14),
          // Images (multiple)
          _imageStrip(),
          const SizedBox(height: 16),
          _field(_name, 'Product name *', validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null),
          _field(_code, 'Code (auto if blank)'),
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Expanded(child: _field(_price, 'Price *', number: true, validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null)),
            if (!_digital) ...[
              const SizedBox(width: 12),
              Expanded(child: _field(_cost, 'Cost', number: true)),
            ],
          ]),
          if (!_digital)
            Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Expanded(child: _field(_qty, 'Opening stock', number: true)),
              const SizedBox(width: 12),
              Expanded(child: _field(_alert, 'Alert qty', number: true)),
            ]),
          _dropdown<IdName>('Category', _category, _categories, (v) => setState(() => _category = v)),
          _dropdown<IdName>('Brand', _brand, _brands, (v) => setState(() => _brand = v)),
          if (!_digital) _dropdown<IdName>('Unit', _unit, _units, (v) => setState(() => _unit = v)),
          _dropdownTax('Tax', _tax, _taxes, (v) => setState(() => _tax = v)),
          if (!_digital) _dropdown<IdName>('Warehouse', _warehouse, _warehouses, (v) => setState(() => _warehouse = v)),
          const SizedBox(height: 4),
          const Align(alignment: Alignment.centerLeft, child: Text('Description', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5))),
          const SizedBox(height: 6),
          _RichDescriptionField(controller: _details),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            height: 50,
            child: ElevatedButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Text('Create product', style: TextStyle(fontWeight: FontWeight.w800)),
            ),
          ),
          const SizedBox(height: 24),
        ]),
      ),
    );
  }

  Widget _imageStrip() => SizedBox(
        height: 88,
        child: ListView(scrollDirection: Axis.horizontal, children: [
          if (_images.length < 6)
            GestureDetector(
              onTap: _pick,
              child: Container(
                width: 88, height: 88,
                margin: const EdgeInsets.only(right: 8),
                decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
                child: const Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                  Icon(Icons.add_a_photo_outlined, color: AppColors.muted),
                  SizedBox(height: 4),
                  Text('Add photos', style: TextStyle(fontSize: 10, color: AppColors.muted)),
                ]),
              ),
            ),
          ..._images.asMap().entries.map((e) => Stack(children: [
                Container(
                  width: 88, height: 88,
                  margin: const EdgeInsets.only(right: 8),
                  decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), image: DecorationImage(image: FileImage(e.value), fit: BoxFit.cover)),
                ),
                Positioned(
                  right: 12, top: 2,
                  child: GestureDetector(
                    onTap: () => setState(() => _images.removeAt(e.key)),
                    child: Container(padding: const EdgeInsets.all(2), decoration: const BoxDecoration(color: Colors.black54, shape: BoxShape.circle), child: const Icon(Icons.close, size: 14, color: Colors.white)),
                  ),
                ),
              ])),
        ]),
      );

  Widget _field(TextEditingController c, String label, {bool number = false, int maxLines = 1, String? Function(String?)? validator}) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextFormField(
          controller: c,
          maxLines: maxLines,
          keyboardType: number ? const TextInputType.numberWithOptions(decimal: true) : TextInputType.text,
          inputFormatters: number ? [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))] : null,
          validator: validator,
          decoration: InputDecoration(labelText: label, border: const OutlineInputBorder()),
        ),
      );

  Widget _dropdown<T extends IdName>(String label, T? value, List<T> items, ValueChanged<T?> onChanged) {
    if (items.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<T>(
        initialValue: value,
        isExpanded: true,
        decoration: InputDecoration(labelText: label, border: const OutlineInputBorder()),
        items: items.map((e) => DropdownMenuItem<T>(value: e, child: Text(e.name, overflow: TextOverflow.ellipsis))).toList(),
        onChanged: onChanged,
      ),
    );
  }

  Widget _dropdownTax(String label, TaxOpt? value, List<TaxOpt> items, ValueChanged<TaxOpt?> onChanged) {
    if (items.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<TaxOpt>(
        initialValue: value,
        isExpanded: true,
        decoration: InputDecoration(labelText: label, border: const OutlineInputBorder()),
        items: items.map((e) => DropdownMenuItem<TaxOpt>(value: e, child: Text('${e.name} (${e.rate.toStringAsFixed(0)}%)'))).toList(),
        onChanged: onChanged,
      ),
    );
  }
}

/// Rich description editor — a formatting toolbar over a multiline field that
/// stores simple HTML (matches the web's TinyMCE product_details output).
class _RichDescriptionField extends StatelessWidget {
  final TextEditingController controller;
  const _RichDescriptionField({required this.controller});

  void _wrap(String open, String close) {
    final sel = controller.selection;
    final text = controller.text;
    if (!sel.isValid) {
      final t = '$text$open$close';
      controller.value = TextEditingValue(text: t, selection: TextSelection.collapsed(offset: (text + open).length));
      return;
    }
    final start = sel.start, end = sel.end;
    final selected = text.substring(start, end);
    final replaced = '$open$selected$close';
    final newText = text.replaceRange(start, end, replaced);
    controller.value = TextEditingValue(
      text: newText,
      selection: TextSelection.collapsed(offset: start + replaced.length),
    );
  }

  void _insert(String snippet) {
    final sel = controller.selection;
    final text = controller.text;
    final at = sel.isValid ? sel.end : text.length;
    final newText = text.replaceRange(at, at, snippet);
    controller.value = TextEditingValue(text: newText, selection: TextSelection.collapsed(offset: at + snippet.length));
  }

  @override
  Widget build(BuildContext context) {
    Widget btn(IconData icon, VoidCallback onTap, {String? tip}) => IconButton(
          tooltip: tip,
          visualDensity: VisualDensity.compact,
          constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
          padding: EdgeInsets.zero,
          icon: Icon(icon, size: 18),
          onPressed: onTap,
        );
    return Container(
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(6), border: Border.all(color: AppColors.border)),
      child: Column(children: [
        Container(
          decoration: const BoxDecoration(color: Color(0xFFF8FAFC), border: Border(bottom: BorderSide(color: AppColors.border))),
          child: Row(children: [
            btn(Icons.format_bold, () => _wrap('<b>', '</b>'), tip: 'Bold'),
            btn(Icons.format_italic, () => _wrap('<i>', '</i>'), tip: 'Italic'),
            btn(Icons.format_underlined, () => _wrap('<u>', '</u>'), tip: 'Underline'),
            btn(Icons.title, () => _wrap('<h4>', '</h4>'), tip: 'Heading'),
            btn(Icons.format_list_bulleted, () => _insert('\n<ul><li></li></ul>'), tip: 'Bullet list'),
            btn(Icons.subject, () => _insert('<br>'), tip: 'Line break'),
          ]),
        ),
        TextField(
          controller: controller,
          maxLines: 7,
          minLines: 5,
          decoration: const InputDecoration(
            hintText: 'Describe the product, features, condition…',
            contentPadding: EdgeInsets.all(12),
            border: InputBorder.none,
          ),
        ),
      ]),
    );
  }
}

/// Standard / Digital / Service product-type segmented selector.
class _TypeSelector extends StatelessWidget {
  final String value;
  final ValueChanged<String> onChanged;
  const _TypeSelector({required this.value, required this.onChanged});

  static const _types = [
    ('standard', 'Standard', Icons.inventory_2_outlined),
    ('digital', 'Digital', Icons.cloud_download_outlined),
    ('service', 'Service', Icons.handyman_outlined),
  ];

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(10)),
      child: Row(children: _types.map((t) {
        final sel = value == t.$1;
        return Expanded(
          child: GestureDetector(
            onTap: () => onChanged(t.$1),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 150),
              padding: const EdgeInsets.symmetric(vertical: 8),
              decoration: BoxDecoration(color: sel ? Colors.white : Colors.transparent, borderRadius: BorderRadius.circular(8), boxShadow: sel ? [const BoxShadow(color: Color(0x14000000), blurRadius: 4, offset: Offset(0, 1))] : null),
              child: Column(children: [
                Icon(t.$3, size: 18, color: sel ? AppColors.primary : AppColors.muted),
                const SizedBox(height: 2),
                Text(t.$2, style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: sel ? AppColors.primary : AppColors.muted)),
              ]),
            ),
          ),
        );
      }).toList()),
    );
  }
}
