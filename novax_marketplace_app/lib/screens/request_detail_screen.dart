import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:novax_core/novax_core.dart';
import '../models/sourcing.dart';
import 'product_screen.dart';

class RequestDetailScreen extends StatefulWidget {
  final int requestId;
  const RequestDetailScreen({super.key, required this.requestId});
  @override
  State<RequestDetailScreen> createState() => _RequestDetailScreenState();
}

class _RequestDetailScreenState extends State<RequestDetailScreen> {
  SourcingRequestFull? _req;
  List<SourcingThread> _threads = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/requests/${widget.requestId}');
      setState(() {
        _req = SourcingRequestFull.fromJson(Map<String, dynamic>.from(res['request']));
        _threads = (res['threads'] as List).map((e) => SourcingThread.fromJson(Map<String, dynamic>.from(e))).toList();
        _loading = false;
      });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Request')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _req == null
              ? const Center(child: Text('Could not load request.'))
              : RefreshIndicator(onRefresh: _load, child: _content(_req!)),
    );
  }

  Widget _content(SourcingRequestFull r) => ListView(padding: const EdgeInsets.all(16), children: [
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Expanded(child: Text(r.title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17))),
              StatusPill(r.status),
            ]),
            const SizedBox(height: 8),
            Text(r.description, style: const TextStyle(color: Color(0xFF475569), height: 1.5, fontSize: 13.5)),
            if (r.budget != null || r.quantity > 0)
              Padding(padding: const EdgeInsets.only(top: 8), child: Text('${r.budget != null ? 'Budget: ${r.budget}  ·  ' : ''}Qty: ${r.quantity}', style: const TextStyle(color: AppColors.muted, fontSize: 12))),
          ]),
        ),
        if (r.imageUrl != null || r.videoUrl != null) ...[
          const SizedBox(height: 14),
          RequestMedia(imageUrl: r.imageUrl, videoUrl: r.videoUrl),
        ],
        const SizedBox(height: 18),
        Row(children: [
          const Text('Vendor Responses', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
          const SizedBox(width: 8),
          Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2), decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(20)), child: Text('${_threads.length}', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700))),
        ]),
        const SizedBox(height: 10),
        if (_threads.isEmpty)
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
            child: const Column(children: [
              Icon(Icons.hourglass_empty, color: AppColors.muted, size: 34),
              SizedBox(height: 8),
              Text("Waiting for vendors to respond. We'll notify you when they do.", textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted, fontSize: 12.5)),
            ]),
          )
        else
          ..._threads.map((t) => _threadTile(t)),
      ]);

  Widget _threadTile(SourcingThread t) => Container(
        margin: const EdgeInsets.only(bottom: 8),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
        child: ListTile(
          leading: CircleAvatar(backgroundColor: AppColors.primary, child: Text(t.vendor.isNotEmpty ? t.vendor[0].toUpperCase() : 'V', style: const TextStyle(color: Colors.white))),
          title: Text(t.vendor, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
          subtitle: Text(
            t.price != null ? '${t.currency ?? ''} ${t.price!.toStringAsFixed(2)} · ${t.message ?? ''}' : (t.message ?? 'Sent an offer'),
            maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12.5),
          ),
          trailing: const Icon(Icons.chat_bubble_outline, color: AppColors.primary),
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ChatScreen(threadId: t.id, vendor: t.vendor))),
        ),
      );
}

/// Thin wrapper over the shared [ChatView].
class ChatScreen extends StatelessWidget {
  final int threadId;
  final String vendor;
  const ChatScreen({super.key, required this.threadId, required this.vendor});

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return Scaffold(
      appBar: AppBar(title: Text(vendor)),
      body: ChatView(
        onLoad: () async {
          final res = await api.get('/threads/$threadId/messages');
          return (res['messages'] as List).map((e) {
            final m = Map<String, dynamic>.from(e);
            final l = m['listing'] is Map ? m['listing'] as Map : null;
            return ChatMessageVM(
              mine: (m['mine'] ?? false) as bool,
              sender: m['sender'] as String?,
              body: m['body'] as String?,
              time: Fmt.chatTime(m['time'] as String?),
              refLabel: l != null ? '${l['name']} · ${l['price'] ?? ''}' : null,
              refImage: l != null ? l['image_url'] as String? : null,
              onRefTap: l != null && l['id'] != null
                  ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => ProductScreen(productId: l['id'] as int)))
                  : null,
            );
          }).toList();
        },
        onSend: (body) => api.post('/threads/$threadId/messages', {'body': body}),
      ),
    );
  }
}
