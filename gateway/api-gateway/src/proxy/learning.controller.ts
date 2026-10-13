import { Body, Controller, Delete, Get, HttpException, Param, Patch, Post, Put, Query, Req, Res, UseGuards } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentitySessionGuard, type GatewayIdentityUser } from './identity-session.guard';
import { LearningClient } from './learning-client';

type AuthenticatedRequest = Request & { identityUser?: GatewayIdentityUser };

@Controller('v1/learning')
@UseGuards(IdentitySessionGuard)
export class LearningController {
  constructor(private readonly learning: LearningClient) {}

  @Get('topics')
  topics(@Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward('/knowledge/topics', request, response);
  }

  @Get('knowledge-items')
  documents(@Query('topic_id') topicId: string | undefined, @Query('q') query: string | undefined, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    const params = new URLSearchParams();
    if (topicId) params.set('topic_id', topicId);
    if (query) params.set('q', query);
    return this.forward(`/knowledge-items${params.size ? `?${params.toString()}` : ''}`, request, response);
  }

  @Get('knowledge-items/:documentId')
  document(@Param('documentId') documentId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/knowledge-items/${encodeURIComponent(documentId)}`, request, response);
  }

  @Get('knowledge-items/:documentId/download')
  downloadDocument(@Param('documentId') documentId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/knowledge-items/${encodeURIComponent(documentId)}/download`, request, response);
  }

  @Get('courses')
  courses(@Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward('/courses', request, response); }

  @Get('questions')
  questions(@Query('topic_id') topicId: string | undefined, @Query('category_id') categoryId: string | undefined, @Query('q') query: string | undefined, @Query('cursor') cursor: string | undefined, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    const params = new URLSearchParams();
    if (topicId) params.set('topic_id', topicId);
    if (categoryId) params.set('category_id', categoryId);
    if (query) params.set('q', query);
    if (cursor) params.set('cursor', cursor);
    return this.forward(`/questions${params.size ? `?${params.toString()}` : ''}`, request, response);
  }

  @Post('questions')
  createQuestion(@Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward('/questions', request, response, 'POST', body);
  }

  @Get('questions/:questionId')
  question(@Param('questionId') questionId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/questions/${encodeURIComponent(questionId)}`, request, response);
  }

  @Post('questions/:questionId/answers')
  createAnswer(@Param('questionId') questionId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/questions/${encodeURIComponent(questionId)}/answers`, request, response, 'POST', body);
  }

  @Patch('answers/:answerId')
  updateAnswer(@Param('answerId') answerId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/answers/${encodeURIComponent(answerId)}`, request, response, 'PATCH', body);
  }

  @Delete('answers/:answerId')
  deleteAnswer(@Param('answerId') answerId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/answers/${encodeURIComponent(answerId)}`, request, response, 'DELETE');
  }

  @Put('questions/:questionId/votes')
  voteQuestion(@Param('questionId') questionId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/questions/${encodeURIComponent(questionId)}/votes`, request, response, 'PUT', body);
  }

  @Put('answers/:answerId/votes')
  voteAnswer(@Param('answerId') answerId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    return this.forward(`/answers/${encodeURIComponent(answerId)}/votes`, request, response, 'PUT', body);
  }

  private async forward(path: string, request: AuthenticatedRequest, response: Response, method?: 'POST' | 'PATCH' | 'PUT' | 'DELETE', body?: unknown) {
    const user = request.identityUser;
    if (! user) throw new HttpException({ message: 'Unauthenticated.' }, 401);
    if (user.must_change_password) {
      throw new HttpException({ message: 'Password change required.', code: 'PASSWORD_CHANGE_REQUIRED' }, 403);
    }
    const result = method
      ? await this.learning.request(method, path, user, body, request.header('x-request-id'))
      : await this.learning.get(path, user, request.header('x-request-id'));
    response.setHeader('Cache-Control', 'no-store');
    return result.status === 204 ? response.status(204).send() : response.status(result.status).json(result.data);
  }
}
