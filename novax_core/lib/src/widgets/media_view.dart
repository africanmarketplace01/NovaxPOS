import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:video_player/video_player.dart';
import '../theme.dart';

/// Shows a sourcing request's photo and/or video.
/// - The photo is tappable to view full-screen (pinch to zoom).
/// - The video shows a poster with a play button and opens a full-screen player.
class RequestMedia extends StatelessWidget {
  final String? imageUrl;
  final String? videoUrl;
  const RequestMedia({super.key, this.imageUrl, this.videoUrl});

  bool get _hasImage => imageUrl != null && imageUrl!.trim().isNotEmpty;
  bool get _hasVideo => videoUrl != null && videoUrl!.trim().isNotEmpty;
  bool get hasAny => _hasImage || _hasVideo;

  @override
  Widget build(BuildContext context) {
    if (!hasAny) return const SizedBox.shrink();
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      if (_hasImage) ...[
        GestureDetector(
          onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => _FullImagePage(url: imageUrl!))),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: CachedNetworkImage(
              imageUrl: imageUrl!,
              width: double.infinity,
              height: 200,
              fit: BoxFit.cover,
              placeholder: (_, __) => Container(height: 200, color: const Color(0xFFF1F5F9), child: const Center(child: CircularProgressIndicator())),
              errorWidget: (_, __, ___) => Container(height: 140, color: const Color(0xFFF1F5F9),
                  child: const Center(child: Icon(Icons.broken_image_outlined, color: Color(0xFFCBD5E1), size: 36))),
            ),
          ),
        ),
        if (_hasVideo) const SizedBox(height: 10),
      ],
      if (_hasVideo)
        GestureDetector(
          onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => VideoPlayerPage(url: videoUrl!))),
          child: Container(
            height: 150,
            width: double.infinity,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              gradient: const LinearGradient(colors: [AppColors.dark, Color(0xFF0B2A4A)], begin: Alignment.topLeft, end: Alignment.bottomRight),
            ),
            child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
              Container(
                width: 54, height: 54,
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: .18), shape: BoxShape.circle),
                child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 34),
              ),
              const SizedBox(height: 8),
              const Text('Watch video', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
            ]),
          ),
        ),
    ]);
  }
}

/// Full-screen network video player with basic controls.
class VideoPlayerPage extends StatefulWidget {
  final String url;
  const VideoPlayerPage({super.key, required this.url});
  @override
  State<VideoPlayerPage> createState() => _VideoPlayerPageState();
}

class _VideoPlayerPageState extends State<VideoPlayerPage> {
  late final VideoPlayerController _c;
  bool _ready = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _c = VideoPlayerController.networkUrl(Uri.parse(widget.url));
    _c.initialize().then((_) {
      if (!mounted) return;
      setState(() => _ready = true);
      _c.play();
    }).catchError((e) {
      if (mounted) setState(() => _error = e.toString());
    });
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ar = (_ready && _c.value.aspectRatio > 0) ? _c.value.aspectRatio : 16 / 9;
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(backgroundColor: Colors.black, foregroundColor: Colors.white, elevation: 0, title: const Text('Video')),
      body: Center(
        child: _error != null
            ? const Padding(padding: EdgeInsets.all(24), child: Text('Could not play this video.', textAlign: TextAlign.center, style: TextStyle(color: Colors.white70)))
            : _ready
                ? AspectRatio(aspectRatio: ar, child: VideoPlayer(_c))
                : const CircularProgressIndicator(color: Colors.white),
      ),
      bottomNavigationBar: _ready
          ? SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 6, 12, 6),
                child: Column(mainAxisSize: MainAxisSize.min, children: [
                  VideoProgressIndicator(_c, allowScrubbing: true,
                      colors: const VideoProgressColors(playedColor: AppColors.primary, bufferedColor: Colors.white30, backgroundColor: Colors.white12)),
                  ValueListenableBuilder<VideoPlayerValue>(
                    valueListenable: _c,
                    builder: (_, v, __) => Row(children: [
                      IconButton(
                        color: Colors.white,
                        icon: Icon(v.isPlaying ? Icons.pause : Icons.play_arrow),
                        onPressed: () => v.isPlaying ? _c.pause() : _c.play(),
                      ),
                      Text(_fmt(v.position), style: const TextStyle(color: Colors.white70, fontSize: 12)),
                      const Spacer(),
                      Text(_fmt(v.duration), style: const TextStyle(color: Colors.white70, fontSize: 12)),
                      IconButton(
                        color: Colors.white,
                        icon: Icon(v.volume > 0 ? Icons.volume_up : Icons.volume_off),
                        onPressed: () => _c.setVolume(v.volume > 0 ? 0 : 1),
                      ),
                    ]),
                  ),
                ]),
              ),
            )
          : null,
    );
  }

  static String _fmt(Duration d) {
    final m = d.inMinutes.remainder(60).toString().padLeft(2, '0');
    final s = d.inSeconds.remainder(60).toString().padLeft(2, '0');
    return '$m:$s';
  }
}

/// Full-screen, zoomable image viewer.
class _FullImagePage extends StatelessWidget {
  final String url;
  const _FullImagePage({required this.url});
  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: Colors.black,
        appBar: AppBar(backgroundColor: Colors.black, foregroundColor: Colors.white, elevation: 0),
        body: Center(
          child: InteractiveViewer(
            minScale: 0.8,
            maxScale: 4,
            child: CachedNetworkImage(
              imageUrl: url,
              fit: BoxFit.contain,
              errorWidget: (_, __, ___) => const Icon(Icons.broken_image_outlined, color: Colors.white38, size: 48),
            ),
          ),
        ),
      );
}
