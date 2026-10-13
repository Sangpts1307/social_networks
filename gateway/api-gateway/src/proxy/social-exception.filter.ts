import { ArgumentsHost, Catch, ExceptionFilter, HttpException } from '@nestjs/common';
import type { Response } from 'express';

@Catch()
export class SocialExceptionFilter implements ExceptionFilter {
  catch(error: unknown, host: ArgumentsHost) {
    const response = host.switchToHttp().getResponse<Response>();
    const status = error instanceof HttpException ? error.getStatus() : 500;
    const payload = error instanceof HttpException ? error.getResponse() : {};
    const data = typeof payload === 'object' && payload !== null ? payload as Record<string, unknown> : {};
    const codes: Record<number, string> = { 401: 'UNAUTHENTICATED', 403: 'FORBIDDEN', 404: 'NOT_FOUND', 409: 'CONFLICT', 422: 'VALIDATION_ERROR', 502: 'UPSTREAM_UNAVAILABLE', 503: 'SERVICE_UNAVAILABLE' };
    response.setHeader('Cache-Control', 'no-store');
    response.status(status).json({ message: data.message ?? 'Social service unavailable.', code: data.code ?? codes[status] ?? 'INTERNAL_ERROR', errors: data.errors ?? {} });
  }
}
