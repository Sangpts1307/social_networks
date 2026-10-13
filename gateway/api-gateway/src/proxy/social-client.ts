import { HttpException, Injectable } from '@nestjs/common';
import { gatewayConfig } from '../config/gateway.config';
import type { GatewayIdentityUser } from './identity-session.guard';

@Injectable()
export class SocialClient {
  async request(method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE', path: string, user: GatewayIdentityUser, body: unknown, requestId?: string, idempotencyKey?: string) {
    const key = process.env.SOCIAL_GATEWAY_KEY ?? '';
    if (key.length < 32) throw new HttpException({ message: 'Social service authentication is not configured.' }, 503);
    try {
      const response = await fetch(`${gatewayConfig().services.social}/api/v1${path}`, {
        method,
        headers: {
          accept: 'application/json',
          ...(body !== undefined ? { 'content-type': 'application/json' } : {}),
          'x-social-gateway-key': key,
          'x-authenticated-user-id': user.id,
          'x-authenticated-organization-id': user.organization_id,
          'x-authenticated-permissions': user.permissions.join(' '),
          ...(requestId ? { 'x-request-id': requestId } : {}),
          ...(idempotencyKey ? { 'idempotency-key': idempotencyKey } : {}),
        },
        ...(body !== undefined ? { body: JSON.stringify(body) } : {}),
        signal: AbortSignal.timeout(5000),
        redirect: 'error',
      });
      if (response.status >= 500) throw new HttpException({ message: 'Social service unavailable.' }, 502);
      const data = response.status === 204 ? null : await response.json();
      return { status: response.status, data, location: response.headers.get('location'), replayed: response.headers.get('idempotency-replayed'), retryAfter: response.headers.get('retry-after'), rateLimit: response.headers.get('x-ratelimit-limit'), rateRemaining: response.headers.get('x-ratelimit-remaining') };
    } catch (error) {
      if (error instanceof HttpException) throw error;
      throw new HttpException({ message: 'Social service unavailable.' }, 502);
    }
  }

}
