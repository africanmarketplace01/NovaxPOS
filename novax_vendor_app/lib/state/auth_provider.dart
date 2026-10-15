import 'package:flutter/foundation.dart';
import '../config.dart';
import '../services/api_client.dart';
import '../services/push_service.dart';

class VendorUser {
  final int id;
  final String name;
  final String email;
  final int roleId;
  VendorUser({required this.id, required this.name, required this.email, required this.roleId});
  factory VendorUser.fromJson(Map<String, dynamic> j) =>
      VendorUser(id: j['id'] as int, name: (j['name'] ?? '') as String, email: (j['email'] ?? '') as String, roleId: (j['role_id'] ?? 0) as int);
}

class AuthProvider extends ChangeNotifier {
  final ApiClient api;
  AuthProvider(this.api);

  VendorUser? _user;
  String? _store;
  bool _booted = false;

  VendorUser? get user => _user;
  String? get store => _store;
  bool get isAuthenticated => _user != null && api.isConfigured;
  bool get booted => _booted;

  Future<void> boot() async {
    await api.load();
    _store = api.store;
    if (api.isConfigured) {
      try {
        final res = await api.get('/vendor/me');
        _user = VendorUser.fromJson(Map<String, dynamic>.from(res['user']));
        _store = (res['store'] ?? _store) as String?;
        _identifyPush();
      } catch (_) {
        await api.clear();
      }
    }
    _booted = true;
    notifyListeners();
  }

  Future<void> login(String subdomain, String login, String password) async {
    final base = Config.apiBaseFor(subdomain);
    final res = await api.postTo('$base/vendor/login', {'login': login, 'password': password});
    if (res is! Map || res['token'] == null) {
      throw ApiException(0, "We couldn't reach your store. Check the store name and your connection, then try again.");
    }
    await api.saveSession(base: base, token: res['token'] as String, store: res['store']?.toString());
    _user = VendorUser.fromJson(Map<String, dynamic>.from(res['user']));
    _store = res['store']?.toString();
    _identifyPush();
    // Register the OneSignal player with the backend so it can target this vendor.
    try {
      final pid = await PushService.playerId();
      if (pid != null) await api.post('/vendor/push/register', {'player_id': pid});
    } catch (_) {}
    notifyListeners();
  }

  void _identifyPush() {
    if (_user != null) {
      // external id lets the backend target this device; tag by tenant for broadcasts
      final sub = (api.base ?? '').replaceAll(RegExp(r'^https?://'), '').split('.').first;
      PushService.identify('vendor:$sub:${_user!.id}', tags: {'role': 'vendor', 'tenant': sub});
    }
  }

  Future<void> logout() async {
    try { await api.post('/vendor/logout'); } catch (_) {}
    await PushService.logout();
    await api.clear();
    _user = null;
    notifyListeners();
  }
}
