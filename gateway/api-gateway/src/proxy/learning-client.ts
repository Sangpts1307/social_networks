import { HttpException, Injectable } from '@nestjs/common';
import { gatewayConfig } from '../config/gateway.config';
import type { GatewayIdentityUser } from './identity-session.guard';

@Injectable()
export class LearningClient {
  async get(path: string, user: GatewayIdentityUser, requestId?: string) {
    return this.request('GET', path, user, undefined, requestId);
  }

  async upload(path: string, user: GatewayIdentityUser, file: { buffer: Buffer; originalname: string; mimetype: string }, requestId?: string) {
    const form = new FormData();
    // Copy out of Node's Buffer type so the Web Blob implementation only sees
    // a standard ArrayBuffer-backed byte view.
    form.append('file', new Blob([Uint8Array.from(file.buffer)], { type: file.mimetype }), file.originalname);

    return this.formRequest('POST', path, user, form, requestId);
  }

  async request(method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE', path: string, user: GatewayIdentityUser, body: unknown, requestId?: string) {
    const key = process.env.LEARNING_GATEWAY_KEY ?? '';
    if (key.length < 32) throw new HttpException({ message: 'Learning service authentication is not configured.' }, 503);
    try {
      const response = await fetch(`${gatewayConfig().services.learning}/api/v1${path}`, {
        method,
        headers: {
          accept: 'application/json',
          ...(body !== undefined ? { 'content-type': 'application/json' } : {}),
          'x-learning-gateway-key': key,
          'x-authenticated-user-id': user.id,
          'x-authenticated-organization-id': user.organization_id,
          'x-authenticated-permissions': user.permissions.join(' '),
          'x-authorization-context': JSON.stringify(user.authorization ?? { version: 1, grants: [], org_unit_ids: [] }),
          ...(requestId ? { 'x-request-id': requestId } : {}),
        },
        ...(body !== undefined ? { body: JSON.stringify(body) } : {}),
        signal: AbortSignal.timeout(5000),
        redirect: 'error',
      });
      if (response.status >= 500) throw new HttpException({ message: 'Learning service unavailable.' }, 502);
      const data = response.status === 204 ? null : await response.json();
      return { status: response.status, data };
    } catch (error) {
      if (error instanceof HttpException) throw error;
      throw new HttpException({ message: 'Learning service unavailable.' }, 502);
    }
  }

  private async formRequest(method: 'POST', path: string, user: GatewayIdentityUser, form: FormData, requestId?: string) {
    const key = process.env.LEARNING_GATEWAY_KEY ?? '';
    if (key.length < 32) throw new HttpException({ message: 'Learning service authentication is not configured.' }, 503);
    try {
      const response = await fetch(`${gatewayConfig().services.learning}/api/v1${path}`, {
        method,
        headers: {
          accept: 'application/json',
          'x-learning-gateway-key': key,
          'x-authenticated-user-id': user.id,
          'x-authenticated-organization-id': user.organization_id,
          'x-authenticated-permissions': user.permissions.join(' '),
          'x-authorization-context': JSON.stringify(user.authorization ?? { version: 1, grants: [], org_unit_ids: [] }),
          ...(requestId ? { 'x-request-id': requestId } : {}),
        },
        body: form,
        signal: AbortSignal.timeout(30000),
        redirect: 'error',
      });
      if (response.status >= 500) throw new HttpException({ message: 'Learning service unavailable.' }, 502);
      const data = response.status === 204 ? null : await response.json();
      return { status: response.status, data };
    } catch (error) {
      if (error instanceof HttpException) throw error;
      throw new HttpException({ message: 'Learning service unavailable.' }, 502);
    }
  }
}
