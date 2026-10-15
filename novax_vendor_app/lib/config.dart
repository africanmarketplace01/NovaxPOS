/// Vendor app configuration.
///
/// Vendors log into their own store (tenant subdomain). The API base is built
/// from the store subdomain the vendor enters at login + [rootDomain].
class Config {
  static const String appName = 'NovaxSuites POS - Vendor';

  /// Your platform root domain. A tenant "acme" → https://acme.novasuitespos.com
  static const String rootDomain = 'novasuitespos.com';

  /// OneSignal App ID (from your OneSignal dashboard). Leave empty to disable push.
  static const String oneSignalAppId = 'c0d024d5-6f2f-46fd-8485-c55ac9918b8b';

  /// Central marketplace API base — used to fetch the platform brand theme
  /// (colours) before/after a store is chosen.
  static String get centralApiBase => 'https://$rootDomain/api/marketplace/v1';

  /// Build the API base URL for a given store subdomain.
  static String apiBaseFor(String subdomain) =>
      'https://${subdomain.trim()}.$rootDomain/api';

  // Local dev override example (Android emulator):
  // static String apiBaseFor(String s) => 'http://10.0.2.2:8000/api';
}
