import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';
import '../models/sourcing.dart';
import '../state/auth_provider.dart';
import 'login_screen.dart';
import 'new_request_screen.dart';
import 'request_detail_screen.dart';

class RequestsScreen extends StatefulWidget {
  const RequestsScreen({super.key});
  @override
  State<RequestsScreen> createState() => _RequestsScreenState();
}

class _RequestsScreenState extends State<RequestsScreen> {
  List<SourcingSummary> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (!context.read<AuthProvider>().isAuthenticated) {
      setState(() => _loading = false);
      return;
    }
    setState(() => _loading = true);
    try {
      final res = await context.read<ApiClient>().get('/requests');
      _items = (res['data'] as List).map((e) => SourcingSummary.fromJson(Map<String, dynamic>.from(e))).toList();
    } catch (_) {} finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _new() async {
    final created = await Navigator.push(context, MaterialPageRoute(builder: (_) => const NewRequestScreen()));
    if (created == true) _load();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Scaffold(
      appBar: AppBar(title: const Text('Sourcing Requests')),
      floatingActionButton: auth.isAuthenticated
          ? FloatingActionButton.extended(onPressed: _new, icon: const Icon(Icons.add), label: const Text('New request'))
          : null,
      body: !auth.isAuthenticated
          ? _signIn()
          : _loading
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _items.isEmpty ? _empty() : ListView.separated(
                    padding: const EdgeInsets.fromLTRB(12, 12, 12, 90),
                    itemCount: _items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (_, i) => _tile(_items[i]),
                  ),
                ),
    );
  }

  Widget _tile(SourcingSummary r) => InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => RequestDetailScreen(requestId: r.id))).then((_) => _load()),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
          child: Row(children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: SizedBox(
                width: 54, height: 54,
                child: r.imageUrl != null
                    ? CachedNetworkImage(imageUrl: r.imageUrl!, fit: BoxFit.cover)
                    : Container(color: r.hasVideo ? AppColors.dark : const Color(0xFFF1F5F9),
                        child: Icon(r.hasVideo ? Icons.play_arrow : Icons.campaign_outlined, color: r.hasVideo ? Colors.white : const Color(0xFFCBD5E1))),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(r.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                const SizedBox(height: 3),
                Text('${Fmt.date(r.createdAt)}${r.category != null ? ' · ${r.category}' : ''}', style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
              ]),
            ),
            Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(20)),
                child: Text('${r.responses}', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700)),
              ),
              const SizedBox(height: 5),
              Text(r.status, style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: r.status == 'open' ? const Color(0xFF16A34A) : AppColors.muted)),
            ]),
          ]),
        ),
      );

  Widget _empty() => ListView(children: const [
        SizedBox(height: 120),
        Icon(Icons.campaign_outlined, size: 56, color: AppColors.muted),
        SizedBox(height: 10),
        Center(child: Text('No requests yet', style: TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF475569)))),
        SizedBox(height: 4),
        Center(child: Padding(padding: EdgeInsets.symmetric(horizontal: 40), child: Text("Looking for a product or spare part? Post a request and let vendors come to you.", textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted, fontSize: 13)))),
      ]);

  Widget _signIn() => Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.lock_outline, size: 52, color: AppColors.muted),
          const SizedBox(height: 10),
          const Text('Sign in to post sourcing requests', style: TextStyle(color: AppColors.muted)),
          const SizedBox(height: 14),
          ElevatedButton(
            onPressed: () async {
              final ok = await Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
              if (ok == true) _load();
            },
            child: const Text('Sign in'),
          ),
        ]),
      );
}
