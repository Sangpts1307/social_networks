import { Body, Controller, Delete, Get, HttpException, Param, Patch, Post, Put, Query, Req, Res, UseGuards } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentitySessionGuard, type GatewayIdentityUser } from './identity-session.guard';
import { IdentityClient } from './identity-client';
type AuthRequest = Request & { identityUser?: GatewayIdentityUser };

@Controller('v1')
@UseGuards(IdentitySessionGuard)
export class IdentityExtensionController {
  constructor(private readonly client: IdentityClient) {}
  @Get('me/profile')
  operation0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/me/profile`, 'GET', query, body, req, res);
  }
  @Patch('me/profile')
  operation1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/me/profile`, 'PATCH', query, body, req, res);
  }
  @Get('directory/users')
  operation2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/directory/users`, 'GET', query, body, req, res);
  }
  @Get('directory/users/:userId')
  operation3(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/directory/users/${encodeURIComponent(params.userId)}`, 'GET', query, body, req, res);
  }
  @Post('admin/users')
  operation4(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/users`, 'POST', query, body, req, res);
  }
  @Patch('admin/users/:userId')
  operation5(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/users/${encodeURIComponent(params.userId)}`, 'PATCH', query, body, req, res);
  }
  @Post('admin/users/:userId/reset-password')
  operation6(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/users/${encodeURIComponent(params.userId)}/reset-password`, 'POST', query, body, req, res);
  }
  private async forward(path: string, method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE', query: Record<string, unknown>, body: unknown, req: AuthRequest, res: Response) {
    const user = req.identityUser;
    if (!user) throw new HttpException({ message: 'Unauthenticated.' }, 401);
    if (user.must_change_password) throw new HttpException({ message: 'Password change required.', code: 'PASSWORD_CHANGE_REQUIRED' }, 403);
    const search = new URLSearchParams();
    for (const [key, value] of Object.entries(query)) {
      if (typeof value !== 'string') throw new HttpException({ message: 'Query parameters must be strings.' }, 422);
      search.set(key, value);
    }
    path += search.size ? `?${search}` : '';
    const token = /^Bearer ([a-f0-9]{64})$/i.exec(req.header('authorization') ?? '')?.[1];
    const result = await this.client.call(path, method, token, method === 'GET' ? undefined : body, req.header('x-request-id'));
    res.setHeader('Cache-Control', 'no-store');
    return result.status === 204 ? res.status(204).end() : res.status(result.status).json(result.data);
  }
}
