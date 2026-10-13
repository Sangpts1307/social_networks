import { CanActivate, ExecutionContext, HttpException, Injectable } from '@nestjs/common';
import type { Request } from 'express';
import { IdentityClient } from './identity-client';

export interface GatewayIdentityUser {
  id: string;
  organization_id: string;
  email: string;
  display_name: string;
  must_change_password: boolean;
  permissions: string[];
}

type AuthenticatedRequest = Request & { identityUser?: GatewayIdentityUser };

@Injectable()
export class IdentitySessionGuard implements CanActivate {
  constructor(private readonly identity: IdentityClient) {}
  async canActivate(context: ExecutionContext): Promise<boolean> {
    const request = context.switchToHttp().getRequest<AuthenticatedRequest>();
    const match = /^Bearer ([a-f0-9]{64})$/i.exec(request.header('authorization') ?? '');
    if (!match) throw new HttpException({ message: 'Unauthenticated.' }, 401);
    const result = await this.identity.call('/auth/session', 'GET', match[1], undefined, request.header('x-request-id'));
    if (result.status !== 200) throw new HttpException(result.data, result.status);
    request.identityUser = result.data.user as GatewayIdentityUser;
    return true;
  }
}
