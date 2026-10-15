import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:video_player/video_player.dart';
import '../services/api_client.dart';
import '../theme.dart';

class NewRequestScreen extends StatefulWidget {
  const NewRequestScreen({super.key});
  @override
  State<NewRequestScreen> createState() => _NewRequestScreenState();
}

class _NewRequestScreenState extends State<NewRequestScreen> {
  final _title = TextEditingController();
  final _desc = TextEditingController();
  final _category = TextEditingController();
  final _brand = TextEditingController();
  final _qty = TextEditingController(text: '1');
  final _budgetMin = TextEditingController();
  final _budgetMax = TextEditingController();
  final _location = TextEditingController();
  final _picker = ImagePicker();

  XFile? _image;
  XFile? _video;
  int _videoBytes = 0;
  VideoPlayerController? _vc;
  bool _busy = false;
  String? _error;

  static const int _maxVideoBytes = 80 * 1024 * 1024;

  @override
  void dispose() {
    _vc?.dispose();
    super.dispose();
  }

  Future<void> _pickImage() async {
    final x = await _picker.pickImage(source: ImageSource.gallery, maxWidth: 1600, imageQuality: 85);
    if (x != null) setState(() => _image = x);
  }

  Future<void> _pickVideo() async {
    final x = await _picker.pickVideo(source: ImageSource.gallery, maxDuration: const Duration(seconds: 120));
    if (x == null) return;
    final size = await File(x.path).length();
    if (size > _maxVideoBytes) {
      setState(() => _error = 'That video is ${_fmtSize(size)} — larger than 80 MB. Please trim it or lower the quality.');
      return;
    }
    // Build a preview.
    await _vc?.dispose();
    final vc = VideoPlayerController.file(File(x.path));
    try {
      await vc.initialize();
      vc.setLooping(true);
    } catch (_) {}
    if (!mounted) { vc.dispose(); return; }
    setState(() { _video = x; _videoBytes = size; _vc = vc; _error = null; });
  }

  void _removeImage() => setState(() => _image = null);

  void _removeVideo() {
    _vc?.dispose();
    setState(() { _video = null; _vc = null; _videoBytes = 0; });
  }

  Future<void> _submit() async {
    if (_title.text.trim().isEmpty || _desc.text.trim().isEmpty) {
      setState(() => _error = 'Please add a title and description.');
      return;
    }
    setState(() { _busy = true; _error = null; });
    try {
      final fields = <String, String>{
        'title': _title.text.trim(),
        'description': _desc.text.trim(),
        if (_category.text.trim().isNotEmpty) 'category': _category.text.trim(),
        if (_brand.text.trim().isNotEmpty) 'brand': _brand.text.trim(),
        'quantity': _qty.text.trim().isEmpty ? '1' : _qty.text.trim(),
        if (_budgetMin.text.trim().isNotEmpty) 'budget_min': _budgetMin.text.trim(),
        if (_budgetMax.text.trim().isNotEmpty) 'budget_max': _budgetMax.text.trim(),
        if (_location.text.trim().isNotEmpty) 'location': _location.text.trim(),
      };
      final files = <String, String>{
        if (_image != null) 'image': _image!.path,
        if (_video != null) 'video': _video!.path,
      };
      await context.read<ApiClient>().postMultipart('/requests', fields: fields, files: files);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Request sent to all vendors')));
        Navigator.pop(context, true);
      }
    } catch (e) {
      setState(() => _error = _friendlyError(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  // Turn raw upload errors into something actionable.
  String _friendlyError(Object e) {
    final s = e.toString();
    if (s.contains('503') || s.contains('502') || s.contains('504') || s.contains('413')) {
      return 'Upload failed — the server rejected the file (it may be too large or timed out). '
          'Try a shorter/smaller video, or send the request without the video.';
    }
    if (s.contains('SocketException') || s.contains('timed out') || s.contains('Connection')) {
      return 'Network problem while uploading. Check your connection and try again.';
    }
    return s;
  }

  static String _fmtSize(int bytes) {
    if (bytes >= 1024 * 1024) return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
    return '${(bytes / 1024).toStringAsFixed(0)} KB';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('New Sourcing Request')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        const Text('What are you looking for?', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.dark)),
        const SizedBox(height: 4),
        const Text('Every vendor gets notified and can respond with an offer.', style: TextStyle(color: AppColors.muted, fontSize: 13)),
        const SizedBox(height: 18),
        if (_error != null)
          Container(
            padding: const EdgeInsets.all(12),
            margin: const EdgeInsets.only(bottom: 14),
            decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(10)),
            child: Row(children: [
              const Icon(Icons.error_outline, color: Color(0xFF991B1B), size: 18),
              const SizedBox(width: 8),
              Expanded(child: Text(_error!, style: const TextStyle(color: Color(0xFF991B1B), fontSize: 12.5))),
            ]),
          ),
        TextField(controller: _title, decoration: const InputDecoration(labelText: 'Title *')),
        const SizedBox(height: 12),
        TextField(controller: _desc, maxLines: 4, decoration: const InputDecoration(labelText: 'Description *')),
        const SizedBox(height: 12),
        Row(children: [
          Expanded(child: TextField(controller: _category, decoration: const InputDecoration(labelText: 'Category'))),
          const SizedBox(width: 10),
          Expanded(child: TextField(controller: _brand, decoration: const InputDecoration(labelText: 'Brand'))),
        ]),
        const SizedBox(height: 12),
        Row(children: [
          Expanded(child: TextField(controller: _qty, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Qty'))),
          const SizedBox(width: 10),
          Expanded(child: TextField(controller: _budgetMin, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Budget from'))),
          const SizedBox(width: 10),
          Expanded(child: TextField(controller: _budgetMax, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Budget to'))),
        ]),
        const SizedBox(height: 12),
        TextField(controller: _location, decoration: const InputDecoration(labelText: 'Your location')),
        const SizedBox(height: 18),

        // ── Attachments with live preview ──────────────────────────────────
        const Text('Attachments', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: AppColors.ink)),
        const SizedBox(height: 8),
        Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Expanded(child: _image == null
              ? _addTile(icon: Icons.image_outlined, label: 'Add photo', onTap: _pickImage)
              : _imagePreview()),
          const SizedBox(width: 10),
          Expanded(child: _video == null
              ? _addTile(icon: Icons.videocam_outlined, label: 'Add video', onTap: _pickVideo)
              : _videoPreview()),
        ]),
        const SizedBox(height: 6),
        const Text('Photo up to 5 MB · video up to ~2 min (80 MB).', style: TextStyle(color: AppColors.muted, fontSize: 11.5)),
        const SizedBox(height: 22),
        ElevatedButton.icon(
          onPressed: _busy ? null : _submit,
          icon: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.send),
          label: Text(_busy ? 'Sending…' : 'Send to all vendors'),
        ),
      ]),
    );
  }

  Widget _addTile({required IconData icon, required String label, required VoidCallback onTap}) => InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          height: 120,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
            Icon(icon, color: AppColors.primary),
            const SizedBox(height: 6),
            Text(label, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppColors.ink)),
          ]),
        ),
      );

  Widget _previewFrame({required Widget child, required VoidCallback onRemove, String? caption}) => Stack(children: [
        Container(
          height: 120,
          width: double.infinity,
          clipBehavior: Clip.antiAlias,
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.primary, width: 1.5), color: Colors.black),
          child: child,
        ),
        Positioned(
          top: 6, right: 6,
          child: GestureDetector(
            onTap: onRemove,
            child: Container(
              padding: const EdgeInsets.all(4),
              decoration: const BoxDecoration(color: Colors.black54, shape: BoxShape.circle),
              child: const Icon(Icons.close, size: 16, color: Colors.white),
            ),
          ),
        ),
        if (caption != null)
          Positioned(
            left: 0, right: 0, bottom: 0,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              color: Colors.black54,
              child: Text(caption, style: const TextStyle(color: Colors.white, fontSize: 10.5), maxLines: 1, overflow: TextOverflow.ellipsis),
            ),
          ),
      ]);

  Widget _imagePreview() => _previewFrame(
        onRemove: _removeImage,
        caption: 'Photo',
        child: Image.file(File(_image!.path), fit: BoxFit.cover),
      );

  Widget _videoPreview() {
    final ready = _vc != null && _vc!.value.isInitialized;
    return _previewFrame(
      onRemove: _removeVideo,
      caption: 'Video · ${_fmtSize(_videoBytes)}',
      child: GestureDetector(
        onTap: () {
          if (!ready) return;
          setState(() => _vc!.value.isPlaying ? _vc!.pause() : _vc!.play());
        },
        child: Stack(alignment: Alignment.center, fit: StackFit.expand, children: [
          if (ready)
            FittedBox(fit: BoxFit.cover, child: SizedBox(width: _vc!.value.size.width, height: _vc!.value.size.height, child: VideoPlayer(_vc!)))
          else
            const Center(child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)),
          if (ready && !_vc!.value.isPlaying)
            const Icon(Icons.play_circle_fill, color: Colors.white70, size: 40),
        ]),
      ),
    );
  }
}
