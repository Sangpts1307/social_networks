import { Body, Controller, Get, HttpException, Param, Patch, Post, Put, Query, Req, Res, UploadedFile, UseGuards, UseInterceptors } from '@nestjs/common';
import { FileInterceptor } from '@nestjs/platform-express';
import type { Request, Response } from 'express';
import { GatewayIdentityUser, IdentitySessionGuard } from './identity-session.guard';
import { LearningClient } from './learning-client';

type AuthenticatedRequest = Request & { identityUser: GatewayIdentityUser };

@Controller('v1/admin/learning')
@UseGuards(IdentitySessionGuard)
export class AdminLearningController {
  constructor(private readonly learning: LearningClient) {}

  @Get('taxonomy') taxonomy(@Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward('/admin/taxonomy', request, response); }
  @Post('topics') storeTopic(@Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward('/admin/topics', request, response, body); }
  @Patch('topics/:topicId') updateTopic(@Param('topicId') topicId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/topics/${encodeURIComponent(topicId)}`, request, response, body); }
  @Post('categories') storeCategory(@Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward('/admin/categories', request, response, body); }
  @Patch('categories/:categoryId') updateCategory(@Param('categoryId') categoryId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/categories/${encodeURIComponent(categoryId)}`, request, response, body); }
  @Get('questions') questions(@Query('q') q: string | undefined, @Query('status') status: string | undefined, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/questions${this.query({ q, status })}`, request, response); }
  @Post('questions/:questionId/moderation') moderateQuestion(@Param('questionId') questionId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/questions/${encodeURIComponent(questionId)}/moderation`, request, response, body); }
  @Get('documents') documents(@Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward('/admin/documents', request, response); }
  @Post('documents') storeDocument(@Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward('/admin/documents', request, response, body); }
  @Patch('documents/:documentId') updateDocument(@Param('documentId') documentId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/documents/${encodeURIComponent(documentId)}`, request, response, body); }
  @Post('documents/:documentId/files')
  @UseInterceptors(FileInterceptor('file', { limits: { fileSize: 50 * 1024 * 1024 } }))
  async uploadDocumentFile(@Param('documentId') documentId: string, @UploadedFile() file: { buffer: Buffer; originalname: string; mimetype: string } | undefined, @Req() request: AuthenticatedRequest, @Res() response: Response) {
    if (!request.identityUser) throw new HttpException({ message: 'Unauthenticated.' }, 401);
    if (!file) throw new HttpException({ message: 'A document file is required.' }, 422);
    const result = await this.learning.upload(`/admin/documents/${encodeURIComponent(documentId)}/files`, request.identityUser, file, request.header('x-request-id'));
    response.setHeader('Cache-Control', 'no-store');
    return response.status(result.status).json(result.data);
  }
  @Post('documents/:documentId/review') reviewDocument(@Param('documentId') documentId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/documents/${encodeURIComponent(documentId)}/review`, request, response, body); }
  @Get('documents/:documentId/access-rules') accessRules(@Param('documentId') documentId: string, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/documents/${encodeURIComponent(documentId)}/access-rules`, request, response); }
  @Put('documents/:documentId/access-rules') syncAccessRules(@Param('documentId') documentId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/documents/${encodeURIComponent(documentId)}/access-rules`, request, response, body); }
  @Post('documents/:documentId/lifecycle') transitionDocument(@Param('documentId') documentId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/documents/${encodeURIComponent(documentId)}/lifecycle`, request, response, body); }
  @Get('courses') courses(@Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward('/admin/courses', request, response); }
  @Post('courses') storeCourse(@Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward('/admin/courses', request, response, body); }
  @Post('courses/:courseId/lifecycle') transitionCourse(@Param('courseId') courseId: string, @Body() body: unknown, @Req() request: AuthenticatedRequest, @Res() response: Response) { return this.forward(`/admin/courses/${encodeURIComponent(courseId)}/lifecycle`, request, response, body); }

  private async forward(path: string, request: AuthenticatedRequest, response: Response, body?: unknown) {
    if (!request.identityUser) throw new HttpException({ message: 'Unauthenticated.' }, 401);
    const result = await this.learning.request(request.method as 'GET' | 'POST' | 'PATCH', path, request.identityUser, body, request.header('x-request-id'));
    response.setHeader('Cache-Control', 'no-store');
    return result.status === 204 ? response.status(204).end() : response.status(result.status).json(result.data);
  }

  private query(values: Record<string, string | undefined>): string {
    const params = new URLSearchParams();
    Object.entries(values).forEach(([key, value]) => { if (value) params.set(key, value); });
    return params.size ? `?${params.toString()}` : '';
  }
}
