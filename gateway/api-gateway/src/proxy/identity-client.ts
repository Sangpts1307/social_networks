import { Injectable, HttpException } from '@nestjs/common';
import { gatewayConfig } from '../config/gateway.config';

@Injectable()
export class IdentityClient {
  async call(path: string, method: string, token?: string, body?: unknown, requestId?: string) {
    const key = process.env.IDENTITY_GATEWAY_KEY ?? '';
    if (key.length < 32) throw new HttpException({ message: 'Identity service authentication is not configured.' }, 503);
    try {
      const response = await fetch(`${gatewayConfig().services.identity}/api/v1${path}`, {
        method,
        headers: {
          'x-identity-service-key': key, accept: 'application/json', 'content-type': 'application/json',
          ...(token ? { authorization: `Bearer ${token}` } : {}),
          ...(requestId ? { 'x-request-id': requestId } : {}),
        },
        body: body === undefined ? undefined : JSON.stringify(body),
        signal: AbortSignal.timeout(5000),
        redirect: 'error',
      });
      if (response.status >= 500) throw new HttpException({ message: 'Identity service unavailable.' }, 502);
      const data = response.status === 204 ? undefined : await response.json();
      return { status: response.status, data };
    } catch (error) {
      if (error instanceof HttpException) throw error;
      throw new HttpException({ message: 'Identity service unavailable.' }, 502);
    }
  }
}
