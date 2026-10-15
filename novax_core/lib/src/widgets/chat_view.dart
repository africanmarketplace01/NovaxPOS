import 'dart:async';
import 'package:flutter/material.dart';
import '../theme.dart';

/// A single chat message in a view-agnostic shape.
class ChatMessageVM {
  final bool mine;
  final String? sender;
  final String? body;
  final String? time;
  final String? refLabel; // optional "Product — price" reference label
  final String? refImage; // optional product reference thumbnail
  final VoidCallback? onRefTap; // optional: open the referenced product
  const ChatMessageVM({required this.mine, this.sender, this.body, this.time, this.refLabel, this.refImage, this.onRefTap});
}

/// Reusable polling chat used by both the customer and vendor apps.
/// Provide [onLoad] (fetch messages) and [onSend] (send a body).
/// Optionally provide [onAttach] to show a "+" button that lets the user
/// attach something (e.g. a product reference); it should perform its own
/// send — the view reloads afterwards.
class ChatView extends StatefulWidget {
  final Future<List<ChatMessageVM>> Function() onLoad;
  final Future<void> Function(String body) onSend;
  final Future<void> Function()? onAttach;
  final Duration pollEvery;
  const ChatView({super.key, required this.onLoad, required this.onSend, this.onAttach, this.pollEvery = const Duration(seconds: 4)});

  @override
  State<ChatView> createState() => _ChatViewState();
}

class _ChatViewState extends State<ChatView> {
  final _input = TextEditingController();
  final _scroll = ScrollController();
  List<ChatMessageVM> _messages = const [];
  Timer? _timer;
  bool _sending = false;
  bool _attaching = false;

  Future<void> _attach() async {
    if (widget.onAttach == null || _attaching) return;
    setState(() => _attaching = true);
    try {
      await widget.onAttach!();
      await _load();
    } catch (_) {} finally {
      if (mounted) setState(() => _attaching = false);
    }
  }

  @override
  void initState() {
    super.initState();
    _load();
    _timer = Timer.periodic(widget.pollEvery, (_) => _load());
  }

  @override
  void dispose() {
    _timer?.cancel();
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final msgs = await widget.onLoad();
      if (!mounted) return;
      setState(() => _messages = msgs);
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (_scroll.hasClients) _scroll.jumpTo(_scroll.position.maxScrollExtent);
      });
    } catch (_) {}
  }

  Future<void> _send() async {
    final body = _input.text.trim();
    if (body.isEmpty) return;
    _input.clear();
    setState(() => _sending = true);
    try {
      await widget.onSend(body);
      await _load();
    } catch (_) {} finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      Expanded(
        child: _messages.isEmpty
            ? const Center(child: Text('No messages yet. Say hello!', style: TextStyle(color: AppColors.muted)))
            : ListView.builder(
                controller: _scroll,
                padding: const EdgeInsets.all(12),
                itemCount: _messages.length,
                itemBuilder: (_, i) => _bubble(_messages[i]),
              ),
      ),
      SafeArea(
        child: Container(
          padding: const EdgeInsets.fromLTRB(10, 8, 10, 8),
          decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: AppColors.border))),
          child: Row(children: [
            if (widget.onAttach != null)
              IconButton(
                tooltip: 'Attach product',
                onPressed: _attaching ? null : _attach,
                icon: _attaching
                    ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.add_shopping_cart, color: AppColors.primary),
              ),
            Expanded(
              child: TextField(
                controller: _input,
                textInputAction: TextInputAction.send,
                onSubmitted: (_) => _send(),
                decoration: const InputDecoration(hintText: 'Type a message…', contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 10)),
              ),
            ),
            const SizedBox(width: 8),
            CircleAvatar(
              backgroundColor: AppColors.primary,
              child: IconButton(
                onPressed: _sending ? null : _send,
                icon: _sending
                    ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Icon(Icons.send, color: Colors.white),
              ),
            ),
          ]),
        ),
      ),
    ]);
  }

  Widget _bubble(ChatMessageVM m) => Align(
        alignment: m.mine ? Alignment.centerRight : Alignment.centerLeft,
        child: Container(
          margin: const EdgeInsets.symmetric(vertical: 4),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * .75),
          decoration: BoxDecoration(
            color: m.mine ? AppColors.primary : Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: m.mine ? null : Border.all(color: AppColors.border),
          ),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            if (m.body != null && m.body!.isNotEmpty)
              Text(m.body!, style: TextStyle(color: m.mine ? Colors.white : AppColors.ink, fontSize: 13.5)),
            if (m.refLabel != null) ...[
              if (m.body != null && m.body!.isNotEmpty) const SizedBox(height: 6),
              _refCard(m),
            ],
            const SizedBox(height: 3),
            Text('${m.sender ?? ''} · ${m.time ?? ''}', style: TextStyle(fontSize: 10, color: m.mine ? Colors.white70 : AppColors.muted)),
          ]),
        ),
      );

  Widget _refCard(ChatMessageVM m) {
    final onLight = !m.mine;
    final border = m.mine ? Colors.white.withValues(alpha: .35) : AppColors.border;
    final textColor = m.mine ? Colors.white : AppColors.ink;
    return GestureDetector(
      onTap: m.onRefTap,
      child: Container(
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(
          color: m.mine ? Colors.white.withValues(alpha: .12) : const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: border),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: SizedBox(
              width: 38, height: 38,
              child: m.refImage != null && m.refImage!.isNotEmpty
                  ? Image.network(m.refImage!, fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => _refPh(onLight))
                  : _refPh(onLight),
            ),
          ),
          const SizedBox(width: 8),
          Flexible(
            child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(m.refLabel!, maxLines: 2, overflow: TextOverflow.ellipsis,
                  style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: textColor)),
              if (m.onRefTap != null)
                Text('View product', style: TextStyle(fontSize: 10, color: m.mine ? Colors.white70 : AppColors.primary)),
            ]),
          ),
        ]),
      ),
    );
  }

  Widget _refPh(bool onLight) => Container(
        color: onLight ? const Color(0xFFEEF2F7) : Colors.white24,
        child: Icon(Icons.inventory_2_outlined, size: 18, color: onLight ? const Color(0xFFCBD5E1) : Colors.white70),
      );
}
