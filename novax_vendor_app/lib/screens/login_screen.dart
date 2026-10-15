import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../config.dart';
import '../state/auth_provider.dart';
import '../theme.dart';
import 'register_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _store = TextEditingController();
  final _login = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  String? _error;

  Future<void> _submit() async {
    if (_store.text.trim().isEmpty ||
        _login.text.trim().isEmpty ||
        _password.text.isEmpty) {
      setState(() => _error = 'Enter your store, email/username and password.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await context
          .read<AuthProvider>()
          .login(_store.text.trim(), _login.text.trim(), _password.text);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child:
              Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            const SizedBox(height: 40),
            Center(
              child: Image.asset(
                'assets/logo.png',
                height: 80,
                width: 100,
                fit: BoxFit.fitWidth,
                errorBuilder: (_, __, ___) => const Icon(Icons.storefront,
                    size: 44, color: AppColors.primary),
              ),
            ),
            const SizedBox(height: 18),
            const Text(
                'Welcome to NovaxSuites POS - Vendor',
                textAlign: TextAlign.center,
                style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: AppColors.dark
                )),
            const SizedBox(height: 4),
            const Text('Login to your store to manage products, orders and more.',
                style: TextStyle(color: AppColors.muted)),
            const SizedBox(height: 26),
            if (_error != null)
              Container(
                padding: const EdgeInsets.all(12),
                margin: const EdgeInsets.only(bottom: 14),
                decoration: BoxDecoration(
                    color: const Color(0xFFFEE2E2),
                    borderRadius: BorderRadius.circular(10)),
                child: Text(_error!,
                    style: const TextStyle(color: Color(0xFF991B1B))),
              ),
            TextField(
              controller: _store,
              autocorrect: false,
              decoration: const InputDecoration(
                  labelText: 'Store subdomain',
                  hintText: 'subdomain',
                  suffixText: '.${Config.rootDomain}'),
            ),
            const SizedBox(height: 14),
            TextField(
                controller: _login,
                decoration:
                    const InputDecoration(labelText: 'Email or username')),
            const SizedBox(height: 14),
            TextField(
                controller: _password,
                obscureText: true,
                decoration: const InputDecoration(labelText: 'Password')),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _busy ? null : _submit,
              child: _busy
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white))
                  : const Text('Sign in'),
            ),
            const SizedBox(height: 18),
            Row(mainAxisAlignment: MainAxisAlignment.center, children: [
              const Text("Don't have a store?", style: TextStyle(color: AppColors.muted, fontSize: 13)),
              TextButton(
                onPressed: _busy
                    ? null
                    : () async {
                        final tenant = await Navigator.push<String>(context, MaterialPageRoute(builder: (_) => const RegisterScreen()));
                        if (tenant != null && mounted) setState(() => _store.text = tenant);
                      },
                child: const Text('Create one', style: TextStyle(fontWeight: FontWeight.w700)),
              ),
            ]),
          ]),
        ),
      ),
    );
  }
}
