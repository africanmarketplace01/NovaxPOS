import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';
import '../models/vendor_models.dart';

// ── Agencies list ────────────────────────────────────────────────────────────
class DeliveryScreen extends StatefulWidget {
  const DeliveryScreen({super.key});
  @override
  State<DeliveryScreen> createState() => _DeliveryScreenState();
}

class _DeliveryScreenState extends State<DeliveryScreen> {
  List<Map<String, dynamic>> _agencies = [];
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
      final res = await context.read<ApiClient>().get('/vendor/delivery/agencies');
      _agencies = (res['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _addAgency() async {
    final created = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(18))),
      builder: (_) => const _AgencyForm(),
    );
    if (created == true) _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Delivery agencies')),
      floatingActionButton: FloatingActionButton.extended(onPressed: _addAgency, icon: const Icon(Icons.add), label: const Text('Add agency')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _agencies.isEmpty)
          ? ErrorRetry(message: _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: _agencies.isEmpty
                  ? ListView(children: const [
                      SizedBox(height: 80),
                      Icon(Icons.local_shipping_outlined, size: 48, color: AppColors.muted),
                      SizedBox(height: 12),
                      Center(child: Padding(padding: EdgeInsets.symmetric(horizontal: 40), child: Text('Add your own delivery agency to set zones and zone-to-zone pricing.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted)))),
                    ])
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(12, 12, 12, 90),
                      itemCount: _agencies.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (_, i) {
                        final a = _agencies[i];
                        return Container(
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
                          child: ListTile(
                            leading: Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: .12), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.local_shipping_outlined, color: AppColors.primary)),
                            title: Text('${a['name']}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                            subtitle: Text('${a['zones_count'] ?? 0} zone(s) · base ${a['currency'] ?? ''} ${((a['base_rate'] ?? 0) as num).toStringAsFixed(2)}', style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
                            trailing: const Icon(Icons.chevron_right, color: AppColors.muted),
                            onTap: () async {
                              await Navigator.push(context, MaterialPageRoute(builder: (_) => AgencyZonesScreen(agencyId: a['id'] as int, agencyName: '${a['name']}')));
                              _load();
                            },
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}

class _AgencyForm extends StatefulWidget {
  const _AgencyForm();
  @override
  State<_AgencyForm> createState() => _AgencyFormState();
}

class _AgencyFormState extends State<_AgencyForm> {
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _rate = TextEditingController(text: '0');
  bool _busy = false;

  Future<void> _save() async {
    if (_name.text.trim().isEmpty) return;
    setState(() => _busy = true);
    try {
      await context.read<ApiClient>().post('/vendor/delivery/agencies', {
        'name': _name.text.trim(),
        if (_phone.text.trim().isNotEmpty) 'phone': _phone.text.trim(),
        'base_rate': double.tryParse(_rate.text.trim()) ?? 0,
      });
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + MediaQuery.of(context).viewInsets.bottom),
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Text('New delivery agency', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
        const SizedBox(height: 14),
        TextField(controller: _name, decoration: const InputDecoration(labelText: 'Agency name *', border: OutlineInputBorder())),
        const SizedBox(height: 12),
        TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Phone', border: OutlineInputBorder())),
        const SizedBox(height: 12),
        TextField(controller: _rate, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(labelText: 'Base delivery rate', border: OutlineInputBorder())),
        const SizedBox(height: 16),
        SizedBox(width: double.infinity, height: 48, child: ElevatedButton(onPressed: _busy ? null : _save, child: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Create agency', style: TextStyle(fontWeight: FontWeight.w800)))),
      ]),
    );
  }
}

// ── Zones + pricing ──────────────────────────────────────────────────────────
class AgencyZonesScreen extends StatefulWidget {
  final int agencyId;
  final String agencyName;
  const AgencyZonesScreen({super.key, required this.agencyId, required this.agencyName});
  @override
  State<AgencyZonesScreen> createState() => _AgencyZonesScreenState();
}

class _AgencyZonesScreenState extends State<AgencyZonesScreen> {
  List<Map<String, dynamic>> _zones = [];
  List<Map<String, dynamic>> _prices = [];
  String _currency = '';
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
      final res = await context.read<ApiClient>().get('/vendor/delivery/agencies/${widget.agencyId}/zones');
      _zones = (res['zones'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
      _prices = (res['prices'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
      _currency = '${res['currency'] ?? ''}';
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _zoneName(int id) => _zones.firstWhere((z) => z['id'] == id, orElse: () => {'name': '?'})['name'] as String;

  Future<void> _addZone() async {
    final ctrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Add zone'),
        content: TextField(controller: ctrl, autofocus: true, decoration: const InputDecoration(hintText: 'Zone name (e.g. Sinkor)')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          ElevatedButton(onPressed: () => Navigator.pop(context, true), child: const Text('Add')),
        ],
      ),
    );
    if (ok == true && ctrl.text.trim().isNotEmpty) {
      try {
        await context.read<ApiClient>().post('/vendor/delivery/agencies/${widget.agencyId}/zones', {'name': ctrl.text.trim()});
        _load();
      } catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  Future<void> _deleteZone(int id) async {
    try {
      await context.read<ApiClient>().delete('/vendor/delivery/zones/$id');
      _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _setPrice() async {
    if (_zones.isEmpty) return;
    int? from = _zones.first['id'] as int?;
    int? to = _zones.first['id'] as int?;
    final priceCtrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => StatefulBuilder(builder: (context, setD) => AlertDialog(
        title: const Text('Set zone-to-zone price'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          DropdownButtonFormField<int>(
            initialValue: from, isExpanded: true, decoration: const InputDecoration(labelText: 'From zone'),
            items: _zones.map((z) => DropdownMenuItem(value: z['id'] as int, child: Text('${z['name']}'))).toList(),
            onChanged: (v) => setD(() => from = v),
          ),
          const SizedBox(height: 8),
          DropdownButtonFormField<int>(
            initialValue: to, isExpanded: true, decoration: const InputDecoration(labelText: 'To zone'),
            items: _zones.map((z) => DropdownMenuItem(value: z['id'] as int, child: Text('${z['name']}'))).toList(),
            onChanged: (v) => setD(() => to = v),
          ),
          const SizedBox(height: 8),
          TextField(controller: priceCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))], decoration: const InputDecoration(labelText: 'Price')),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          ElevatedButton(onPressed: () => Navigator.pop(context, true), child: const Text('Save')),
        ],
      )),
    );
    if (ok == true && from != null && to != null && priceCtrl.text.trim().isNotEmpty) {
      try {
        await context.read<ApiClient>().post('/vendor/delivery/agencies/${widget.agencyId}/prices', {
          'from_zone_id': from, 'to_zone_id': to, 'price': double.tryParse(priceCtrl.text.trim()) ?? 0,
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
      appBar: AppBar(title: Text(widget.agencyName, maxLines: 1, overflow: TextOverflow.ellipsis)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _zones.isEmpty)
          ? ErrorRetry(message: _error!, onRetry: _load)
          : ListView(padding: const EdgeInsets.all(16), children: [
              Row(children: [
                Expanded(child: OutlinedButton.icon(
                  onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => AgentsScreen(agencyId: widget.agencyId, agencyName: widget.agencyName))),
                  icon: const Icon(Icons.person_pin_circle_outlined, size: 18), label: const Text('Agents'),
                )),
                const SizedBox(width: 10),
                Expanded(child: OutlinedButton.icon(
                  onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => BikesScreen(agencyId: widget.agencyId, agencyName: widget.agencyName))),
                  icon: const Icon(Icons.two_wheeler_outlined, size: 18), label: const Text('Bikes'),
                )),
              ]),
              const SizedBox(height: 18),
              Row(children: [
                const Text('Zones', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                const Spacer(),
                TextButton.icon(onPressed: _addZone, icon: const Icon(Icons.add, size: 18), label: const Text('Add zone')),
              ]),
              if (_zones.isEmpty)
                const Padding(padding: EdgeInsets.symmetric(vertical: 8), child: Text('No zones yet. Add zones like "Sinkor", "Paynesville".', style: TextStyle(color: AppColors.muted)))
              else
                Wrap(spacing: 8, runSpacing: 8, children: _zones.map((z) => Chip(
                      label: Text('${z['name']}'),
                      onDeleted: () => _deleteZone(z['id'] as int),
                      deleteIcon: const Icon(Icons.close, size: 16),
                    )).toList()),
              const SizedBox(height: 20),
              Row(children: [
                const Text('Zone-to-zone pricing', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                const Spacer(),
                if (_zones.isNotEmpty) TextButton.icon(onPressed: _setPrice, icon: const Icon(Icons.add, size: 18), label: const Text('Set price')),
              ]),
              if (_prices.isEmpty)
                const Padding(padding: EdgeInsets.symmetric(vertical: 8), child: Text('No pricing set. Prices apply when a customer picks their from/to zones.', style: TextStyle(color: AppColors.muted)))
              else
                ..._prices.map((p) => Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10), border: Border.all(color: AppColors.border)),
                      child: Row(children: [
                        Expanded(child: Row(children: [
                          Flexible(child: Text(_zoneName(p['from_zone_id'] as int), overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13))),
                          const Padding(padding: EdgeInsets.symmetric(horizontal: 6), child: Icon(Icons.arrow_forward, size: 14, color: AppColors.muted)),
                          Flexible(child: Text(_zoneName(p['to_zone_id'] as int), overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13))),
                        ])),
                        Text('$_currency ${((p['price'] ?? 0) as num).toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w800, color: AppColors.primary)),
                      ]),
                    )),
            ]),
    );
  }
}

// ── Agents (riders) ──────────────────────────────────────────────────────────
class AgentsScreen extends StatefulWidget {
  final int agencyId;
  final String agencyName;
  const AgentsScreen({super.key, required this.agencyId, required this.agencyName});
  @override
  State<AgentsScreen> createState() => _AgentsScreenState();
}

class _AgentsScreenState extends State<AgentsScreen> {
  List<Map<String, dynamic>> _agents = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/delivery/agencies/${widget.agencyId}/agents');
      _agents = (res['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    } catch (e) { if (mounted) setState(() => _error = e.toString()); }
    finally { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _add() async {
    final created = await Navigator.push<bool>(context, MaterialPageRoute(builder: (_) => _AgentForm(agencyId: widget.agencyId)));
    if (created == true) _load();
  }

  Future<void> _delete(int id) async {
    try { await context.read<ApiClient>().delete('/vendor/delivery/agents/$id'); _load(); }
    catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('${widget.agencyName} · Agents', maxLines: 1, overflow: TextOverflow.ellipsis)),
      floatingActionButton: FloatingActionButton.extended(onPressed: _add, icon: const Icon(Icons.person_add_alt), label: const Text('Add agent')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _agents.isEmpty)
          ? ErrorRetry(message: _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: _agents.isEmpty
                  ? ListView(children: const [SizedBox(height: 120), Center(child: Text('No agents yet. Add riders who deliver orders.', style: TextStyle(color: AppColors.muted)))])
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(12, 12, 12, 90),
                      itemCount: _agents.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (_, i) {
                        final a = _agents[i];
                        return Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
                          child: Row(children: [
                            CircleAvatar(radius: 24, backgroundColor: const Color(0xFFEEF2F7), backgroundImage: a['photo_url'] != null ? NetworkImage('${a['photo_url']}') : null, child: a['photo_url'] == null ? const Icon(Icons.person, color: AppColors.muted) : null),
                            const SizedBox(width: 12),
                            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Text('${a['name']}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                              if (a['phone'] != null) Text('${a['phone']}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                              if (a['bike'] != null) Text('🏍 ${a['bike']['label']} · ${a['bike']['plate']}', style: const TextStyle(color: AppColors.primary, fontSize: 11.5)),
                            ])),
                            IconButton(icon: const Icon(Icons.delete_outline, color: AppColors.muted, size: 20), onPressed: () => _delete(a['id'] as int)),
                          ]),
                        );
                      },
                    ),
            ),
    );
  }
}

class _AgentForm extends StatefulWidget {
  final int agencyId;
  const _AgentForm({required this.agencyId});
  @override
  State<_AgentForm> createState() => _AgentFormState();
}

class _AgentFormState extends State<_AgentForm> {
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _license = TextEditingController();
  File? _photo;
  bool _busy = false;

  Future<void> _pick() async {
    final x = await ImagePicker().pickImage(source: ImageSource.gallery, maxWidth: 800, imageQuality: 85);
    if (x != null) setState(() => _photo = File(x.path));
  }

  Future<void> _save() async {
    if (_name.text.trim().isEmpty) return;
    setState(() => _busy = true);
    try {
      await context.read<ApiClient>().postMultipart('/vendor/delivery/agencies/${widget.agencyId}/agents',
        fields: {
          'name': _name.text.trim(),
          if (_phone.text.trim().isNotEmpty) 'phone': _phone.text.trim(),
          if (_email.text.trim().isNotEmpty) 'email': _email.text.trim(),
          if (_license.text.trim().isNotEmpty) 'license_number': _license.text.trim(),
        },
        files: _photo != null ? {'photo': _photo!.path} : const {},
      );
      if (mounted) Navigator.pop(context, true);
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); }
    finally { if (mounted) setState(() => _busy = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Add agent')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Center(child: GestureDetector(
          onTap: _pick,
          child: CircleAvatar(radius: 44, backgroundColor: const Color(0xFFF1F5F9), backgroundImage: _photo != null ? FileImage(_photo!) : null,
            child: _photo == null ? const Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.add_a_photo_outlined, color: AppColors.muted), SizedBox(height: 2), Text('Photo', style: TextStyle(fontSize: 10, color: AppColors.muted))]) : null),
        )),
        const SizedBox(height: 18),
        _f(_name, 'Full name *'),
        _f(_phone, 'Phone', kb: TextInputType.phone),
        _f(_email, 'Email', kb: TextInputType.emailAddress),
        _f(_license, "Driver's license number"),
        const SizedBox(height: 16),
        SizedBox(width: double.infinity, height: 50, child: ElevatedButton(onPressed: _busy ? null : _save, child: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Save agent', style: TextStyle(fontWeight: FontWeight.w800)))),
      ]),
    );
  }

  Widget _f(TextEditingController c, String label, {TextInputType? kb}) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextField(controller: c, keyboardType: kb, decoration: InputDecoration(labelText: label, border: const OutlineInputBorder())),
      );
}

// ── Bikes ──────────────────────────────────────────────────────────────────
class BikesScreen extends StatefulWidget {
  final int agencyId;
  final String agencyName;
  const BikesScreen({super.key, required this.agencyId, required this.agencyName});
  @override
  State<BikesScreen> createState() => _BikesScreenState();
}

class _BikesScreenState extends State<BikesScreen> {
  List<Map<String, dynamic>> _bikes = [];
  List<IdName> _agents = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/delivery/agencies/${widget.agencyId}/bikes');
      _bikes = (res['bikes'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
      _agents = (res['agents'] as List).map((e) => IdName.fromJson(Map.from(e))).toList();
    } catch (e) { if (mounted) setState(() => _error = e.toString()); }
    finally { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _add() async {
    final created = await Navigator.push<bool>(context, MaterialPageRoute(builder: (_) => _BikeForm(agencyId: widget.agencyId, agents: _agents)));
    if (created == true) _load();
  }

  Future<void> _delete(int id) async {
    try { await context.read<ApiClient>().delete('/vendor/delivery/bikes/$id'); _load(); }
    catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('${widget.agencyName} · Bikes', maxLines: 1, overflow: TextOverflow.ellipsis)),
      floatingActionButton: FloatingActionButton.extended(onPressed: _add, icon: const Icon(Icons.add), label: const Text('Add bike')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null && _bikes.isEmpty)
          ? ErrorRetry(message: _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: _bikes.isEmpty
                  ? ListView(children: const [SizedBox(height: 120), Center(child: Text('No bikes yet. Add a bike and assign it to an agent.', style: TextStyle(color: AppColors.muted)))])
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(12, 12, 12, 90),
                      itemCount: _bikes.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (_, i) {
                        final b = _bikes[i];
                        return Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
                          child: Row(children: [
                            Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: .12), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.two_wheeler, color: AppColors.primary)),
                            const SizedBox(width: 12),
                            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Text([b['make'], b['model']].where((x) => x != null && '$x'.isNotEmpty).join(' ').trim().isEmpty ? 'Bike' : [b['make'], b['model']].where((x) => x != null).join(' '), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                              Text('Plate: ${b['plate_number'] ?? '—'}${b['color'] != null ? ' · ${b['color']}' : ''}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                              Text(b['agent_name'] != null ? '👤 ${b['agent_name']}' : 'Unassigned', style: TextStyle(color: b['agent_name'] != null ? AppColors.primary : AppColors.muted, fontSize: 11.5)),
                            ])),
                            IconButton(icon: const Icon(Icons.delete_outline, color: AppColors.muted, size: 20), onPressed: () => _delete(b['id'] as int)),
                          ]),
                        );
                      },
                    ),
            ),
    );
  }
}

class _BikeForm extends StatefulWidget {
  final int agencyId;
  final List<IdName> agents;
  const _BikeForm({required this.agencyId, required this.agents});
  @override
  State<_BikeForm> createState() => _BikeFormState();
}

class _BikeFormState extends State<_BikeForm> {
  final _plate = TextEditingController();
  final _make = TextEditingController();
  final _model = TextEditingController();
  final _color = TextEditingController();
  final _year = TextEditingController();
  final _reg = TextEditingController();
  final _ins = TextEditingController();
  final _note = TextEditingController();
  IdName? _agent;
  bool _busy = false;

  Future<void> _save() async {
    if (_plate.text.trim().isEmpty) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Plate number is required.'))); return; }
    setState(() => _busy = true);
    try {
      await context.read<ApiClient>().post('/vendor/delivery/agencies/${widget.agencyId}/bikes', {
        'plate_number': _plate.text.trim(),
        if (_make.text.trim().isNotEmpty) 'make': _make.text.trim(),
        if (_model.text.trim().isNotEmpty) 'model': _model.text.trim(),
        if (_color.text.trim().isNotEmpty) 'color': _color.text.trim(),
        if (_year.text.trim().isNotEmpty) 'year': _year.text.trim(),
        if (_reg.text.trim().isNotEmpty) 'registration_no': _reg.text.trim(),
        if (_ins.text.trim().isNotEmpty) 'insurance_no': _ins.text.trim(),
        if (_note.text.trim().isNotEmpty) 'note': _note.text.trim(),
        if (_agent != null) 'agent_id': _agent!.id,
      });
      if (mounted) Navigator.pop(context, true);
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); }
    finally { if (mounted) setState(() => _busy = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Add bike')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        _f(_plate, 'License plate number *'),
        Row(children: [Expanded(child: _f(_make, 'Make (e.g. Honda)')), const SizedBox(width: 12), Expanded(child: _f(_model, 'Model'))]),
        Row(children: [Expanded(child: _f(_color, 'Color')), const SizedBox(width: 12), Expanded(child: _f(_year, 'Year', kb: TextInputType.number))]),
        _f(_reg, 'Registration number'),
        _f(_ins, 'Insurance number'),
        if (widget.agents.isNotEmpty)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: DropdownButtonFormField<IdName>(
              initialValue: _agent, isExpanded: true,
              decoration: const InputDecoration(labelText: 'Assign to agent', border: OutlineInputBorder()),
              items: widget.agents.map((a) => DropdownMenuItem(value: a, child: Text(a.name, overflow: TextOverflow.ellipsis))).toList(),
              onChanged: (v) => setState(() => _agent = v),
            ),
          ),
        _f(_note, 'Note', maxLines: 2),
        const SizedBox(height: 16),
        SizedBox(width: double.infinity, height: 50, child: ElevatedButton(onPressed: _busy ? null : _save, child: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Save bike', style: TextStyle(fontWeight: FontWeight.w800)))),
      ]),
    );
  }

  Widget _f(TextEditingController c, String label, {TextInputType? kb, int maxLines = 1}) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextField(controller: c, keyboardType: kb, maxLines: maxLines, decoration: InputDecoration(labelText: label, border: const OutlineInputBorder())),
      );
}
