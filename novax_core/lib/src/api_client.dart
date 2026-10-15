import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiException implements Exception {
  final int status;
  final String message;
  ApiException(this.status, this.message);
  @override
  String toString() => message;
}

/// Shared HTTP client for both apps. Supports a fixed base URL (customer app)
/// or a dynamic, persisted base set at login (vendor app), plus Bearer tokens.
class ApiClient {
  ApiClient({this.baseUrl, this.tokenKey = 'token', this.baseKey = 'base', this.storeKey = 'store'});

  String? baseUrl;
  final String tokenKey;
  final String baseKey;
  final String storeKey;

  String? _token;
  String? store;

  bool get hasToken => _token != null;
  bool get isConfigured => _token != null && baseUrl != null;
  String? get base => baseUrl;

  /// Load persisted token/base/store. Keeps the constructor baseUrl if none stored.
  Future<void> load() async {
    final p = await SharedPreferences.getInstance();
    _token = p.getString(tokenKey);
    baseUrl = p.getString(baseKey) ?? baseUrl;
    store = p.getString(storeKey);
  }

  /// Customer-app convenience alias.
  Future<void> loadToken() => load();

  Future<void> setToken(String? token) async {
    _token = token;
    final p = await SharedPreferences.getInstance();
    if (token == null) {
      await p.remove(tokenKey);
    } else {
      await p.setString(tokenKey, token);
    }
  }

  /// Vendor-app: persist base + token + store together.
  Future<void> saveSession({required String base, required String token, String? store}) async {
    baseUrl = base;
    _token = token;
    this.store = store;
    final p = await SharedPreferences.getInstance();
    await p.setString(baseKey, base);
    await p.setString(tokenKey, token);
    if (store != null) await p.setString(storeKey, store);
  }

  Future<void> clear() => setToken(null);

  /// Identify the app to the server. A descriptive User-Agent avoids WAF/security
  /// rules that block the default Dart client UA ("Dart/x.y (dart:io)").
  static const String userAgent = 'NovaxSuitesApp/1.0 (Flutter; okhttp-compatible)';

  Map<String, String> _headers({bool json = true}) => {
        if (json) 'Content-Type': 'application/json',
        'Accept': 'application/json',
        'User-Agent': userAgent,
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Uri _uri(String path, [Map<String, dynamic>? query]) {
    final q = query?.map((k, v) => MapEntry(k, '$v'));
    return Uri.parse('$baseUrl$path').replace(queryParameters: (q != null && q.isNotEmpty) ? q : null);
  }

  static const Duration _timeout = Duration(seconds: 30);

  /// Wraps a request so network failures become friendly, actionable messages
  /// instead of raw exceptions surfacing in the UI.
  Future<dynamic> _guard(Future<http.Response> Function() run) async {
    http.Response r;
    try {
      r = await run().timeout(_timeout);
    } on TimeoutException {
      throw ApiException(0, "This is taking too long. Check your internet connection and try again.");
    } on SocketException {
      throw ApiException(0, "You appear to be offline. Please connect to the internet and try again.");
    } on http.ClientException {
      throw ApiException(0, "Couldn't reach the server. Please check your connection and try again.");
    } on HandshakeException {
      throw ApiException(0, "Secure connection failed. Please try again.");
    } catch (_) {
      throw ApiException(0, "Network error. Please check your connection and try again.");
    }
    return _decode(r);
  }

  Future<dynamic> get(String path, {Map<String, dynamic>? query}) =>
      _guard(() => http.get(_uri(path, query), headers: _headers(json: false)));

  Future<dynamic> post(String path, [Map<String, dynamic>? body]) =>
      _guard(() => http.post(_uri(path), headers: _headers(), body: jsonEncode(body ?? {})));

  Future<dynamic> put(String path, [Map<String, dynamic>? body]) =>
      _guard(() => http.put(_uri(path), headers: _headers(), body: jsonEncode(body ?? {})));

  Future<dynamic> delete(String path, [Map<String, dynamic>? body]) =>
      _guard(() => http.delete(_uri(path), headers: _headers(), body: jsonEncode(body ?? {})));

  /// POST to an explicit base URL before a session exists (vendor login).
  Future<dynamic> postTo(String url, Map<String, dynamic> body) => _guard(() => http.post(
        Uri.parse(url),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'User-Agent': userAgent},
        body: jsonEncode(body)));

  /// Multipart POST — used for sourcing requests with optional photo/video.
  Future<dynamic> postMultipart(String path, {Map<String, String> fields = const {}, Map<String, String> files = const {}}) {
    return _guard(() async {
      final req = http.MultipartRequest('POST', _uri(path));
      req.headers.addAll(_headers(json: false));
      fields.forEach((k, v) => req.fields[k] = v);
      for (final e in files.entries) {
        req.files.add(await http.MultipartFile.fromPath(e.key, e.value));
      }
      return http.Response.fromStream(await req.send());
    });
  }

  /// Turn an HTTP response into data, or throw a friendly ApiException.
  dynamic _decode(http.Response r) {
    dynamic data;
    try { data = r.body.isEmpty ? null : jsonDecode(r.body); } catch (_) { data = null; }
    final code = r.statusCode;
    if (code >= 200 && code < 300) return data;

    // Prefer a specific server message for 4xx (e.g. validation) — but only when
    // it looks human-friendly (JSON APIs), not an HTML error page.
    final serverMsg = (data is Map && data['message'] is String && (data['message'] as String).trim().isNotEmpty)
        ? (data['message'] as String)
        : null;

    String msg;
    if (code >= 300 && code < 400) {
      msg = "We couldn't reach your store. Check the store name/address and your connection, then try again.";
    } else if (code == 401) {
      msg = "Your session has expired. Please sign in again.";
    } else if (code == 403) {
      msg = serverMsg ?? "You don't have permission to do that.";
    } else if (code == 404) {
      msg = serverMsg ?? "We couldn't find what you were looking for.";
    } else if (code == 419) {
      msg = "Your session has expired. Please sign in again.";
    } else if (code == 422) {
      msg = serverMsg ?? "Please check the details you entered and try again.";
    } else if (code == 429) {
      msg = "Too many attempts. Please wait a moment and try again.";
    } else if (code >= 500) {
      msg = "The server ran into a problem. Please try again in a moment.";
    } else {
      msg = serverMsg ?? "Something went wrong. Please try again.";
    }
    throw ApiException(code, msg);
  }
}
