import '../error/failures.dart';

/// Generic Result type representing either a [Success] or a [FailureResult].
sealed class Result<T> {
  const Result();

  const factory Result.success(T data) = Success<T>;
  const factory Result.failure(Failure failure) = FailureResult<T>;

  bool get isSuccess => this is Success<T>;
  bool get isFailure => this is FailureResult<T>;

  T? get dataOrNull => switch (this) {
    Success(data: final d) => d,
    FailureResult() => null,
  };

  Failure? get failureOrNull => switch (this) {
    Success() => null,
    FailureResult(failure: final f) => f,
  };

  R when<R>({
    required R Function(T data) success,
    required R Function(Failure failure) failure,
  }) {
    return switch (this) {
      Success(data: final d) => success(d),
      FailureResult(failure: final f) => failure(f),
    };
  }
}

final class Success<T> extends Result<T> {
  final T data;
  const Success(this.data);
}

final class FailureResult<T> extends Result<T> {
  final Failure failure;
  const FailureResult(this.failure);
}
