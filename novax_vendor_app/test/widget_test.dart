// Smoke tests for the NovaxSuites Vendor app.
//
// `main` performs async plugin initialisation before `runApp`, so these tests
// exercise the shared building blocks directly rather than booting the app.

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:novax_core/novax_core.dart';

void main() {
  testWidgets('Vendor theme builds and renders a scaffold', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: buildTheme(),
      home: const Scaffold(body: Center(child: Text('Novax Vendor'))),
    ));

    expect(find.text('Novax Vendor'), findsOneWidget);
  });

  test('Fmt.money formats value with a currency prefix', () {
    expect(Fmt.money(9.9, 'USD'), 'USD 9.90');
  });
}
