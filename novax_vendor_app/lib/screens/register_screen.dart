import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:novax_core/novax_core.dart';
import '../config.dart';
import '../theme.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});
  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _api = ApiClient(baseUrl: Config.centralApiBase);
  final _form = GlobalKey<FormState>();

  final _tenant = TextEditingController();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _company = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();

  List<Map<String, dynamic>> _packages = [];
  Map<String, dynamic>? _package;
  File? _logo;
  bool _loadingPkgs = true;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadPackages();
  }

  Future<void> _loadPackages() async {
    try {
      final res = await _api.get('/vendor-signup/packages');
      _packages = (res['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
      _package = _packages.isNotEmpty ? _packages.first : null;
    } catch (e) {
      _error = 'Could not load plans. $e';
    } finally {
      if (mounted) setState(() => _loadingPkgs = false);
    }
  }

  String? get _subType {
    if (_package == null) return null;
    if (_package!['is_free_trial'] == true) return 'free_trial';
    if (_package!['allow_paid_trial'] == true) return 'paid_trial';
    return null; // paid-only plan → must sign up on web
  }

  Future<void> _pickLogo() async {
    final x = await ImagePicker().pickImage(source: ImageSource.gallery, maxWidth: 800, imageQuality: 85);
    if (x != null) setState(() => _logo = File(x.path));
  }

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    if (_package == null) { setState(() => _error = 'Select a plan.'); return; }
    final sub = _subType;
    if (sub == null) {
      setState(() => _error = 'This is a paid plan — please complete signup on the website to pay.');
      return;
    }
    setState(() { _busy = true; _error = null; });
    try {
      final res = await _api.postMultipart('/vendor-signup/register',
        fields: {
          'tenant': _tenant.text.trim().toLowerCase(),
          'name': _name.text.trim(),
          'email': _email.text.trim(),
          if (_company.text.trim().isNotEmpty) 'company_name': _company.text.trim(),
          if (_phone.text.trim().isNotEmpty) 'phone_number': _phone.text.trim(),
          'password': _password.text,
          'package_id': '${_package!['id']}',
          'subscription_type': sub,
        },
        files: _logo != null ? {'logo': _logo!.path} : const {},
      );
      if (!mounted) return;
      final onlineStore = res['online_store'] == true;
      await showDialog(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text('Store created 🎉'),
          content: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('${res['message'] ?? 'Your store is ready!'}'),
            const SizedBox(height: 10),
            Text('Store: ${_tenant.text.trim().toLowerCase()}.${Config.rootDomain}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5)),
            if (onlineStore) ...[
              const SizedBox(height: 10),
              const Text('Your plan includes an online store. After signing in, open Setup → Online store settings to add your banner and go live.', style: TextStyle(fontSize: 12.5, color: AppColors.muted)),
            ],
          ]),
          actions: [ElevatedButton(onPressed: () => Navigator.pop(context), child: const Text('Continue to sign in'))],
        ),
      );
      if (mounted) Navigator.pop(context, _tenant.text.trim().toLowerCase());
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Create your store')),
      body: _loadingPkgs
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _form,
              child: ListView(padding: const EdgeInsets.all(20), children: [
                if (_error != null)
                  Container(
                    padding: const EdgeInsets.all(12),
                    margin: const EdgeInsets.only(bottom: 14),
                    decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(10)),
                    child: Text(_error!, style: const TextStyle(color: Color(0xFF991B1B))),
                  ),

                // Logo
                Center(
                  child: GestureDetector(
                    onTap: _pickLogo,
                    child: Container(
                      width: 96, height: 96,
                      decoration: BoxDecoration(color: const Color(0xFFF8FAFC), shape: BoxShape.circle, border: Border.all(color: AppColors.border)),
                      child: _logo != null
                          ? ClipOval(child: Image.file(_logo!, fit: BoxFit.cover, width: 96, height: 96))
                          : const Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                              Icon(Icons.add_a_photo_outlined, color: AppColors.muted),
                              SizedBox(height: 4),
                              Text('Logo', style: TextStyle(fontSize: 10, color: AppColors.muted)),
                            ]),
                    ),
                  ),
                ),
                const SizedBox(height: 18),

                _field(_name, 'Your name *', validator: _req),
                _field(_company, 'Business name'),
                _field(_email, 'Email *', keyboard: TextInputType.emailAddress, validator: (v) => (v == null || !v.contains('@')) ? 'Valid email required' : null),
                _field(_phone, 'Phone', keyboard: TextInputType.phone),
                TextFormField(
                  controller: _tenant,
                  autocorrect: false,
                  decoration: const InputDecoration(labelText: 'Store address (subdomain) *', hintText: 'yourstore', suffixText: '.${Config.rootDomain}', border: OutlineInputBorder()),
                  validator: (v) {
                    final s = (v ?? '').trim();
                    if (s.length < 3) return 'At least 3 characters';
                    if (!RegExp(r'^[a-zA-Z0-9]+$').hasMatch(s)) return 'Letters and numbers only';
                    return null;
                  },
                ),
                const SizedBox(height: 12),
                _field(_password, 'Password *', obscure: true, validator: (v) => (v == null || v.length < 6) ? 'Min 6 characters' : null),

                const SizedBox(height: 18),
                const Text('Choose a plan', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                const SizedBox(height: 8),
                ..._packages.map(_planCard),

                const SizedBox(height: 22),
                SizedBox(
                  width: double.infinity, height: 50,
                  child: ElevatedButton(
                    onPressed: _busy ? null : _submit,
                    child: _busy
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Text('Create store', style: TextStyle(fontWeight: FontWeight.w800)),
                  ),
                ),
                const SizedBox(height: 24),
              ]),
            ),
    );
  }

  String? _req(String? v) => (v == null || v.trim().isEmpty) ? 'Required' : null;

  Widget _field(TextEditingController c, String label, {TextInputType? keyboard, bool obscure = false, String? Function(String?)? validator}) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextFormField(controller: c, keyboardType: keyboard, obscureText: obscure, validator: validator, decoration: InputDecoration(labelText: label, border: const OutlineInputBorder())),
      );

  String _money(dynamic v) {
    final n = (v is num) ? v : double.tryParse('$v') ?? 0;
    return '\$${n.toStringAsFixed(0)}';
  }

  Widget _planCard(Map<String, dynamic> p) {
    final selected = _package?['id'] == p['id'];
    final free = p['is_free_trial'] == true;
    final paidTrial = !free && p['allow_paid_trial'] == true;
    final online = p['online_store'] == true;
    final onetime = p['price_onetime'];
    final monthly = p['price_monthly'];
    final trialDays = p['trial_days'] ?? 14;

    // Primary price line — mirrors the SaaS landing pricing display.
    String priceLine;
    Color priceColor = AppColors.primary;
    if (free) { priceLine = 'FREE — $trialDays-day trial'; priceColor = const Color(0xFF10B981); }
    else if (onetime != null) { priceLine = '${_money(onetime)} once'; }
    else if (monthly != null) { priceLine = '${_money(monthly)}/mo'; }
    else { priceLine = 'Contact for pricing'; }
    return GestureDetector(
      onTap: () => setState(() { _package = p; _error = null; }),
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: selected ? AppColors.primary.withValues(alpha: .06) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: selected ? AppColors.primary : AppColors.border, width: selected ? 1.6 : 1),
        ),
        child: Row(children: [
          Icon(selected ? Icons.radio_button_checked : Icons.radio_button_off, color: selected ? AppColors.primary : AppColors.muted),
          const SizedBox(width: 12),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Text('${p['name']}', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
              if (p['badge'] != null) ...[
                const SizedBox(width: 6),
                Container(padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1), decoration: BoxDecoration(color: AppColors.accent.withValues(alpha: .2), borderRadius: BorderRadius.circular(10)), child: Text('${p['badge']}', style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w700))),
              ],
            ]),
            const SizedBox(height: 3),
            Text(priceLine, style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5, color: priceColor)),
            if (onetime != null && monthly != null)
              Text('or ${_money(monthly)}/mo', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
            if (paidTrial)
              Text('$trialDays-day trial available', style: const TextStyle(fontSize: 11, color: Color(0xFFF59E0B))),
            const SizedBox(height: 4),
            Wrap(spacing: 6, runSpacing: 4, children: [
              if (online) _tag('Online store', const Color(0xFF0EA5E9)),
              if (!free && !paidTrial) _tag('Payment on web', const Color(0xFFF59E0B)),
            ]),
          ])),
        ]),
      ),
    );
  }

  Widget _tag(String t, Color c) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
        decoration: BoxDecoration(color: c.withValues(alpha: .12), borderRadius: BorderRadius.circular(20)),
        child: Text(t, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: c)),
      );
}
