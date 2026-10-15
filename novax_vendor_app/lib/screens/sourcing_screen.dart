import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';
import '../models/vendor_models.dart';

class SourcingScreen extends StatefulWidget {
  const SourcingScreen({super.key});
  @override
  State<SourcingScreen> createState() => _SourcingScreenState();
}

class _SourcingScreenState extends State<SourcingScreen> {
  List<VendorRequest> _items = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/sourcing');
      _items = (res['data'] as List).map((e) => VendorRequest.fromJson(Map<String, dynamic>.from(e))).toList();
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // Open the respond/detail view (request info, media, product references, form).
  Future<void> _view(VendorRequest r) async {
    final res = await Navigator.push(context, MaterialPageRoute(builder: (_) => RespondScreen(request: r)));
    if (res != null && mounted) {
      // Vendor just sent a first response — jump straight into the chat.
      Navigator.push(context, MaterialPageRoute(builder: (_) => SourcingChatScreen(threadId: res as int, title: r.title)));
    }
    _load();
  }

  // Open the chat directly using the known thread id.
  Future<void> _openChat(VendorRequest r) async {
    if (r.threadId == null) { _view(r); return; }
    await Navigator.push(context, MaterialPageRoute(builder: (_) => SourcingChatScreen(threadId: r.threadId!, title: r.title)));
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sourcing Requests')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _error != null && _items.isEmpty
                  ? ErrorRetry(message: _error!, onRetry: _load)
                  : _items.isEmpty
                  ? ListView(children: const [SizedBox(height: 120), Center(child: Text('No open requests.', style: TextStyle(color: AppColors.muted)))])
                  : ListView.separated(
                      padding: const EdgeInsets.all(12),
                      itemCount: _items.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (_, i) => _tile(_items[i]),
                    ),
            ),
    );
  }

  Widget _tile(VendorRequest r) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(child: Text(r.title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14))),
            if (r.answered)
              Container(padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2), decoration: BoxDecoration(color: const Color(0xFFDCFCE7), borderRadius: BorderRadius.circular(20)), child: const Text('Responded', style: TextStyle(color: Color(0xFF166534), fontSize: 10.5, fontWeight: FontWeight.w700))),
          ]),
          const SizedBox(height: 4),
          Text(r.description, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Color(0xFF475569), fontSize: 12.5)),
          const SizedBox(height: 6),
          Row(children: [
            const Icon(Icons.schedule, size: 12, color: AppColors.muted),
            const SizedBox(width: 4),
            Expanded(
              child: Text(
                [
                  r.createdAt ?? r.createdAgo ?? '',
                  if (r.category != null) r.category!,
                  'Qty ${r.quantity}',
                  if (r.budget != null) r.budget!,
                ].where((s) => s.isNotEmpty).join('  ·  '),
                style: const TextStyle(color: AppColors.muted, fontSize: 11.5),
              ),
            ),
          ]),
          const SizedBox(height: 10),
          if (r.answered)
            Row(children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _view(r),
                  style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10), side: const BorderSide(color: AppColors.border)),
                  icon: const Icon(Icons.visibility_outlined, size: 16),
                  label: const Text('View'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _openChat(r),
                  style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10)),
                  icon: _chatIcon(r.unread),
                  label: const Text('Open chat'),
                ),
              ),
            ])
          else
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _view(r),
                style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10)),
                icon: const Icon(Icons.reply, size: 16),
                label: const Text('Respond'),
              ),
            ),
        ]),
      );

  Widget _chatIcon(int unread) {
    if (unread <= 0) return const Icon(Icons.chat_bubble_outline, size: 16);
    return Stack(clipBehavior: Clip.none, children: [
      const Icon(Icons.chat_bubble_outline, size: 16),
      Positioned(
        right: -6, top: -6,
        child: Container(
          padding: const EdgeInsets.all(3),
          decoration: const BoxDecoration(color: Color(0xFFEF4444), shape: BoxShape.circle),
          constraints: const BoxConstraints(minWidth: 14, minHeight: 14),
          child: Text('$unread', textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.w800)),
        ),
      ),
    ]);
  }
}

// ── Respond / detail ─────────────────────────────────────────────────────────
class RespondScreen extends StatefulWidget {
  final VendorRequest request;
  const RespondScreen({super.key, required this.request});
  @override
  State<RespondScreen> createState() => _RespondScreenState();
}

class _RespondScreenState extends State<RespondScreen> {
  final _message = TextEditingController();
  final _price = TextEditingController();
  final List<ProductRef> _refs = [];
  bool _busy = false;

  bool get _answered => widget.request.answered;

  Future<void> _pickRef() async {
    final picked = await showProductRefPicker(context);
    if (picked != null && !_refs.any((r) => r.id == picked.id)) {
      setState(() => _refs.add(picked));
    }
  }

  Future<void> _send() async {
    if (_message.text.trim().isEmpty) return;
    setState(() => _busy = true);
    try {
      final res = await context.read<ApiClient>().post('/vendor/sourcing/${widget.request.id}/respond', {
        'message': _message.text.trim(),
        if (_price.text.trim().isNotEmpty) 'price': _price.text.trim(),
        if (_refs.isNotEmpty) 'listing_ids': _refs.map((r) => r.id).toList(),
      });
      if (mounted) Navigator.pop(context, res['thread_id']);
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final r = widget.request;
    return Scaffold(
      appBar: AppBar(title: Text(_answered ? 'Request' : 'Respond')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(r.title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
            const SizedBox(height: 6),
            Text(r.description, style: const TextStyle(color: Color(0xFF475569), fontSize: 13)),
            const SizedBox(height: 8),
            Text([
              r.createdAt ?? r.createdAgo ?? '',
              if (r.category != null) r.category!,
              'Qty ${r.quantity}',
              if (r.budget != null) r.budget!,
            ].where((s) => s.isNotEmpty).join('  ·  '), style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
          ]),
        ),
        if (r.imageUrl != null || r.videoUrl != null) ...[
          const SizedBox(height: 12),
          RequestMedia(imageUrl: r.imageUrl, videoUrl: r.videoUrl),
        ],
        const SizedBox(height: 16),
        if (_answered) ...[
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () {
                if (r.threadId != null) {
                  Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => SourcingChatScreen(threadId: r.threadId!, title: r.title)));
                }
              },
              icon: const Icon(Icons.chat_bubble_outline),
              label: const Text('Open chat'),
            ),
          ),
          const SizedBox(height: 8),
          const Text('You have already responded. Send more messages and product references from the chat.',
              style: TextStyle(color: AppColors.muted, fontSize: 12)),
        ] else ...[
          TextField(controller: _message, maxLines: 4, decoration: const InputDecoration(labelText: 'Your message *', hintText: 'e.g. Yes, we have this in stock…')),
          const SizedBox(height: 12),
          TextField(controller: _price, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Your price (optional)')),
          const SizedBox(height: 16),
          Row(children: [
            const Text('Product references', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
            const Spacer(),
            TextButton.icon(onPressed: _pickRef, icon: const Icon(Icons.add, size: 18), label: const Text('Add')),
          ]),
          if (_refs.isEmpty)
            const Text('Attach one or more of your listed products so the customer can view them.',
                style: TextStyle(color: AppColors.muted, fontSize: 12))
          else
            ..._refs.map((ref) => _refRow(ref)),
          const SizedBox(height: 20),
          ElevatedButton.icon(
            onPressed: _busy ? null : _send,
            icon: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.send),
            label: const Text('Send response'),
          ),
        ],
      ]),
    );
  }

  Widget _refRow(ProductRef ref) => Container(
        margin: const EdgeInsets.only(top: 8),
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(10), border: Border.all(color: AppColors.border)),
        child: Row(children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: SizedBox(
              width: 40, height: 40,
              child: ref.imageUrl != null
                  ? Image.network(ref.imageUrl!, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const _RefPh())
                  : const _RefPh(),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(ref.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5)),
            Text(ref.priceLabel, style: const TextStyle(color: AppColors.primary, fontSize: 11.5, fontWeight: FontWeight.w700)),
          ])),
          IconButton(icon: const Icon(Icons.close, size: 18, color: AppColors.muted), onPressed: () => setState(() => _refs.removeWhere((x) => x.id == ref.id))),
        ]),
      );
}

class _RefPh extends StatelessWidget {
  const _RefPh();
  @override
  Widget build(BuildContext context) => Container(color: const Color(0xFFEEF2F7), child: const Icon(Icons.inventory_2_outlined, size: 18, color: Color(0xFFCBD5E1)));
}

// ── Product reference picker (shared bottom sheet) ───────────────────────────
Future<ProductRef?> showProductRefPicker(BuildContext context) {
  return showModalBottomSheet<ProductRef>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.white,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(18))),
    builder: (_) => const _ProductRefPicker(),
  );
}

class _ProductRefPicker extends StatefulWidget {
  const _ProductRefPicker();
  @override
  State<_ProductRefPicker> createState() => _ProductRefPickerState();
}

class _ProductRefPickerState extends State<_ProductRefPicker> {
  final _search = TextEditingController();
  List<ProductRef> _items = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load([String q = '']) async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<ApiClient>().get('/vendor/sourcing/listings', query: {if (q.isNotEmpty) 'q': q});
      _items = (res['data'] as List).map((e) => ProductRef.fromJson(Map.from(e))).toList();
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SizedBox(
        height: MediaQuery.of(context).size.height * .7,
        child: Column(children: [
          const SizedBox(height: 10),
          Container(width: 40, height: 4, decoration: BoxDecoration(color: const Color(0xFFE2E8F0), borderRadius: BorderRadius.circular(2))),
          const Padding(padding: EdgeInsets.all(12), child: Text('Attach a product', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15))),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: TextField(
              controller: _search,
              onSubmitted: _load,
              decoration: const InputDecoration(hintText: 'Search your listings…', prefixIcon: Icon(Icons.search)),
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? ErrorRetry(message: _error!, onRetry: () => _load(_search.text.trim()))
                    : _items.isEmpty
                        ? const Center(child: Padding(padding: EdgeInsets.all(24), child: Text('No listed products. Enable products for the global market first.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted))))
                        : ListView.separated(
                            padding: const EdgeInsets.all(12),
                            itemCount: _items.length,
                            separatorBuilder: (_, __) => const Divider(height: 1),
                            itemBuilder: (_, i) {
                              final p = _items[i];
                              return ListTile(
                                leading: ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: SizedBox(
                                    width: 44, height: 44,
                                    child: p.imageUrl != null
                                        ? Image.network(p.imageUrl!, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const _RefPh())
                                        : const _RefPh(),
                                  ),
                                ),
                                title: Text(p.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                                subtitle: Text(p.priceLabel, style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w700, fontSize: 12)),
                                trailing: const Icon(Icons.add_circle_outline, color: AppColors.primary),
                                onTap: () => Navigator.pop(context, p),
                              );
                            },
                          ),
          ),
        ]),
      ),
    );
  }
}

// ── Chat (thin wrapper over the shared ChatView) ─────────────────────────────
class SourcingChatScreen extends StatelessWidget {
  final int threadId;
  final String title;
  const SourcingChatScreen({super.key, required this.threadId, required this.title});

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return Scaffold(
      appBar: AppBar(title: Text(title, maxLines: 1, overflow: TextOverflow.ellipsis)),
      body: ChatView(
        onLoad: () async {
          final res = await api.get('/vendor/threads/$threadId/messages');
          return (res['messages'] as List).map((e) {
            final m = Map<String, dynamic>.from(e);
            final l = m['listing'] is Map ? m['listing'] as Map : null;
            return ChatMessageVM(
              mine: (m['mine'] ?? false) as bool,
              sender: m['sender'] as String?,
              body: m['body'] as String?,
              time: m['time'] as String?,
              refLabel: l != null ? '${l['name']} · ${l['price'] ?? ''}' : null,
              refImage: l != null ? l['image_url'] as String? : null,
            );
          }).toList();
        },
        onSend: (body) => api.post('/vendor/threads/$threadId/messages', {'body': body}),
        onAttach: () async {
          final ref = await showProductRefPicker(context);
          if (ref != null) {
            await api.post('/vendor/threads/$threadId/messages', {'listing_id': ref.id});
          }
        },
      ),
    );
  }
}
