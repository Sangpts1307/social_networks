import { Body, Controller, Delete, Get, HttpException, Param, Patch, Post, Put, Query, Req, Res, UseGuards } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentitySessionGuard, type GatewayIdentityUser } from './identity-session.guard';
import { NotificationClient } from './notification-client';
type AuthRequest = Request & { identityUser?: GatewayIdentityUser };

@Controller('v1')
@UseGuards(IdentitySessionGuard)
export class NotificationController {
  constructor(private readonly client: NotificationClient) {}
  @Get('notifications')
  operation0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/notifications`, 'GET', query, body, req, res);
  }
  @Get('notifications/unread-count')
  operation1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/notifications/unread-count`, 'GET', query, body, req, res);
  }
  @Put('notifications/read-all')
  operation2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/notifications/read-all`, 'PUT', query, body, req, res);
  }
  @Put('notifications/:notificationId/read')
  operation3(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/notifications/${encodeURIComponent(params.notificationId)}/read`, 'PUT', query, body, req, res);
  }
  @Get('notification-preferences')
  operation4(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/notification-preferences`, 'GET', query, body, req, res);
  }
  @Put('notification-preferences/:type')
  operation5(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/notification-preferences/${encodeURIComponent(params.type)}`, 'PUT', query, body, req, res);
  }
  @Get('devices')
  operation6(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/devices`, 'GET', query, body, req, res);
  }
  @Post('devices')
  operation7(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/devices`, 'POST', query, body, req, res);
  }
  @Delete('devices/:deviceId')
  operation8(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/devices/${encodeURIComponent(params.deviceId)}`, 'DELETE', query, body, req, res);
  }
  @Get('admin/broadcasts')
  broadcast0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/broadcasts`, 'GET', query, body, req, res);
  }
  @Post('admin/broadcasts')
  broadcast1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/broadcasts`, 'POST', query, body, req, res);
  }
  @Get('admin/broadcasts/:broadcastId')
  broadcast2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/broadcasts/${encodeURIComponent(params.broadcastId)}`, 'GET', query, body, req, res);
  }
  @Post('admin/broadcasts/:broadcastId/publish')
  broadcast3(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/broadcasts/${encodeURIComponent(params.broadcastId)}/publish`, 'POST', query, body, req, res);
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
    const result = await this.client.request(method, path, user, method === 'GET' || method === 'DELETE' ? undefined : body, req.header('x-request-id'), req.header('idempotency-key'));
    res.setHeader('Cache-Control', 'no-store');
    return result.status === 204 ? res.status(204).end() : res.status(result.status).json(result.data);
  }
}
