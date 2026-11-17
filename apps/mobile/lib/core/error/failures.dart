/// Base Failure class representing an error that has been caught and handled.
abstract class Failure {
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? validationErrors;

  const Failure(this.message, {this.statusCode, this.validationErrors});

  @override
  String toString() => 'Failure(message: $message, statusCode: $statusCode)';
}

/// Server responded with an error (e.g. 400 Bad Request, 500 Internal Error)
class ServerFailure extends Failure {
  const ServerFailure(
    super.message, {
    super.statusCode,
    super.validationErrors,
  });
}

/// Validation failure (HTTP 422 Unprocessable Entity)
class ValidationFailure extends Failure {
  const ValidationFailure(
    super.message, {
    super.statusCode = 422,
    super.validationErrors,
  });
}

/// No internet connection, timeout, or DNS failure
class NetworkFailure extends Failure {
  const NetworkFailure(super.message, {super.statusCode});
}

/// Authentication failure (e.g. 401 Unauthorized / expired token)
class AuthFailure extends Failure {
  const AuthFailure(super.message, {super.statusCode = 401});
}

/// Unknown / unexpected failure
class UnexpectedFailure extends Failure {
  const UnexpectedFailure(super.message, {super.statusCode});
}
