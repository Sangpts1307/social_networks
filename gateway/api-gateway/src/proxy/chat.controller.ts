import { FileInterceptor } from '@nestjs/platform-express';
import { Body, Controller, Delete, Get, HttpException, Param, Patch, Post, Put, Query, Req, Res, UseGuards, UseInterceptors, UploadedFile } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentitySessionGuard, type GatewayIdentityUser } from './identity-session.guard';
import { ChatClient } from './chat-client';
type AuthRequest = Request & { identityUser?: GatewayIdentityUser };

@Controller('v1/chat')
@UseGuards(IdentitySessionGuard)
export class ChatController {
  constructor(private readonly client: ChatClient) {}
  @Get('conversations')
  operation0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations`, 'GET', query, body, req, res);
  }
  @Post('conversations')
  operation1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations`, 'POST', query, body, req, res);
  }
  @Get('conversations/:conversationId')
  operation2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations/${encodeURIComponent(params.conversationId)}`, 'GET', query, body, req, res);
  }
  @Get('conversations/:conversationId/messages')
  operation3(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations/${encodeURIComponent(params.conversationId)}/messages`, 'GET', query, body, req, res);
  }
  @Post('conversations/:conversationId/messages')
  operation4(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations/${encodeURIComponent(params.conversationId)}/messages`, 'POST', query, body, req, res);
  }
  @Put('conversations/:conversationId/read')
  operation5(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations/${encodeURIComponent(params.conversationId)}/read`, 'PUT', query, body, req, res);
  }
  @Post('conversations/:conversationId/members')
  operation6(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations/${encodeURIComponent(params.conversationId)}/members`, 'POST', query, body, req, res);
  }
  @Delete('conversations/:conversationId/members/:userId')
  operation7(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations/${encodeURIComponent(params.conversationId)}/members/${encodeURIComponent(params.userId)}`, 'DELETE', query, body, req, res);
  }
  @Post('conversations/:conversationId/attachments')
  @UseInterceptors(FileInterceptor('file', { limits: { fileSize: 20 * 1024 * 1024, files: 1 } }))
  async uploadAttachment(@Param('conversationId') id: string, @UploadedFile() file: { buffer: Buffer; originalname: string; mimetype: string } | undefined, @Req() req: AuthRequest, @Res() res: Response) {
    if (!req.identityUser) throw new HttpException({ message: 'Unauthenticated.' }, 401);
    if (req.identityUser.must_change_password) throw new HttpException({ message: 'Password change required.' }, 403);
    if (!file) throw new HttpException({ message: 'A file is required.' }, 422);
    const result = await this.client.upload(`/conversations/${encodeURIComponent(id)}/attachments`, req.identityUser, file, req.header('x-request-id'));
    res.setHeader('Cache-Control', 'no-store');
    return res.status(result.status).json(result.data);
  }
  @Get('conversations/:conversationId/attachments/:attachmentId/download')
  file0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations/${encodeURIComponent(params.conversationId)}/attachments/${encodeURIComponent(params.attachmentId)}/download`, 'GET', query, body, req, res);
  }
  @Patch('conversations/:conversationId')
  ownership0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations/${encodeURIComponent(params.conversationId)}`, 'PATCH', query, body, req, res);
  }
  @Put('conversations/:conversationId/owner')
  ownership1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/conversations/${encodeURIComponent(params.conversationId)}/owner`, 'PUT', query, body, req, res);
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
