import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/error/failures.dart';
import '../../core/utils/result.dart';
import '../models/knowledge_model.dart';
import '../services/learning_service.dart';

final learningRepositoryProvider = Provider<LearningRepository>((ref) {
  final service = ref.watch(learningServiceProvider);
  return LearningRepository(service);
});

class LearningRepository {
  final LearningService _service;

  LearningRepository(this._service);

  Future<Result<List<KnowledgeItem>>> getLibrary() async {
    try {
      final items = await _service.getLibrary();
      return Result.success(items);
    } on DioException catch (e) {
      return Result.failure(ServerFailure(e.message ?? 'Không thể tải thư viện tri thức.'));
    } catch (e) {
      return Result.failure(UnexpectedFailure(e.toString()));
    }
  }
}
