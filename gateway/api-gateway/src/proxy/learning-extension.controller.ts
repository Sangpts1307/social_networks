import { Body, Controller, Delete, Get, HttpException, Param, Patch, Post, Put, Query, Req, Res, UseGuards } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentitySessionGuard, type GatewayIdentityUser } from './identity-session.guard';
import { LearningClient } from './learning-client';
type AuthRequest = Request & { identityUser?: GatewayIdentityUser };

@Controller('v1/learning')
@UseGuards(IdentitySessionGuard)
export class LearningExtensionController {
  constructor(private readonly client: LearningClient) {}
  @Get('rewards/wallet')
  operation0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/rewards/wallet`, 'GET', query, body, req, res);
  }
  @Get('rewards/ledger')
  operation1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/rewards/ledger`, 'GET', query, body, req, res);
  }
  @Patch('questions/:questionId')
  extended0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/questions/${encodeURIComponent(params.questionId)}`, 'PATCH', query, body, req, res);
  }
  @Delete('questions/:questionId')
  extended1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/questions/${encodeURIComponent(params.questionId)}`, 'DELETE', query, body, req, res);
  }
  @Put('questions/:questionId/follow')
  extended2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/questions/${encodeURIComponent(params.questionId)}/follow`, 'PUT', query, body, req, res);
  }
  @Delete('questions/:questionId/follow')
  extended3(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/questions/${encodeURIComponent(params.questionId)}/follow`, 'DELETE', query, body, req, res);
  }
  @Put('questions/:questionId/bookmark')
  extended4(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/questions/${encodeURIComponent(params.questionId)}/bookmark`, 'PUT', query, body, req, res);
  }
  @Delete('questions/:questionId/bookmark')
  extended5(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/questions/${encodeURIComponent(params.questionId)}/bookmark`, 'DELETE', query, body, req, res);
  }
  @Get('questions/:questionId/comments')
  extended6(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/questions/${encodeURIComponent(params.questionId)}/comments`, 'GET', query, body, req, res);
  }
  @Post('questions/:questionId/comments')
  extended7(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/questions/${encodeURIComponent(params.questionId)}/comments`, 'POST', query, body, req, res);
  }
  @Patch('qa-comments/:commentId')
  extended8(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/qa-comments/${encodeURIComponent(params.commentId)}`, 'PATCH', query, body, req, res);
  }
  @Delete('qa-comments/:commentId')
  extended9(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/qa-comments/${encodeURIComponent(params.commentId)}`, 'DELETE', query, body, req, res);
  }
  @Put('knowledge-items/:documentId/votes')
  settlement5(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/knowledge-items/${encodeURIComponent(params.documentId)}/votes`, 'PUT', query, body, req, res);
  }
  @Put('knowledge-items/:documentId/follow')
  settlement6(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/knowledge-items/${encodeURIComponent(params.documentId)}/follow`, 'PUT', query, body, req, res);
  }
  @Delete('knowledge-items/:documentId/follow')
  settlement7(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/knowledge-items/${encodeURIComponent(params.documentId)}/follow`, 'DELETE', query, body, req, res);
  }
  @Get('rewards/accruals')
  rewardList0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward('/rewards/accruals', 'GET', query, body, req, res);
  }
  @Get('rewards/leaderboard')
  rewardList1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward('/rewards/leaderboard', 'GET', query, body, req, res);
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
