import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/dio_client.dart';
import '../models/knowledge_model.dart';

final learningServiceProvider = Provider<LearningService>((ref) {
  final dio = ref.watch(dioProvider);
  return LearningService(dio);
});

class LearningService {
  final Dio _dio;

  LearningService(this._dio);

  Future<List<KnowledgeItem>> getLibrary() async {
    final response = await _dio.get('/v1/learning/knowledge-items');
    final data = response.data as Map<String, dynamic>;
    final items = (data['data'] as List<dynamic>?)
            ?.map((e) => KnowledgeItem.fromJson(e as Map<String, dynamic>))
            .toList() ??
        [];
    return items;
  }
}
