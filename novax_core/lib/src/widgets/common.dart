import 'package:flutter/material.dart';
import '../theme.dart';

/// A small labelled stat tile (used on dashboards).
class StatCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color color;
  const StatCard({super.key, required this.label, required this.value, required this.icon, this.color = AppColors.primary});

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Icon(icon, color: color, size: 22),
          Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(value, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppColors.dark)),
            Text(label, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
          ]),
        ]),
      );
}

/// Coloured status pill (orders, requests…).
class StatusPill extends StatelessWidget {
  final String status;
  const StatusPill(this.status, {super.key});

  static const _map = {
    'pending': AppColors.warn, 'processing': AppColors.primary, 'shipped': Color(0xFF6366F1),
    'delivered': AppColors.accent, 'completed': AppColors.accent, 'cancelled': AppColors.danger,
    'open': AppColors.accent, 'paid': AppColors.accent,
  };

  @override
  Widget build(BuildContext context) {
    final c = _map[status] ?? AppColors.muted;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
      decoration: BoxDecoration(color: c.withValues(alpha: .12), borderRadius: BorderRadius.circular(20)),
      child: Text(status.isEmpty ? '' : status[0].toUpperCase() + status.substring(1),
          style: TextStyle(color: c, fontSize: 11.5, fontWeight: FontWeight.w700)),
    );
  }
}

/// Centered empty state.
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  const EmptyState({super.key, required this.icon, required this.title, this.subtitle});

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Icon(icon, size: 54, color: AppColors.muted),
            const SizedBox(height: 10),
            Text(title, style: const TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF475569))),
            if (subtitle != null) Padding(padding: const EdgeInsets.only(top: 4), child: Text(subtitle!, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.muted, fontSize: 13))),
          ]),
        ),
      );
}

/// Error state with a Retry button — used when a request fails, so failures are
/// visible instead of showing a misleading "empty" screen.
class ErrorRetry extends StatelessWidget {
  final String message;
  final Future<void> Function() onRetry;
  const ErrorRetry({super.key, required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) => ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          const SizedBox(height: 90),
          const Icon(Icons.cloud_off_rounded, size: 48, color: AppColors.muted),
          const SizedBox(height: 10),
          const Center(child: Text("Couldn't load data", style: TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF475569)))),
          Padding(
            padding: const EdgeInsets.fromLTRB(40, 4, 40, 0),
            child: Text(message, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
          ),
          const SizedBox(height: 14),
          Center(
            child: OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh, size: 18),
              label: const Text('Retry'),
            ),
          ),
        ],
      );
}
