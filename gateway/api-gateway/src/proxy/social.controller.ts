import { Body, Controller, Delete, Get, HttpException, Param, Patch, Post, Put, Query, Req, Res, UseGuards, UseFilters } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentitySessionGuard, type GatewayIdentityUser } from './identity-session.guard';
import { SocialClient } from './social-client';
import { SocialExceptionFilter } from './social-exception.filter';

type AuthenticatedRequest = Request & { identityUser?: GatewayIdentityUser };

@Controller('v1/social')
@UseGuards(IdentitySessionGuard)
@UseFilters(SocialExceptionFilter)
export class SocialController {
  constructor(private readonly social: SocialClient) {}

  @Get('channels')
  channels(@Query() query: Record<string, unknown>, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/channels`, 'GET', undefined, query, request, response);
  }

  @Post('channels')
  createChannel(@Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/channels`, 'POST', body, {}, request, response);
  }

  @Put('channels/:channelId/membership')
  joinChannel(@Param('channelId') channelId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/channels/${encodeURIComponent(channelId)}/membership`, 'PUT', body, {}, request, response);
  }

  @Delete('channels/:channelId/membership')
  leaveChannel(@Param('channelId') channelId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/channels/${encodeURIComponent(channelId)}/membership`, 'DELETE', undefined, {}, request, response);
  }

  @Get('posts')
  posts(@Query() query: Record<string, unknown>, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts`, 'GET', undefined, query, request, response);
  }

  @Post('posts')
  createPost(@Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts`, 'POST', body, {}, request, response);
  }

  @Get('posts/:postId')
  post(@Param('postId') postId: string, @Query() query: Record<string, unknown>, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}`, 'GET', undefined, query, request, response);
  }

  @Patch('posts/:postId')
  updatePost(@Param('postId') postId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}`, 'PATCH', body, {}, request, response);
  }

  @Delete('posts/:postId')
  deletePost(@Param('postId') postId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}`, 'DELETE', undefined, {}, request, response);
  }

  @Get('posts/:postId/comments')
  comments(@Param('postId') postId: string, @Query() query: Record<string, unknown>, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}/comments`, 'GET', undefined, query, request, response);
  }

  @Post('posts/:postId/comments')
  createComment(@Param('postId') postId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}/comments`, 'POST', body, {}, request, response);
  }

  @Patch('comments/:commentId')
  updateComment(@Param('commentId') commentId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/comments/${encodeURIComponent(commentId)}`, 'PATCH', body, {}, request, response);
  }

  @Delete('comments/:commentId')
  deleteComment(@Param('commentId') commentId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/comments/${encodeURIComponent(commentId)}`, 'DELETE', undefined, {}, request, response);
  }

  @Put('posts/:postId/reaction')
  reaction(@Param('postId') postId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}/reaction`, 'PUT', body, {}, request, response);
  }

  @Delete('posts/:postId/reaction')
  removeReaction(@Param('postId') postId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}/reaction`, 'DELETE', undefined, {}, request, response);
  }

  @Put('posts/:postId/save')
  save(@Param('postId') postId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}/save`, 'PUT', body, {}, request, response);
  }

  @Delete('posts/:postId/save')
  unsave(@Param('postId') postId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}/save`, 'DELETE', undefined, {}, request, response);
  }

  @Post('posts/:postId/reports')
  report(@Param('postId') postId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/posts/${encodeURIComponent(postId)}/reports`, 'POST', body, {}, request, response);
  }

  @Get('admin/reports')
  reports(@Query() query: Record<string, unknown>, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/admin/reports`, 'GET', undefined, query, request, response);
  }

  @Post('admin/posts/:postId/moderation')
  moderate(@Param('postId') postId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/admin/posts/${encodeURIComponent(postId)}/moderation`, 'POST', body, {}, request, response);
  }

  private async forward(path: string, method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE', body: unknown, query: Record<string, unknown>, request: AuthenticatedRequest, response: Response) {
    const user = request.identityUser;
    if (!user) throw new HttpException({ message: 'Unauthenticated.', code: 'UNAUTHENTICATED' }, 401);
    if (user.must_change_password) throw new HttpException({ message: 'Password change required.', code: 'PASSWORD_CHANGE_REQUIRED' }, 403);
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(query)) {
      if (typeof value !== 'string') throw new HttpException({ message: 'Query parameters must be scalar strings.', code: 'VALIDATION_ERROR' }, 422);
      params.set(key, value);
    }
    const result = await this.social.request(method, path + (params.size ? `?${params}` : ''), user, body, request.header('x-request-id'), request.header('idempotency-key'));
    response.setHeader('Cache-Control', 'no-store');
    if (result.retryAfter) response.setHeader('Retry-After', result.retryAfter);
    if (result.rateLimit) response.setHeader('X-RateLimit-Limit', result.rateLimit);
    if (result.rateRemaining) response.setHeader('X-RateLimit-Remaining', result.rateRemaining);
    if (result.location) response.setHeader('Location', result.location);
    if (result.replayed) response.setHeader('Idempotency-Replayed', result.replayed);
    return result.status === 204 ? response.status(204).send() : response.status(result.status).json(result.data);
  }
}
