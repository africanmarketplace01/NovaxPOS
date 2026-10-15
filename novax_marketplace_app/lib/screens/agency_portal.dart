import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:novax_core/novax_core.dart';
import '../config.dart';
import '../theme.dart';

/// Self-contained delivery-agency portal (own bearer token, no global provider).
class AgencyPortal extends StatefulWidget {
  const AgencyPortal({super.key});
  @override
  State<AgencyPortal> createState() => _AgencyPortalState();
}

class _AgencyPortalState extends State<AgencyPortal> {
  final ApiClient _api = ApiClient(baseUrl: Config.apiBaseUrl, tokenKey: 'agency_token', baseKey: 'agency_base', storeKey: 'agency_store');
  bool _loading = true;
  bool _authed = false;
  Map<String, dynamic>? _agency;

  @override
  void initState() {
    super.initState();
    _boot();
  }

  Future<void> _boot() async {
    await _api.load();
    if (_api.hasToken) {
      try {
        final res = await _api.get('/agency/me');
        _agency = Map<String, dynamic>.from(res['agency']);
        _authed = true;
      } catch (_) {
        await _api.clear();
      }
    }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _onLoggedIn(Map<String, dynamic> agency) async {
    setState(() { _agency = agency; _authed = true; });
  }

  Future<void> _logout() async {
    try { await _api.post('/agency/logout'); } catch (_) {}
    await _api.clear();
    if (mounted) setState(() { _authed = false; _agency = null; });
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Scaffold(body: Center(child: CircularProgressIndicator()));
    if (!_authed) return _AgencyLogin(api: _api, onLoggedIn: _onLoggedIn);
    return _AgencyHome(api: _api, agency: _agency!, onLogout: _logout);
  }
}

// ── Login ────────────────────────────────────────────────────────────────────
class _AgencyLogin extends StatefulWidget {
  final ApiClient api;
  final Future<void> Function(Map<String, dynamic>) onLoggedIn;
  const _AgencyLogin({required this.api, required this.onLoggedIn});
  @override
  State<_AgencyLogin> createState() => _AgencyLoginState();
}

class _AgencyLoginState extends State<_AgencyLogin> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  String? _error;

  Future<void> _submit() async {
    if (_email.text.trim().isEmpty || _password.text.isEmpty) {
      setState(() => _error = 'Enter your agency email and password.');
      return;
    }
    setState(() { _busy = true; _error = null; });
    try {
      final res = await widget.api.post('/agency/login', {'email': _email.text.trim(), 'password': _password.text});
      if (res is! Map || res['token'] == null) throw ApiException(0, 'Login failed. Please try again.');
      await widget.api.setToken(res['token'] as String);
      await widget.onLoggedIn(Map<String, dynamic>.from(res['agency']));
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Delivery Partner')),
      body: ListView(padding: const EdgeInsets.all(24), children: [
        const SizedBox(height: 20),
        const Icon(Icons.local_shipping, size: 56, color: AppColors.primary),
        const SizedBox(height: 12),
        const Text('Delivery agency sign in', textAlign: TextAlign.center, style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.dark)),
        const SizedBox(height: 4),
        const Text('Manage your riders, bikes and assigned orders.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted)),
        const SizedBox(height: 24),
        if (_error != null)
          Container(padding: const EdgeInsets.all(12), margin: const EdgeInsets.only(bottom: 14), decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(10)), child: Text(_error!, style: const TextStyle(color: Color(0xFF991B1B)))),
        TextField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Agency email', border: OutlineInputBorder())),
        const SizedBox(height: 14),
        TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'Password', border: OutlineInputBorder())),
        const SizedBox(height: 22),
        SizedBox(height: 50, child: ElevatedButton(onPressed: _busy ? null : _submit, child: _busy ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Sign in', style: TextStyle(fontWeight: FontWeight.w800)))),
        const SizedBox(height: 14),
        const Text('Not registered? Register your agency on the website, then sign in here once approved.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted, fontSize: 12)),
      ]),
    );
  }
}

// ── Home (tabs) ──────────────────────────────────────────────────────────────
class _AgencyHome extends StatelessWidget {
  final ApiClient api;
  final Map<String, dynamic> agency;
  final Future<void> Function() onLogout;
  const _AgencyHome({required this.api, required this.agency, required this.onLogout});

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: Text('${agency['name'] ?? 'My Agency'}', maxLines: 1, overflow: TextOverflow.ellipsis),
          actions: [IconButton(onPressed: onLogout, icon: const Icon(Icons.logout))],
          bottom: const TabBar(tabs: [Tab(text: 'Orders'), Tab(text: 'Agents'), Tab(text: 'Bikes')]),
        ),
        body: TabBarView(children: [
          _AgencyOrders(api: api),
          _AgencyAgents(api: api),
          _AgencyBikes(api: api),
        ]),
      ),
    );
  }
}

// ── Orders ───────────────────────────────────────────────────────────────────
class _AgencyOrders extends StatefulWidget {
  final ApiClient api;
  const _AgencyOrders({required this.api});
  @override
  State<_AgencyOrders> createState() => _AgencyOrdersState();
}

class _AgencyOrdersState extends State<_AgencyOrders> {
  List<Map<String, dynamic>> _orders = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await widget.api.get('/agency/orders');
      _orders = (res['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    } catch (e) { if (mounted) setState(() => _error = e.toString()); }
    finally { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _assign(Map<String, dynamic> o) async {
    List agents;
    try { final res = await widget.api.get('/agency/agents'); agents = (res['data'] as List?) ?? []; }
    catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); return; }
    if (agents.isEmpty) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Add agents first (Agents tab).'))); return; }
    final picked = await showModalBottomSheet<int>(context: context, builder: (_) => SafeArea(child: Column(mainAxisSize: MainAxisSize.min, children: [
      const Padding(padding: EdgeInsets.all(16), child: Text('Assign rider', style: TextStyle(fontWeight: FontWeight.w800))),
      ...agents.map((a) { final m = Map<String, dynamic>.from(a); return ListTile(
        leading: CircleAvatar(backgroundColor: const Color(0xFFEEF2F7), backgroundImage: m['photo_url'] != null ? NetworkImage('${m['photo_url']}') : null, child: m['photo_url'] == null ? const Icon(Icons.person, size: 18, color: AppColors.muted) : null),
        title: Text('${m['name']}'), subtitle: m['phone'] != null ? Text('${m['phone']}') : null,
        onTap: () => Navigator.pop(context, m['id'] as int)); }),
      ListTile(leading: const Icon(Icons.person_off_outlined), title: const Text('Unassign'), onTap: () => Navigator.pop(context, -1)),
    ])));
    if (picked == null) return;
    try { await widget.api.post('/agency/orders/${o['id']}/assign', {'agent_id': picked == -1 ? null : picked}); _load(); }
    catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); }
  }

  Future<void> _status(Map<String, dynamic> o) async {
    const options = ['processing', 'shipped', 'delivered'];
    final picked = await showModalBottomSheet<String>(context: context, builder: (_) => SafeArea(child: Column(mainAxisSize: MainAxisSize.min, children: [
      const Padding(padding: EdgeInsets.all(16), child: Text('Update status', style: TextStyle(fontWeight: FontWeight.w800))),
      ...options.map((s) => ListTile(title: Text(s[0].toUpperCase() + s.substring(1)), onTap: () => Navigator.pop(context, s))),
    ])));
    if (picked == null) return;
    try { await widget.api.post('/agency/orders/${o['id']}/status', {'status': picked}); _load(); }
    catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null && _orders.isEmpty) return ErrorRetry(message: _error!, onRetry: _load);
    return RefreshIndicator(
      onRefresh: _load,
      child: _orders.isEmpty
          ? ListView(children: const [SizedBox(height: 120), Center(child: Text('No orders assigned yet.', style: TextStyle(color: AppColors.muted)))])
          : ListView.separated(
              padding: const EdgeInsets.all(12),
              itemCount: _orders.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (_, i) {
                final o = _orders[i];
                final agent = o['agent'] as Map?;
                return Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [
                      Expanded(child: Text('${o['order_number']}', style: const TextStyle(fontWeight: FontWeight.w800))),
                      Text('${o['currency']} ${((o['total'] ?? 0) as num).toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w800, color: AppColors.primary)),
                    ]),
                    const SizedBox(height: 2),
                    Text('${o['customer'] ?? ''}${o['phone'] != null ? ' · ${o['phone']}' : ''}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                    if (o['address'] != null) Text('${o['address']}', maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                    const SizedBox(height: 6),
                    Row(children: [
                      _pill('${o['status']}', const Color(0xFF6366F1)),
                      const SizedBox(width: 6),
                      if (agent != null) Expanded(child: Text('🏍 ${agent['name']}${agent['bike'] != null ? ' · ${agent['bike']}' : ''}', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11.5, color: AppColors.primary)))
                      else const Expanded(child: Text('No rider assigned', style: TextStyle(fontSize: 11.5, color: AppColors.muted))),
                    ]),
                    const SizedBox(height: 8),
                    Row(children: [
                      Expanded(child: OutlinedButton.icon(onPressed: () => _assign(o), icon: const Icon(Icons.person_pin_circle_outlined, size: 16), label: const Text('Assign rider', style: TextStyle(fontSize: 12.5)))),
                      const SizedBox(width: 8),
                      Expanded(child: ElevatedButton.icon(onPressed: () => _status(o), icon: const Icon(Icons.local_shipping_outlined, size: 16), label: const Text('Status', style: TextStyle(fontSize: 12.5)))),
                    ]),
                  ]),
                );
              },
            ),
    );
  }

  Widget _pill(String t, Color c) => Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2), decoration: BoxDecoration(color: c.withValues(alpha: .12), borderRadius: BorderRadius.circular(20)), child: Text(t.isEmpty ? '' : t[0].toUpperCase() + t.substring(1), style: TextStyle(color: c, fontSize: 11, fontWeight: FontWeight.w700)));
}

// ── Agents ───────────────────────────────────────────────────────────────────
class _AgencyAgents extends StatefulWidget {
  final ApiClient api;
  const _AgencyAgents({required this.api});
  @override
  State<_AgencyAgents> createState() => _AgencyAgentsState();
}

class _AgencyAgentsState extends State<_AgencyAgents> {
  List<Map<String, dynamic>> _agents = [];
  bool _loading = true;

  @override
  void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final res = await widget.api.get('/agency/agents'); _agents = (res['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList(); }
    catch (_) {} finally { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _add() async {
    final ok = await Navigator.push<bool>(context, MaterialPageRoute(builder: (_) => _AgentForm(api: widget.api)));
    if (ok == true) _load();
  }

  Future<void> _delete(int id) async {
    try { await widget.api.delete('/agency/agents/$id'); _load(); } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionButton.extended(onPressed: _add, icon: const Icon(Icons.person_add_alt), label: const Text('Add rider')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _agents.isEmpty
                  ? ListView(children: const [SizedBox(height: 120), Center(child: Text('No riders yet.', style: TextStyle(color: AppColors.muted)))])
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
                            CircleAvatar(radius: 22, backgroundColor: const Color(0xFFEEF2F7), backgroundImage: a['photo_url'] != null ? NetworkImage('${a['photo_url']}') : null, child: a['photo_url'] == null ? const Icon(Icons.person, color: AppColors.muted) : null),
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
  final ApiClient api;
  const _AgentForm({required this.api});
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
      await widget.api.postMultipart('/agency/agents',
        fields: {'name': _name.text.trim(), if (_phone.text.trim().isNotEmpty) 'phone': _phone.text.trim(), if (_email.text.trim().isNotEmpty) 'email': _email.text.trim(), if (_license.text.trim().isNotEmpty) 'license_number': _license.text.trim()},
        files: _photo != null ? {'photo': _photo!.path} : const {});
      if (mounted) Navigator.pop(context, true);
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); }
    finally { if (mounted) setState(() => _busy = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Add rider')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Center(child: GestureDetector(onTap: _pick, child: CircleAvatar(radius: 44, backgroundColor: const Color(0xFFF1F5F9), backgroundImage: _photo != null ? FileImage(_photo!) : null, child: _photo == null ? const Icon(Icons.add_a_photo_outlined, color: AppColors.muted) : null))),
        const SizedBox(height: 18),
        _f(_name, 'Full name *'),
        _f(_phone, 'Phone', kb: TextInputType.phone),
        _f(_email, 'Email', kb: TextInputType.emailAddress),
        _f(_license, "Driver's license number"),
        const SizedBox(height: 16),
        SizedBox(width: double.infinity, height: 50, child: ElevatedButton(onPressed: _busy ? null : _save, child: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Save rider', style: TextStyle(fontWeight: FontWeight.w800)))),
      ]),
    );
  }

  Widget _f(TextEditingController c, String label, {TextInputType? kb}) => Padding(padding: const EdgeInsets.only(bottom: 12), child: TextField(controller: c, keyboardType: kb, decoration: InputDecoration(labelText: label, border: const OutlineInputBorder())));
}

// ── Bikes ────────────────────────────────────────────────────────────────────
class _AgencyBikes extends StatefulWidget {
  final ApiClient api;
  const _AgencyBikes({required this.api});
  @override
  State<_AgencyBikes> createState() => _AgencyBikesState();
}

class _AgencyBikesState extends State<_AgencyBikes> {
  List<Map<String, dynamic>> _bikes = [];
  List<Map<String, dynamic>> _agents = [];
  bool _loading = true;

  @override
  void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await widget.api.get('/agency/bikes');
      _bikes = (res['bikes'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
      _agents = (res['agents'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    } catch (_) {} finally { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _add() async {
    final ok = await Navigator.push<bool>(context, MaterialPageRoute(builder: (_) => _BikeForm(api: widget.api, agents: _agents)));
    if (ok == true) _load();
  }

  Future<void> _delete(int id) async {
    try { await widget.api.delete('/agency/bikes/$id'); _load(); } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'))); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionButton.extended(onPressed: _add, icon: const Icon(Icons.add), label: const Text('Add bike')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _bikes.isEmpty
                  ? ListView(children: const [SizedBox(height: 120), Center(child: Text('No bikes yet.', style: TextStyle(color: AppColors.muted)))])
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(12, 12, 12, 90),
                      itemCount: _bikes.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (_, i) {
                        final b = _bikes[i];
                        final title = [b['make'], b['model']].where((x) => x != null && '$x'.isNotEmpty).join(' ');
                        return Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
                          child: Row(children: [
                            Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: .12), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.two_wheeler, color: AppColors.primary)),
                            const SizedBox(width: 12),
                            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Text(title.isEmpty ? 'Bike' : title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
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
  final ApiClient api;
  final List<Map<String, dynamic>> agents;
  const _BikeForm({required this.api, required this.agents});
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
  int? _agentId;
  bool _busy = false;

  Future<void> _save() async {
    if (_plate.text.trim().isEmpty) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Plate number is required.'))); return; }
    setState(() => _busy = true);
    try {
      await widget.api.post('/agency/bikes', {
        'plate_number': _plate.text.trim(),
        if (_make.text.trim().isNotEmpty) 'make': _make.text.trim(),
        if (_model.text.trim().isNotEmpty) 'model': _model.text.trim(),
        if (_color.text.trim().isNotEmpty) 'color': _color.text.trim(),
        if (_year.text.trim().isNotEmpty) 'year': _year.text.trim(),
        if (_reg.text.trim().isNotEmpty) 'registration_no': _reg.text.trim(),
        if (_ins.text.trim().isNotEmpty) 'insurance_no': _ins.text.trim(),
        if (_agentId != null) 'agent_id': _agentId,
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
        Row(children: [Expanded(child: _f(_make, 'Make')), const SizedBox(width: 12), Expanded(child: _f(_model, 'Model'))]),
        Row(children: [Expanded(child: _f(_color, 'Color')), const SizedBox(width: 12), Expanded(child: _f(_year, 'Year', kb: TextInputType.number))]),
        _f(_reg, 'Registration number'),
        _f(_ins, 'Insurance number'),
        if (widget.agents.isNotEmpty)
          Padding(padding: const EdgeInsets.only(bottom: 12), child: DropdownButtonFormField<int>(
            initialValue: _agentId, isExpanded: true,
            decoration: const InputDecoration(labelText: 'Assign to rider', border: OutlineInputBorder()),
            items: widget.agents.map((a) => DropdownMenuItem(value: a['id'] as int, child: Text('${a['name']}', overflow: TextOverflow.ellipsis))).toList(),
            onChanged: (v) => setState(() => _agentId = v),
          )),
        const SizedBox(height: 16),
        SizedBox(width: double.infinity, height: 50, child: ElevatedButton(onPressed: _busy ? null : _save, child: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Save bike', style: TextStyle(fontWeight: FontWeight.w800)))),
      ]),
    );
  }

  Widget _f(TextEditingController c, String label, {TextInputType? kb}) => Padding(padding: const EdgeInsets.only(bottom: 12), child: TextField(controller: c, keyboardType: kb, decoration: InputDecoration(labelText: label, border: const OutlineInputBorder())));
}
