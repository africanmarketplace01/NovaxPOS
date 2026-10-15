import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'config.dart';
import 'theme.dart';
import 'services/api_client.dart';
import 'services/push_service.dart';
import 'state/auth_provider.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'screens/pos_screen.dart';
import 'screens/orders_screen.dart';
import 'screens/products_screen.dart';
import 'screens/sourcing_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await PushService.init(Config.oneSignalAppId);
  final api = ApiClient(tokenKey: 'vendor_token', baseKey: 'vendor_base', storeKey: 'vendor_store');
  final auth = AuthProvider(api);

  // Fetch the platform brand palette (all colours) from the central marketplace
  // API, in parallel with boot. Falls back to defaults so startup never blocks.
  final themeApi = ApiClient(baseUrl: Config.centralApiBase);
  final brandFuture = fetchBrandColors(themeApi)
      .timeout(const Duration(seconds: 4), onTimeout: () => BrandColors.fallback);
  await auth.boot();
  BrandColors brand;
  try {
    brand = await brandFuture;
  } catch (_) {
    brand = BrandColors.fallback;
  }

  runApp(MultiProvider(
    providers: [
      Provider<ApiClient>.value(value: api),
      ChangeNotifierProvider<AuthProvider>.value(value: auth),
    ],
    child: VendorApp(brand: brand),
  ));
}

class VendorApp extends StatelessWidget {
  final BrandColors brand;
  const VendorApp({super.key, this.brand = BrandColors.fallback});
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: Config.appName,
      debugShowCheckedModeBanner: false,
      theme: buildTheme(brand: brand),
      home: const _Gate(),
    );
  }
}

class _Gate extends StatelessWidget {
  const _Gate();
  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.booted) return const Scaffold(body: Center(child: CircularProgressIndicator()));
    return auth.isAuthenticated ? const VendorShell() : const LoginScreen();
  }
}

class VendorShell extends StatefulWidget {
  const VendorShell({super.key});
  @override
  State<VendorShell> createState() => _VendorShellState();
}

class _VendorShellState extends State<VendorShell> {
  int _index = 0;

  @override
  void initState() {
    super.initState();
    // Show the OneSignal verification dialog once the device registers.
    WidgetsBinding.instance.addPostFrameCallback((_) => PushService.setupWelcomeDialog(context));
  }

  @override
  Widget build(BuildContext context) {
    const pages = [DashboardScreen(), PosScreen(), OrdersScreen(), ProductsScreen(), SourcingScreen()];
    return Scaffold(
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.point_of_sale_outlined), selectedIcon: Icon(Icons.point_of_sale), label: 'POS'),
          NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long), label: 'Orders'),
          NavigationDestination(icon: Icon(Icons.inventory_2_outlined), selectedIcon: Icon(Icons.inventory_2), label: 'Products'),
          NavigationDestination(icon: Icon(Icons.campaign_outlined), selectedIcon: Icon(Icons.campaign), label: 'Requests'),
        ],
      ),
    );
  }
}
