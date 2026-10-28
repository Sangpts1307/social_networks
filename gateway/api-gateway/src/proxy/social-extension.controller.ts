import { FileInterceptor } from '@nestjs/platform-express';
import { Body, Controller, Delete, Get, HttpException, Param, Patch, Post, Put, Query, Req, Res, UseGuards, UseInterceptors, UploadedFile } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentitySessionGuard, type GatewayIdentityUser } from './identity-session.guard';
import { SocialClient } from './social-client';
type AuthRequest = Request & { identityUser?: GatewayIdentityUser };

@Controller('v1/social')
@UseGuards(IdentitySessionGuard)
export class SocialExtensionController {
  constructor(private readonly client: SocialClient) {}
  @Put('users/:userId/follow')
  operation0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/users/${encodeURIComponent(params.userId)}/follow`, 'PUT', query, body, req, res);
  }
  @Delete('users/:userId/follow')
  operation1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/users/${encodeURIComponent(params.userId)}/follow`, 'DELETE', query, body, req, res);
  }
  @Get('following')
  operation2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/following`, 'GET', query, body, req, res);
  }
  @Get('follow-stats')
  followStats(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward('/follow-stats', 'GET', query, body, req, res);
  }
  @Put('posts/:postId/pin')
  operation3(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/posts/${encodeURIComponent(params.postId)}/pin`, 'PUT', query, body, req, res);
  }
  @Delete('posts/:postId/pin')
  operation4(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/posts/${encodeURIComponent(params.postId)}/pin`, 'DELETE', query, body, req, res);
  }
  @Get('posts/:postId/event')
  operation5(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/posts/${encodeURIComponent(params.postId)}/event`, 'GET', query, body, req, res);
  }
  @Put('posts/:postId/rsvp')
  operation6(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/posts/${encodeURIComponent(params.postId)}/rsvp`, 'PUT', query, body, req, res);
  }
  @Delete('posts/:postId/rsvp')
  operation7(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/posts/${encodeURIComponent(params.postId)}/rsvp`, 'DELETE', query, body, req, res);
  }
  @Post('posts/:postId/attachments')
  @UseInterceptors(FileInterceptor('file', { limits: { fileSize: 20 * 1024 * 1024, files: 1 } }))
  async uploadAttachment(@Param('postId') id: string, @UploadedFile() file: { buffer: Buffer; originalname: string; mimetype: string } | undefined, @Req() req: AuthRequest, @Res() res: Response) {
    if (!req.identityUser) throw new HttpException({ message: 'Unauthenticated.' }, 401);
    if (req.identityUser.must_change_password) throw new HttpException({ message: 'Password change required.' }, 403);
    if (!file) throw new HttpException({ message: 'A file is required.' }, 422);
    const result = await this.client.upload(`/posts/${encodeURIComponent(id)}/attachments`, req.identityUser, file, req.header('x-request-id'));
    res.setHeader('Cache-Control', 'no-store');
    return res.status(result.status).json(result.data);
  }
  @Get('posts/:postId/attachments/:attachmentId/download')
  file0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/posts/${encodeURIComponent(params.postId)}/attachments/${encodeURIComponent(params.attachmentId)}/download`, 'GET', query, body, req, res);
  }
  @Get('posts/:postId/attachments')
  file1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/posts/${encodeURIComponent(params.postId)}/attachments`, 'GET', query, body, req, res);
  }
  @Delete('posts/:postId/attachments/:attachmentId')
  file2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/posts/${encodeURIComponent(params.postId)}/attachments/${encodeURIComponent(params.attachmentId)}`, 'DELETE', query, body, req, res);
  }
  @Patch('channels/:channelId')
  channelAdmin0(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/channels/${encodeURIComponent(params.channelId)}`, 'PATCH', query, body, req, res);
  }
  @Get('channels/:channelId/members')
  channelAdmin1(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/channels/${encodeURIComponent(params.channelId)}/members`, 'GET', query, body, req, res);
  }
  @Put('channels/:channelId/members/:userId')
  channelAdmin2(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/channels/${encodeURIComponent(params.channelId)}/members/${encodeURIComponent(params.userId)}`, 'PUT', query, body, req, res);
  }
  @Delete('channels/:channelId/members/:userId')
  channelAdmin3(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/channels/${encodeURIComponent(params.channelId)}/members/${encodeURIComponent(params.userId)}`, 'DELETE', query, body, req, res);
  }
  @Put('posts/:postId/acknowledgement')
  channelAdmin4(@Param() params: Record<string, string>, @Query() query: Record<string, unknown>, @Body() body: unknown, @Req() req: AuthRequest, @Res() res: Response) {
    return this.forward(`/posts/${encodeURIComponent(params.postId)}/acknowledgement`, 'PUT', query, body, req, res);
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
