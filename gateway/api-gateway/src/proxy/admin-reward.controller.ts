import { Body, Controller, Delete, Get, HttpException, Param, Patch, Post, Put, Query, Req, Res, UseGuards } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentitySessionGuard, type GatewayIdentityUser } from './identity-session.guard';
import { LearningClient } from './learning-client';
type AuthRequest = Request & { identityUser?: GatewayIdentityUser };

@Controller('v1/admin/learning')
@UseGuards(IdentitySessionGuard)
export class AdminRewardController {
  constructor(private readonly client: LearningClient) {}
  @Get('reward-rules')
  operation2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/reward-rules`, 'GET', query, body, req, res);
  }
  @Post('reward-rules')
  operation3(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/reward-rules`, 'POST', query, body, req, res);
  }
  @Put('reward-rules/:ruleId')
  operation4(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/reward-rules/${encodeURIComponent(params.ruleId)}`, 'PUT', query, body, req, res);
  }
  @Get('reward-settlements')
  settlement0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/reward-settlements`, 'GET', query, body, req, res);
  }
  @Post('reward-settlements')
  settlement1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/reward-settlements`, 'POST', query, body, req, res);
  }
  @Get('reward-settlements/:settlementId')
  settlement2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/reward-settlements/${encodeURIComponent(params.settlementId)}`, 'GET', query, body, req, res);
  }
  @Post('reward-settlements/:settlementId/approve')
  settlement3(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/reward-settlements/${encodeURIComponent(params.settlementId)}/approve`, 'POST', query, body, req, res);
  }
  @Post('reward-settlements/:settlementId/cancel')
  settlement4(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/admin/reward-settlements/${encodeURIComponent(params.settlementId)}/cancel`, 'POST', query, body, req, res);
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
    const result = method === 'GET' ? await this.client.get(path, user, req.header('x-request-id')) : await this.client.request(method, path, user, body, req.header('x-request-id'));
    res.setHeader('Cache-Control', 'no-store');
    return result.status === 204 ? res.status(204).end() : res.status(result.status).json(result.data);
  }
}
