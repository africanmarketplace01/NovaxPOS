import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../config.dart';
import '../models/product.dart';
import '../services/api_client.dart';
import '../state/auth_provider.dart';
import '../state/cart_provider.dart';
import '../theme.dart';
import '../widgets/product_card.dart';
import 'product_screen.dart';
import 'shop_screen.dart';
import 'orders_screen.dart';
import 'account_screen.dart';
import 'login_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _Slide {
  final String title, sub;
  final IconData icon;
  final Color c1, c2;
  const _Slide(this.title, this.sub, this.icon, this.c1, this.c2);
}

class _HomeScreenState extends State<HomeScreen> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  final _hero = PageController();
  final List<Product> _products = [];
  List<String> _categories = [];
  List<String> _brands = [];
  List<Map<String, dynamic>> _shops = [];
  String? _category;
  String? _brand;
  bool _loading = false;
  bool _end = false;
  int _page = 1;
  String _q = '';
  String? _error;
  int _heroIndex = 0;
  Timer? _heroTimer;
  // Stable per-session seed so the random "popular" mix doesn't repeat while paging.
  final int _seed = DateTime.now().millisecondsSinceEpoch % 100000;

  bool get _isDefaultView => _category == null && _brand == null && _q.isEmpty;

  static const _slides = <_Slide>[
    _Slide('Welcome to Novax Marketplace', 'Shop from verified vendors near you', Icons.storefront, AppColors.primary, AppColors.dark),
    _Slide('Fast, tracked delivery', 'Order today — delivered to your door', Icons.local_shipping, Color(0xFF6366F1), AppColors.primary),
    _Slide("Can't find it? Ask vendors", 'Post a sourcing request with a photo or video', Icons.campaign, Color(0xFF0EA5E9), Color(0xFF0369A1)),
  ];

  @override
  void initState() {
    super.initState();
    _load(reset: true);
    _loadCategories();
    _loadBrands();
    _loadShops();
    _scroll.addListener(() {
      if (_scroll.position.pixels > _scroll.position.maxScrollExtent - 400) _load();
    });
    _heroTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (!_hero.hasClients) return;
      _heroIndex = (_heroIndex + 1) % _slides.length;
      _hero.animateToPage(_heroIndex, duration: const Duration(milliseconds: 450), curve: Curves.easeOut);
    });
  }

  @override
  void dispose() {
    _heroTimer?.cancel();
    _hero.dispose();
    _scroll.dispose();
    _search.dispose();
    super.dispose();
  }

  Future<void> _loadCategories() async {
    try {
      final res = await context.read<ApiClient>().get('/categories');
      final list = ((res['data'] as List?) ?? []).map((e) => '$e').where((e) => e.isNotEmpty).toList();
      if (mounted) setState(() => _categories = list);
    } catch (_) {}
  }

  Future<void> _loadBrands() async {
    try {
      final res = await context.read<ApiClient>().get('/brands');
      final list = ((res['data'] as List?) ?? []).map((e) => '$e').where((e) => e.isNotEmpty).toList();
      if (mounted) setState(() => _brands = list);
    } catch (_) {}
  }

  Future<void> _loadShops() async {
    try {
      final res = await context.read<ApiClient>().get('/shops');
      final list = ((res['data'] as List?) ?? []).map((e) => Map<String, dynamic>.from(e)).toList();
      if (mounted) setState(() => _shops = list);
    } catch (_) {}
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading || (_end && !reset)) return;
    setState(() => _loading = true);
    if (reset) { _page = 1; _end = false; _products.clear(); _error = null; }
    try {
      final api = context.read<ApiClient>();
      final res = await api.get('/products', query: {
        'q': _q,
        'page': _page,
        'per_page': 20,
        if (_category != null) 'category': _category,
        if (_brand != null) 'brand': _brand,
        // Default (no filters/search) shows a random mix; seed keeps paging stable.
        if (_isDefaultView) ...{'sort': 'random', 'seed': _seed},
      });
      final data = (res['data'] as List).map((e) => Product.fromJson(Map<String, dynamic>.from(e))).toList();
      final meta = res['meta'] as Map;
      setState(() {
        _products.addAll(data);
        _end = (meta['current_page'] as int) >= (meta['last_page'] as int);
        _page++;
        _error = null;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _selectCategory(String? c) {
    if (_category == c) return;
    setState(() => _category = c);
    _load(reset: true);
  }

  void _selectBrand(String? b) {
    setState(() => _brand = b);
    _load(reset: true);
  }

  void _addToCart(Product p) {
    context.read<CartProvider>().add(p);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('${p.name} added to cart'), duration: const Duration(milliseconds: 900)));
  }

  PopupMenuItem<String> _menuItem(String value, IconData icon, String label, {Color? color}) =>
      PopupMenuItem<String>(
        value: value,
        child: Row(children: [
          Icon(icon, size: 20, color: color ?? AppColors.ink),
          const SizedBox(width: 12),
          Text(label, style: TextStyle(color: color ?? AppColors.ink)),
        ]),
      );

  void _onMenu(String value) {
    switch (value) {
      case 'account':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const AccountScreen()));
        break;
      case 'orders':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const OrdersScreen()));
        break;
      case 'support':
        showDialog(
          context: context,
          builder: (_) => AlertDialog(
            title: const Text('Support'),
            content: const Text('Need help with an order?\nContact us at support@novasuitespos.com'),
            actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Close'))],
          ),
        );
        break;
      case 'about':
        showAboutDialog(context: context, applicationName: Config.appName, applicationVersion: '1.0.0', applicationLegalese: '© NovaxSuites');
        break;
      case 'login':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
        break;
      case 'logout':
        context.read<AuthProvider>().logout();
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        titleSpacing: 16,
        title: const Text(Config.appName, style: TextStyle(fontWeight: FontWeight.w800)),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 2),
            child: Image.asset('assets/logo.png', height: 28, errorBuilder: (_, __, ___) => const SizedBox.shrink()),
          ),
          PopupMenuButton<String>(
            icon: const Icon(Icons.more_vert),
            onSelected: _onMenu,
            itemBuilder: (_) {
              final signedIn = context.read<AuthProvider>().isAuthenticated;
              return [
                _menuItem('account', Icons.person_outline, 'My account'),
                _menuItem('orders', Icons.receipt_long_outlined, 'My orders'),
                _menuItem('support', Icons.headset_mic_outlined, 'Support'),
                _menuItem('about', Icons.info_outline, 'About'),
                const PopupMenuDivider(),
                signedIn
                    ? _menuItem('logout', Icons.logout, 'Sign out', color: AppColors.danger)
                    : _menuItem('login', Icons.login, 'Sign in'),
              ];
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async { await _load(reset: true); await _loadCategories(); },
        child: CustomScrollView(
          controller: _scroll,
          slivers: [
            SliverToBoxAdapter(child: _searchBar()),
            SliverToBoxAdapter(child: _heroCarousel()),
            if (_shops.isNotEmpty && _isDefaultView) SliverToBoxAdapter(child: _shopsCarousel()),
            if (_categories.isNotEmpty) SliverToBoxAdapter(child: _categoryRow()),
            SliverToBoxAdapter(child: _sectionHeader()),
            _productsArea(),
            SliverToBoxAdapter(child: _footerLoader()),
          ],
        ),
      ),
    );
  }

  Widget _searchBar() => Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
        child: TextField(
          controller: _search,
          textInputAction: TextInputAction.search,
          onSubmitted: (v) { _q = v.trim(); _load(reset: true); },
          decoration: InputDecoration(
            hintText: 'Search products, brands, stores…',
            prefixIcon: const Icon(Icons.search),
            suffixIcon: _q.isNotEmpty
                ? IconButton(icon: const Icon(Icons.clear), onPressed: () { _search.clear(); _q = ''; _load(reset: true); })
                : null,
          ),
        ),
      );

  Widget _heroCarousel() => Column(children: [
        SizedBox(
          height: 158,
          child: PageView.builder(
            controller: _hero,
            itemCount: _slides.length,
            onPageChanged: (i) => setState(() => _heroIndex = i),
            itemBuilder: (_, i) {
              final s = _slides[i];
              return Container(
                margin: const EdgeInsets.fromLTRB(16, 8, 16, 6),
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(18),
                  gradient: LinearGradient(colors: [s.c1, s.c2], begin: Alignment.topLeft, end: Alignment.bottomRight),
                  boxShadow: [BoxShadow(color: s.c1.withValues(alpha: .28), blurRadius: 16, offset: const Offset(0, 8))],
                ),
                child: Row(children: [
                  Expanded(
                    child: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(s.title, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, height: 1.15)),
                      const SizedBox(height: 6),
                      Text(s.sub, style: const TextStyle(color: Colors.white70, fontSize: 12.5)),
                    ]),
                  ),
                  const SizedBox(width: 10),
                  Icon(s.icon, color: Colors.white24, size: 64),
                ]),
              );
            },
          ),
        ),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(_slides.length, (i) => AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                width: i == _heroIndex ? 18 : 6, height: 6,
                decoration: BoxDecoration(color: i == _heroIndex ? AppColors.primary : AppColors.border, borderRadius: BorderRadius.circular(3)),
              )),
        ),
      ]);

  Widget _categoryRow() => SizedBox(
        height: 46,
        child: ListView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
          children: [
            _catChip('All', _category == null, () => _selectCategory(null)),
            ..._categories.map((c) => _catChip(c, _category == c, () => _selectCategory(c))),
          ],
        ),
      );

  Widget _catChip(String label, bool selected, VoidCallback onTap) => Padding(
        padding: const EdgeInsets.only(right: 8),
        child: ChoiceChip(
          label: Text(label),
          selected: selected,
          onSelected: (_) => onTap(),
          showCheckmark: false,
          labelStyle: TextStyle(color: selected ? Colors.white : AppColors.ink, fontSize: 12.5, fontWeight: FontWeight.w600),
          backgroundColor: Colors.white,
          selectedColor: AppColors.primary,
          side: BorderSide(color: selected ? AppColors.primary : AppColors.border),
        ),
      );

  Widget _shopsCarousel() => Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 10, 16, 6),
          child: Text('Shops near you', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.dark)),
        ),
        SizedBox(
          height: 116,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: _shops.length,
            separatorBuilder: (_, __) => const SizedBox(width: 10),
            itemBuilder: (_, i) {
              final s = _shops[i];
              return InkWell(
                borderRadius: BorderRadius.circular(12),
                onTap: () => Navigator.push(context, MaterialPageRoute(
                  builder: (_) => ShopScreen(tenantId: '${s['tenant_id']}', name: '${s['name']}'))),
                child: SizedBox(
                  width: 92,
                  child: Column(children: [
                    Stack(clipBehavior: Clip.none, children: [
                      Container(
                        width: 72, height: 72,
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          shape: BoxShape.circle,
                          border: Border.all(color: AppColors.border),
                          image: s['image_url'] != null ? DecorationImage(image: NetworkImage('${s['image_url']}'), fit: BoxFit.cover) : null,
                        ),
                        child: s['image_url'] == null ? const Icon(Icons.storefront_outlined, color: AppColors.primary) : null,
                      ),
                      // Total stock badge (top-right)
                      Positioned(
                        top: -4, right: -4,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(20), border: Border.all(color: Colors.white, width: 1.5)),
                          child: Row(mainAxisSize: MainAxisSize.min, children: [
                            const Icon(Icons.inventory_2, color: Colors.white, size: 9),
                            const SizedBox(width: 2),
                            Text('${s['total_stock'] ?? 0}', style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w800)),
                          ]),
                        ),
                      ),
                    ]),
                    const SizedBox(height: 6),
                    Text('${s['name']}', maxLines: 2, textAlign: TextAlign.center, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, height: 1.1)),
                  ]),
                ),
              );
            },
          ),
        ),
      ]);

  Widget _sectionHeader() => Padding(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 2),
        child: Row(children: [
          Expanded(
            child: Text(_brand ?? _category ?? (_q.isNotEmpty ? 'Results' : 'Popular products'),
                maxLines: 1, overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.dark)),
          ),
          if (_brands.isNotEmpty)
            PopupMenuButton<String?>(
              onSelected: _selectBrand,
              itemBuilder: (_) => [
                const PopupMenuItem<String?>(value: null, child: Text('All brands')),
                ..._brands.map((b) => PopupMenuItem<String?>(value: b, child: Text(b))),
              ],
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: _brand != null ? AppColors.primary.withValues(alpha: .10) : Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: _brand != null ? AppColors.primary : AppColors.border),
                ),
                child: Row(mainAxisSize: MainAxisSize.min, children: [
                  Icon(Icons.sell_outlined, size: 15, color: _brand != null ? AppColors.primary : AppColors.muted),
                  const SizedBox(width: 4),
                  Text(_brand ?? 'Brand', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: _brand != null ? AppColors.primary : AppColors.ink)),
                  const Icon(Icons.arrow_drop_down, size: 18, color: AppColors.muted),
                ]),
              ),
            ),
          if (_category != null || _brand != null)
            TextButton(onPressed: () { setState(() { _category = null; _brand = null; }); _load(reset: true); }, child: const Text('Clear', style: TextStyle(fontSize: 12.5))),
        ]),
      );

  Widget _productsArea() {
    if (_products.isEmpty && !_loading) {
      return SliverToBoxAdapter(child: _emptyOrError());
    }
    if (_products.isEmpty && _loading) {
      return const SliverToBoxAdapter(
        child: Padding(padding: EdgeInsets.symmetric(vertical: 60), child: Center(child: CircularProgressIndicator())));
    }
    return SliverPadding(
      padding: const EdgeInsets.fromLTRB(12, 4, 12, 8),
      sliver: SliverGrid(
        gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
          maxCrossAxisExtent: 200, mainAxisExtent: 266, crossAxisSpacing: 10, mainAxisSpacing: 10),
        delegate: SliverChildBuilderDelegate(
          (_, i) {
            final p = _products[i];
            return ProductCard(
              product: p,
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ProductScreen(productId: p.id))),
              onAdd: () => _addToCart(p),
            );
          },
          childCount: _products.length,
        ),
      ),
    );
  }

  Widget _footerLoader() {
    if (_products.isEmpty) return const SizedBox(height: 20);
    if (!_end) {
      return const Padding(padding: EdgeInsets.symmetric(vertical: 18), child: Center(child: CircularProgressIndicator()));
    }
    return const Padding(
      padding: EdgeInsets.fromLTRB(16, 8, 16, 28),
      child: Center(child: Text("You've reached the end", style: TextStyle(color: AppColors.muted, fontSize: 12.5))),
    );
  }

  Widget _emptyOrError() => Padding(
        padding: const EdgeInsets.fromLTRB(24, 60, 24, 60),
        child: Column(children: [
          Icon(_error != null ? Icons.wifi_off_rounded : Icons.inventory_2_outlined, size: 48, color: AppColors.muted),
          const SizedBox(height: 10),
          Text(_error != null ? "Couldn't load products" : 'No products found',
              style: const TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF475569))),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Text(_error!, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
            ),
          const SizedBox(height: 14),
          OutlinedButton.icon(onPressed: () => _load(reset: true), icon: const Icon(Icons.refresh, size: 18), label: const Text('Retry')),
        ]),
      );
}
