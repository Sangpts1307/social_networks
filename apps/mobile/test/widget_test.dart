import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:openhello_mobile/app.dart';

void main() {
  testWidgets('OpenhelloApp smoke test boots cleanly', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: OpenhelloApp()));
    await tester.pump();
    expect(find.byType(OpenhelloApp), findsOneWidget);
  });
}
