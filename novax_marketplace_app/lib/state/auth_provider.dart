import 'package:flutter/foundation.dart';
import '../services/api_client.dart';
import '../services/push_service.dart';

class Customer {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? address;
  Customer({required this.id, required this.name, required this.email, this.phone, this.address});
  factory Customer.fromJson(Map<String, dynamic> j) => Customer(
        id: j['id'] as int,
        name: (j['name'] ?? '') as String,
        email: (j['email'] ?? '') as String,
        phone: j['phone'] as String?,
        address: j['address'] as String?,
      );
}

class AuthProvider extends ChangeNotifier {
  final ApiClient api;
  AuthProvider(this.api);

  Customer? _customer;
  bool _booted = false;

  Customer? get customer => _customer;
  bool get isAuthenticated => _customer != null && api.hasToken;
  bool get booted => _booted;

  /// Restore a saved session on app start.
  Future<void> boot() async {
    await api.loadToken();
    if (api.hasToken) {
      try {
        final res = await api.get('/auth/me');
        _customer = Customer.fromJson(Map<String, dynamic>.from(res['customer']));
        _afterAuth();
      } catch (_) {
        await api.setToken(null); // token invalid → clear
      }
    }
    _booted = true;
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    final res = await api.post('/auth/login', {'email': email, 'password': password});
    await api.setToken(res['token'] as String);
    _customer = Customer.fromJson(Map<String, dynamic>.from(res['customer']));
    _afterAuth();
    notifyListeners();
  }

  Future<void> register(String name, String email, String password, String? phone) async {
    final res = await api.post('/auth/register',
        {'name': name, 'email': email, 'password': password, if (phone != null) 'phone': phone});
    await api.setToken(res['token'] as String);
    _customer = Customer.fromJson(Map<String, dynamic>.from(res['customer']));
    _afterAuth();
    notifyListeners();
  }

  /// Bind push identity and register the device after a successful auth.
  void _afterAuth() {
    if (_customer == null) return;
    PushService.identify('customer:${_customer!.id}', tags: {'role': 'customer'});
    PushService.playerId().then((pid) {
      if (pid != null) api.post('/push/register', {'player_id': pid}).catchError((_) => null);
    });
  }

  Future<void> logout() async {
    try {
      await api.post('/auth/logout');
    } catch (_) {}
    await PushService.logout();
    await api.setToken(null);
    _customer = null;
    notifyListeners();
  }
}
