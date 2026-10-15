import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'config.dart';
import 'theme.dart';
import 'services/api_client.dart';
import 'services/push_service.dart';
import 'state/auth_provider.dart';
import 'state/cart_provider.dart';
import 'screens/home_screen.dart';
import 'screens/cart_screen.dart';
import 'screens/orders_screen.dart';
import 'screens/account_screen.dart';
import 'screens/requests_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await PushService.init(Config.oneSignalAppId);
  final api = ApiClient(baseUrl: Config.apiBaseUrl, tokenKey: 'mkt_token');
  final auth = AuthProvider(api);
  final cart = CartProvider();

  // Pull the live SaaS brand palette (all colours) in parallel with a short
  // timeout; falls back to the default palette so startup never blocks.
  final brandFuture = fetchBrandColors(api)
      .timeout(const Duration(seconds: 4), onTimeout: () => BrandColors.fallback);
  await Future.wait([auth.boot(), cart.load()]);
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
      ChangeNotifierProvider<CartProvider>.value(value: cart),
    ],
    child: NovaxApp(brand: brand),
  ));
}

class NovaxApp extends StatelessWidget {
  final BrandColors brand;
  const NovaxApp({super.key, this.brand = BrandColors.fallback});
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: Config.appName,
      debugShowCheckedModeBanner: false,
      theme: buildTheme(brand: brand),
      home: const RootShell(),
    );
  }
}

class RootShell extends StatefulWidget {
  const RootShell({super.key});
  @override
  State<RootShell> createState() => _RootShellState();
}

class _RootShellState extends State<RootShell> {
  int _index = 0;

  @override
  void initState() {
    super.initState();
    // Show the OneSignal verification dialog once the device registers.
    WidgetsBinding.instance.addPostFrameCallback((_) => PushService.setupWelcomeDialog(context));
  }

  @override
  Widget build(BuildContext context) {
    final cartCount = context.watch<CartProvider>().count;
    const pages = [HomeScreen(), CartScreen(), RequestsScreen(), OrdersScreen(), AccountScreen()];

    return Scaffold(
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: [
          const NavigationDestination(icon: Icon(Icons.storefront_outlined), selectedIcon: Icon(Icons.storefront), label: 'Shop'),
          NavigationDestination(
            icon: Badge(isLabelVisible: cartCount > 0, label: Text('$cartCount'), child: const Icon(Icons.shopping_cart_outlined)),
            selectedIcon: const Icon(Icons.shopping_cart),
            label: 'Cart',
          ),
          const NavigationDestination(icon: Icon(Icons.campaign_outlined), selectedIcon: Icon(Icons.campaign), label: 'Requests'),
          const NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long), label: 'Orders'),
          const NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Account'),
        ],
      ),
    );
  }
}
