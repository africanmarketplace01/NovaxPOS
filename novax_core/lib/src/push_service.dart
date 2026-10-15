import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:onesignal_flutter/onesignal_flutter.dart';

/// Centralized OneSignal wrapper for both apps.
///
/// Per the OneSignal integration guidance, ALL OneSignal SDK calls are isolated
/// in this single class — no other file talks to the SDK directly. It's a no-op
/// until [init] is called with a non-empty App ID, so the apps still run when
/// push isn't configured.
class PushService {
  static String _appId = '';
  static bool _welcomeShown = false;
  static bool get enabled => _appId.isNotEmpty;

  /// Initialize the SDK. Call once in `main()` before `runApp`.
  static Future<void> init(String appId) async {
    _appId = appId;
    if (!enabled) return;
    // Verbose logs while developing; silent in release builds.
    OneSignal.Debug.setLogLevel(kDebugMode ? OSLogLevel.verbose : OSLogLevel.none);
    OneSignal.initialize(appId);
    await OneSignal.Notifications.requestPermission(true);
  }

  /// Bind this device to a stable external id + tags so the backend can target it.
  static Future<void> identify(String externalId, {Map<String, String> tags = const {}}) async {
    if (!enabled) return;
    try {
      await OneSignal.login(externalId);
      if (tags.isNotEmpty) OneSignal.User.addTags(tags);
    } catch (_) {}
  }

  static Future<String?> playerId() async {
    if (!enabled) return null;
    try {
      return OneSignal.User.pushSubscription.id;
    } catch (_) {
      return null;
    }
  }

  static Future<void> logout() async {
    if (!enabled) return;
    try { await OneSignal.logout(); } catch (_) {}
  }

  /// Verification helper from the OneSignal integration guide: when the device
  /// first receives a push subscription id, show a one-time dialog confirming
  /// the integration and let the user trigger their first journey (in-app msg).
  ///
  /// Wire this once from the app's root widget. Safe to remove after you've
  /// confirmed the integration works.
  static void setupWelcomeDialog(BuildContext context) {
    if (!enabled) return;
    OneSignal.User.pushSubscription.addObserver((state) {
      final previousId = state.previous.id;
      final currentId = state.current.id;
      final becameSubscribed =
          (previousId == null || previousId.isEmpty) && currentId != null && currentId.isNotEmpty;
      if (!becameSubscribed || _welcomeShown) return;
      _welcomeShown = true;
      if (!context.mounted) return;
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          title: const Text('Your OneSignal integration is complete!'),
          content: const Text('Click the button below to trigger your first journey via an in-app message.'),
          actions: [
            TextButton(
              onPressed: () {
                OneSignal.InAppMessages.addTrigger('ai_implementation_campaign_email_journey', 'true');
                Navigator.pop(ctx);
              },
              child: const Text('Trigger your first journey'),
            ),
          ],
        ),
      );
    });
  }
}
