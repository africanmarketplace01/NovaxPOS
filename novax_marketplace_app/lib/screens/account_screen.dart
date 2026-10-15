import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../config.dart';
import '../state/auth_provider.dart';
import '../theme.dart';
import 'login_screen.dart';
import 'register_screen.dart';
import 'orders_screen.dart';
import 'agency_portal.dart';
import 'vendor_signup_screen.dart';

class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Scaffold(
      appBar: AppBar(title: const Text('Account')),
      body: auth.isAuthenticated ? _signedIn(context, auth) : _guest(context),
    );
  }

  Widget _signedIn(BuildContext context, AuthProvider auth) {
    final c = auth.customer!;
    return ListView(padding: const EdgeInsets.all(16), children: [
      Row(children: [
        CircleAvatar(radius: 28, backgroundColor: AppColors.primary, child: Text(c.name.isNotEmpty ? c.name[0].toUpperCase() : '?', style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700))),
        const SizedBox(width: 14),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(c.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            Text(c.email, style: const TextStyle(color: AppColors.muted, fontSize: 13)),
            if (c.phone != null) Text(c.phone!, style: const TextStyle(color: AppColors.muted, fontSize: 13)),
          ]),
        ),
      ]),
      const SizedBox(height: 24),
      _item(Icons.receipt_long_outlined, 'My orders', 'Track and review your purchases',
          () => Navigator.push(context, MaterialPageRoute(builder: (_) => const OrdersScreen()))),
      _item(Icons.headset_mic_outlined, 'Support', 'Get help with an order', () => showDialog(
            context: context,
            builder: (_) => AlertDialog(
              title: const Text('Support'),
              content: const Text('Need help with an order?\nContact us at support@novasuitespos.com'),
              actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Close'))],
            ),
          )),
      _item(Icons.info_outline, 'About', '${Config.appName} v1.0.0',
          () => showAboutDialog(context: context, applicationName: Config.appName, applicationVersion: '1.0.0', applicationLegalese: '© NovaxSuites')),
      const SizedBox(height: 8),
      _item(Icons.local_shipping_outlined, 'Delivery partner', 'Delivery agency portal — riders, bikes & orders',
          () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AgencyPortal()))),
      const SizedBox(height: 8),
      _item(Icons.storefront_outlined, 'Become a vendor', 'Open your store — pick a plan & sign up',
          () => Navigator.push(context, MaterialPageRoute(builder: (_) => const VendorSignupScreen()))),
      const SizedBox(height: 24),
      OutlinedButton.icon(
        onPressed: () => context.read<AuthProvider>().logout(),
        style: OutlinedButton.styleFrom(foregroundColor: const Color(0xFFEF4444), side: const BorderSide(color: Color(0xFFFECACA)), padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
        icon: const Icon(Icons.logout),
        label: const Text('Sign out', style: TextStyle(fontWeight: FontWeight.w700)),
      ),
    ]);
  }

  Widget _guest(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.person_outline, size: 60, color: AppColors.muted),
            const SizedBox(height: 12),
            const Text('Sign in to your account', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            const SizedBox(height: 6),
            const Text('Track orders, check out faster and manage your details.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted)),
            const SizedBox(height: 20),
            SizedBox(width: double.infinity, child: ElevatedButton(onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen())), child: const Text('Sign in'))),
            const SizedBox(height: 10),
            SizedBox(width: double.infinity, child: OutlinedButton(onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterScreen())), style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))), child: const Text('Create an account'))),
            const SizedBox(height: 24),
            const Divider(),
            const SizedBox(height: 8),
            const Align(alignment: Alignment.centerLeft, child: Text('Portals', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.muted, letterSpacing: .4))),
            const SizedBox(height: 8),
            SizedBox(width: double.infinity, child: TextButton.icon(
              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AgencyPortal())),
              icon: const Icon(Icons.local_shipping_outlined),
              label: const Text('Delivery partner portal'),
            )),
            SizedBox(width: double.infinity, child: TextButton.icon(
              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const VendorSignupScreen())),
              icon: const Icon(Icons.storefront_outlined),
              label: const Text('Become a vendor'),
            )),
          ]),
        ),
      );

  Widget _item(IconData icon, String title, String sub, VoidCallback onTap) => Card(
        margin: const EdgeInsets.only(bottom: 8),
        child: ListTile(
          onTap: onTap,
          leading: Icon(icon, color: AppColors.primary),
          title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
          subtitle: Text(sub, style: const TextStyle(fontSize: 12, color: AppColors.muted)),
          trailing: const Icon(Icons.chevron_right, color: AppColors.muted),
        ),
      );
}
