// Smoke tests for the NovaxSuites Marketplace app.
//
// The real app entrypoint (`main`) performs async plugin initialisation
// (push, shared_preferences) before `runApp`, so these tests exercise the
// shared building blocks directly instead of booting the full app.

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:novax_core/novax_core.dart';

void main() {
  testWidgets('App theme builds and renders a scaffold', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: buildTheme(),
      home: const Scaffold(body: Center(child: Text('NovaxSuites'))),
    ));

    expect(find.text('NovaxSuites'), findsOneWidget);
  });

  test('Fmt.money formats value with a currency prefix', () {
    expect(Fmt.money(12.5, 'USD'), 'USD 12.50');
    expect(Fmt.money(3), '3.00');
  });
}
