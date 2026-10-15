import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';
import '../models/vendor_models.dart';
import 'delivery_screens.dart';

// ── Setup landing (grouped like the web sidebar) ─────────────────────────────
class SetupScreen extends StatelessWidget {
  const SetupScreen({super.key});

  @override
  Widget build(BuildContext context) {
    Widget tile(IconData icon, String title, String sub, Widget page) => Container(
          margin: const EdgeInsets.only(bottom: 8),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
          child: ListTile(
            leading: Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: .12), borderRadius: BorderRadius.circular(10)), child: Icon(icon, color: AppColors.primary)),
            title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
            subtitle: Text(sub, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
            trailing: const Icon(Icons.chevron_right, color: AppColors.muted),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => page)),
          ),
        );
    Widget header(String s) => Padding(padding: const EdgeInsets.fromLTRB(4, 14, 4, 8), child: Text(s.toUpperCase(), style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w800, color: AppColors.muted, letterSpacing: .5)));

    return Scaffold(
      appBar: AppBar(title: const Text('Setup')),
      body: ListView(padding: const EdgeInsets.fromLTRB(16, 8, 16, 24), children: [
        header('Catalog'),
        tile(Icons.category_outlined, 'Categories', 'Product categories', const TaxonomyScreen(kind: 'categories', title: 'Categories')),
        tile(Icons.sell_outlined, 'Brands', 'Product brands', const TaxonomyScreen(kind: 'brands', title: 'Brands')),
        tile(Icons.straighten_outlined, 'Units', 'Units of measure', const TaxonomyScreen(kind: 'units', title: 'Units')),
        tile(Icons.percent_outlined, 'Taxes', 'Tax rates', const TaxonomyScreen(kind: 'taxes', title: 'Taxes')),

        header('Places & people'),
        tile(Icons.warehouse_outlined, 'Warehouses', 'Add a stock location', const AddWarehouseScreen()),
        tile(Icons.group_outlined, 'People', 'Add staff and manage users', const PeopleScreen()),

        header('Store & delivery'),
        tile(Icons.local_shipping_outlined, 'Delivery', 'Agencies, zones & zone-to-zone pricing', const DeliveryScreen()),
        tile(Icons.storefront_outlined, 'Online store settings', 'Store info, logo, banner, payments', const StoreSettingsScreen()),
      ]),
    );
  }
}

// ── Generic taxonomy manager (categories / brands / units / taxes) ───────────
class TaxonomyScreen extends StatefulWidget {
  final String kind; // categories | brands | units | taxes
  final String title;
  const TaxonomyScreen({super.key, required this.kind, required this.title});
  @override
  State<TaxonomyScreen> createState() => _TaxonomyScreenState();
}

class _TaxonomyScreenState extends State<TaxonomyScreen> {
  List<Map<String, dynamic>> _items = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/setup/${widget.kind}');
      _items = (res['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _add() async {
    final name = TextEditingController();
    final second = TextEditingController(); // code (units) or rate (taxes)
    final isUnit = widget.kind == 'units';
    final isTax = widget.kind == 'taxes';
    final singular = widget.title.endsWith('s') ? widget.title.substring(0, widget.title.length - 1) : widget.title;
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('Add $singular'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          TextField(controller: name, autofocus: true, decoration: InputDecoration(labelText: '$singular name')),
          if (isUnit) Padding(padding: const EdgeInsets.only(top: 10), child: TextField(controller: second, decoration: const InputDecoration(labelText: 'Short code (e.g. PC, KG)'))),
          if (isTax) Padding(padding: const EdgeInsets.only(top: 10), child: TextField(controller: second, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(labelText: 'Rate %'))),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          ElevatedButton(onPressed: () => Navigator.pop(context, true), child: const Text('Add')),
        ],
      ),
    );
    if (ok == true && name.text.trim().isNotEmpty) {
      try {
        await context.read<ApiClient>().post('/vendor/setup/${widget.kind}', {
          'name': name.text.trim(),
          if (isUnit && second.text.trim().isNotEmpty) 'code': second.text.trim(),
          if (isTax) 'rate': double.tryParse(second.text.trim()) ?? 0,
        });
        _load();
      } catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      floatingActionButton: FloatingActionButton.extended(onPressed: _add, icon: const Icon(Icons.add), label: const Text('Add')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _items.isEmpty)
          ? ErrorRetry(message: _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: _items.isEmpty
                  ? ListView(children: [const SizedBox(height: 120), Center(child: Text('No ${widget.title.toLowerCase()} yet.', style: const TextStyle(color: AppColors.muted)))])
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(12, 12, 12, 90),
                      itemCount: _items.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 6),
                      itemBuilder: (_, i) => Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10), border: Border.all(color: AppColors.border)),
                        child: Text('${_items[i]['name']}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                      ),
                    ),
            ),
    );
  }
}

// ── Add warehouse ────────────────────────────────────────────────────────────
class AddWarehouseScreen extends StatefulWidget {
  const AddWarehouseScreen({super.key});
  @override
  State<AddWarehouseScreen> createState() => _AddWarehouseScreenState();
}

class _AddWarehouseScreenState extends State<AddWarehouseScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _address = TextEditingController();
  bool _busy = false;

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    setState(() => _busy = true);
    try {
      await context.read<ApiClient>().post('/vendor/warehouses', {
        'name': _name.text.trim(),
        if (_phone.text.trim().isNotEmpty) 'phone': _phone.text.trim(),
        if (_email.text.trim().isNotEmpty) 'email': _email.text.trim(),
        if (_address.text.trim().isNotEmpty) 'address': _address.text.trim(),
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Warehouse created.')));
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Add warehouse')),
      body: Form(
        key: _form,
        child: ListView(padding: const EdgeInsets.all(16), children: [
          _field(_name, 'Warehouse name *', validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null),
          _field(_phone, 'Phone', keyboard: TextInputType.phone),
          _field(_email, 'Email', keyboard: TextInputType.emailAddress),
          _field(_address, 'Address', maxLines: 2),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            height: 50,
            child: ElevatedButton(
              onPressed: _busy ? null : _save,
              child: _busy ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Create warehouse', style: TextStyle(fontWeight: FontWeight.w800)),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _field(TextEditingController c, String label, {TextInputType? keyboard, int maxLines = 1, String? Function(String?)? validator}) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextFormField(controller: c, keyboardType: keyboard, maxLines: maxLines, validator: validator, decoration: InputDecoration(labelText: label, border: const OutlineInputBorder())),
      );
}

// ── People (staff users) ─────────────────────────────────────────────────────
class PeopleScreen extends StatefulWidget {
  const PeopleScreen({super.key});
  @override
  State<PeopleScreen> createState() => _PeopleScreenState();
}

class _PeopleScreenState extends State<PeopleScreen> {
  List<Map<String, dynamic>> _people = [];
  bool _loading = true;
  String? _error;
  Map<String, dynamic>? _meta;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/people');
      _people = (res['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _add() async {
    _meta ??= Map<String, dynamic>.from(await context.read<ApiClient>().get('/vendor/people/meta'));
    if (!mounted) return;
    final created = await Navigator.push<bool>(context, MaterialPageRoute(builder: (_) => _AddPersonScreen(meta: _meta!)));
    if (created == true) _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('People')),
      floatingActionButton: FloatingActionButton.extended(onPressed: _add, icon: const Icon(Icons.person_add_alt), label: const Text('Add person')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _people.isEmpty)
          ? ErrorRetry(message: _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: _people.isEmpty
                  ? ListView(children: const [SizedBox(height: 120), Center(child: Text('No staff yet.', style: TextStyle(color: AppColors.muted)))])
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(12, 12, 12, 90),
                      itemCount: _people.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (_, i) {
                        final p = _people[i];
                        return Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
                          child: Row(children: [
                            CircleAvatar(backgroundColor: AppColors.primary.withValues(alpha: .12), child: Text('${p['name'] ?? '?'}'.characters.first.toUpperCase(), style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w800))),
                            const SizedBox(width: 12),
                            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Text('${p['name'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                              Text('${p['email'] ?? ''}', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
                            ])),
                            if (p['role'] != null)
                              Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3), decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(20)), child: Text('${p['role']}', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: AppColors.muted))),
                          ]),
                        );
                      },
                    ),
            ),
    );
  }
}

class _AddPersonScreen extends StatefulWidget {
  final Map<String, dynamic> meta;
  const _AddPersonScreen({required this.meta});
  @override
  State<_AddPersonScreen> createState() => _AddPersonScreenState();
}

class _AddPersonScreenState extends State<_AddPersonScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  List<IdName> _roles = [];
  List<IdName> _warehouses = [];
  List<IdName> _billers = [];
  IdName? _role, _warehouse, _biller;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    final m = widget.meta;
    _roles = (m['roles'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
    _warehouses = (m['warehouses'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
    _billers = (m['billers'] as List? ?? []).map((e) => IdName.fromJson(Map.from(e))).toList();
    if (_roles.isNotEmpty) _role = _roles.first;
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    if (_role == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Select a role.'))); return; }
    setState(() => _busy = true);
    try {
      await context.read<ApiClient>().post('/vendor/people', {
        'name': _name.text.trim(),
        'email': _email.text.trim(),
        'password': _password.text,
        'role_id': _role!.id,
        if (_phone.text.trim().isNotEmpty) 'phone': _phone.text.trim(),
        if (_warehouse != null) 'warehouse_id': _warehouse!.id,
        if (_biller != null) 'biller_id': _biller!.id,
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Person added.')));
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Add person')),
      body: Form(
        key: _form,
        child: ListView(padding: const EdgeInsets.all(16), children: [
          _field(_name, 'Full name *', validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null),
          _field(_email, 'Email *', keyboard: TextInputType.emailAddress, validator: (v) => (v == null || !v.contains('@')) ? 'Valid email required' : null),
          _field(_phone, 'Phone', keyboard: TextInputType.phone),
          _field(_password, 'Password *', obscure: true, validator: (v) => (v == null || v.length < 6) ? 'Min 6 characters' : null),
          _dropdown('Role *', _role, _roles, (v) => setState(() => _role = v)),
          _dropdown('Warehouse', _warehouse, _warehouses, (v) => setState(() => _warehouse = v)),
          _dropdown('Biller', _biller, _billers, (v) => setState(() => _biller = v)),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            height: 50,
            child: ElevatedButton(
              onPressed: _busy ? null : _save,
              child: _busy ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Add person', style: TextStyle(fontWeight: FontWeight.w800)),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _field(TextEditingController c, String label, {TextInputType? keyboard, bool obscure = false, String? Function(String?)? validator}) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextFormField(controller: c, keyboardType: keyboard, obscureText: obscure, validator: validator, decoration: InputDecoration(labelText: label, border: const OutlineInputBorder())),
      );

  Widget _dropdown(String label, IdName? value, List<IdName> items, ValueChanged<IdName?> onChanged) {
    if (items.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<IdName>(
        initialValue: value,
        isExpanded: true,
        decoration: InputDecoration(labelText: label, border: const OutlineInputBorder()),
        items: items.map((e) => DropdownMenuItem<IdName>(value: e, child: Text(e.name, overflow: TextOverflow.ellipsis))).toList(),
        onChanged: onChanged,
      ),
    );
  }
}

// ── Online store settings ────────────────────────────────────────────────────
class StoreSettingsScreen extends StatefulWidget {
  const StoreSettingsScreen({super.key});
  @override
  State<StoreSettingsScreen> createState() => _StoreSettingsScreenState();
}

class _StoreSettingsScreenState extends State<StoreSettingsScreen> {
  Map<String, dynamic> _s = {};
  bool _loading = true;
  bool _busy = false;
  String? _error;

  final _name = TextEditingController();
  final _about = TextEditingController();
  final _email = TextEditingController();
  final _whatsapp = TextEditingController();
  final _note = TextEditingController();
  final _instagram = TextEditingController();
  final _facebook = TextEditingController();
  final _tax = TextEditingController();
  File? _logo, _banner;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _pick(bool banner) async {
    final x = await ImagePicker().pickImage(source: ImageSource.gallery, maxWidth: banner ? 1600 : 800, imageQuality: 85);
    if (x != null) setState(() { if (banner) { _banner = File(x.path); } else { _logo = File(x.path); } });
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/store-settings');
      _s = Map<String, dynamic>.from(res['settings']);
      _name.text = '${_s['store_name'] ?? ''}';
      _about.text = '${_s['store_about'] ?? ''}';
      _email.text = '${_s['store_email'] ?? ''}';
      _whatsapp.text = '${_s['whatsapp_number'] ?? ''}';
      _note.text = '${_s['order_note'] ?? ''}';
      _instagram.text = '${_s['store_instagram'] ?? ''}';
      _facebook.text = '${_s['store_facebook'] ?? ''}';
      _tax.text = '${(_s['tax_rate'] ?? 0)}';
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  bool _b(String k) => (_s[k] ?? false) == true;

  Future<void> _save() async {
    setState(() => _busy = true);
    String s(bool v) => v ? '1' : '0';
    try {
      final res = await context.read<ApiClient>().postMultipart('/vendor/store-settings',
        fields: {
          'enabled': s(_b('enabled')),
          'store_name': _name.text.trim(),
          'store_about': _about.text.trim(),
          'store_email': _email.text.trim(),
          'whatsapp_number': _whatsapp.text.trim(),
          'order_note': _note.text.trim(),
          'store_instagram': _instagram.text.trim(),
          'store_facebook': _facebook.text.trim(),
          'tax_rate': '${double.tryParse(_tax.text.trim()) ?? 0}',
          'show_out_of_stock': s(_b('show_out_of_stock')),
          'allow_orders': s(_b('allow_orders')),
          'accept_stripe': s(_b('accept_stripe')),
          'accept_paystack': s(_b('accept_paystack')),
          'accept_bank_transfer': s(_b('accept_bank_transfer')),
          'accept_mtn': s(_b('accept_mtn')),
          'accept_orange': s(_b('accept_orange')),
          'accept_cod': s(_b('accept_cod')),
        },
        files: {
          if (_logo != null) 'store_logo': _logo!.path,
          if (_banner != null) 'store_banner': _banner!.path,
        },
      );
      // Reflect the persisted image URLs and clear the local picks.
      if (res is Map) {
        if (res['store_logo_url'] != null) _s['store_logo_url'] = res['store_logo_url'];
        if (res['store_banner_url'] != null) _s['store_banner_url'] = res['store_banner_url'];
      }
      if (mounted) setState(() { _logo = null; _banner = null; });
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Store settings saved.')));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Online store settings')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _s.isEmpty)
          ? ErrorRetry(message: _error!, onRetry: _load)
          : ListView(padding: const EdgeInsets.all(16), children: [
              _switch('Online store enabled', 'enabled'),
              const Divider(height: 24),
              // Banner
              GestureDetector(
                onTap: () => _pick(true),
                child: Container(
                  height: 120,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.border),
                    image: _banner != null
                        ? DecorationImage(image: FileImage(_banner!), fit: BoxFit.cover)
                        : (_s['store_banner_url'] != null ? DecorationImage(image: NetworkImage('${_s['store_banner_url']}'), fit: BoxFit.cover) : null),
                  ),
                  child: (_banner == null && _s['store_banner_url'] == null)
                      ? const Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.image_outlined, color: AppColors.muted), SizedBox(height: 4), Text('Add store banner', style: TextStyle(color: AppColors.muted, fontSize: 12))]))
                      : null,
                ),
              ),
              const SizedBox(height: 12),
              Row(children: [
                GestureDetector(
                  onTap: () => _pick(false),
                  child: Container(
                    width: 64, height: 64,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9), shape: BoxShape.circle, border: Border.all(color: AppColors.border),
                      image: _logo != null
                          ? DecorationImage(image: FileImage(_logo!), fit: BoxFit.cover)
                          : (_s['store_logo_url'] != null ? DecorationImage(image: NetworkImage('${_s['store_logo_url']}'), fit: BoxFit.cover) : null),
                    ),
                    child: (_logo == null && _s['store_logo_url'] == null) ? const Icon(Icons.add_a_photo_outlined, color: AppColors.muted, size: 20) : null,
                  ),
                ),
                const SizedBox(width: 12),
                const Expanded(child: Text('Tap to set your store logo and banner. Shown on your online store and marketplace shop.', style: TextStyle(fontSize: 12, color: AppColors.muted))),
              ]),
              const Divider(height: 24),
              _field(_name, 'Store name'),
              _field(_about, 'About', maxLines: 3),
              _field(_email, 'Store email', keyboard: TextInputType.emailAddress),
              _field(_whatsapp, 'WhatsApp number', keyboard: TextInputType.phone),
              _field(_instagram, 'Instagram'),
              _field(_facebook, 'Facebook'),
              _field(_note, 'Order note', maxLines: 2),
              _field(_tax, 'Tax rate (%)', keyboard: const TextInputType.numberWithOptions(decimal: true)),
              const SizedBox(height: 6),
              const Text('Options', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
              _switch('Show out-of-stock products', 'show_out_of_stock'),
              _switch('Allow orders', 'allow_orders'),
              const SizedBox(height: 6),
              const Text('Payment methods', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
              _switch('Stripe', 'accept_stripe'),
              _switch('Paystack', 'accept_paystack'),
              _switch('Bank transfer', 'accept_bank_transfer'),
              _switch('MTN Mobile Money', 'accept_mtn'),
              _switch('Orange Money', 'accept_orange'),
              _switch('Cash on delivery', 'accept_cod'),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: _busy ? null : _save,
                  child: _busy ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Save settings', style: TextStyle(fontWeight: FontWeight.w800)),
                ),
              ),
              const SizedBox(height: 24),
            ]),
    );
  }

  Widget _field(TextEditingController c, String label, {TextInputType? keyboard, int maxLines = 1}) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextField(controller: c, keyboardType: keyboard, maxLines: maxLines, decoration: InputDecoration(labelText: label, border: const OutlineInputBorder())),
      );

  Widget _switch(String label, String key) => SwitchListTile(
        contentPadding: EdgeInsets.zero,
        title: Text(label, style: const TextStyle(fontSize: 13.5)),
        value: _b(key),
        activeThumbColor: AppColors.primary,
        onChanged: (v) => setState(() => _s[key] = v),
      );
}
