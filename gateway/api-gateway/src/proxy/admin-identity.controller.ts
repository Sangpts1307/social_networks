import { Body, Controller, Delete, Get, HttpException, Param, Patch, Post, Query, Req, Res, UseGuards } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentityClient } from './identity-client';
import { IdentitySessionGuard } from './identity-session.guard';

@Controller('v1/admin')
@UseGuards(IdentitySessionGuard)
export class AdminIdentityController {
  constructor(private readonly identity: IdentityClient) {}

  @Get('dashboard/identity')
  dashboard(@Req() request: Request, @Res() response: Response) { return this.forward('/admin/dashboard/identity', request, response); }

  @Get('users')
  users(@Query('q') query: string | undefined, @Query('status') status: string | undefined, @Query('page') page: string | undefined, @Query('per_page') perPage: string | undefined, @Req() request: Request, @Res() response: Response) {
    return this.forward(`/admin/users${this.query({ q: query, status, page, per_page: perPage })}`, request, response);
  }

  @Get('users/:userId')
  user(@Param('userId') userId: string, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/users/${encodeURIComponent(userId)}`, request, response); }

  @Patch('users/:userId/status')
  updateUserStatus(@Param('userId') userId: string, @Body() body: unknown, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/users/${encodeURIComponent(userId)}/status`, request, response, body); }

  @Post('users/:userId/unlock')
  unlockUser(@Param('userId') userId: string, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/users/${encodeURIComponent(userId)}/unlock`, request, response, {}); }

  @Post('users/:userId/force-password-change')
  forcePasswordChange(@Param('userId') userId: string, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/users/${encodeURIComponent(userId)}/force-password-change`, request, response, {}); }

  @Get('org-units')
  orgUnits(@Req() request: Request, @Res() response: Response) { return this.forward('/admin/org-units', request, response); }

  @Post('org-units')
  storeOrgUnit(@Body() body: unknown, @Req() request: Request, @Res() response: Response) { return this.forward('/admin/org-units', request, response, body); }

  @Patch('org-units/:orgUnitId')
  updateOrgUnit(@Param('orgUnitId') orgUnitId: string, @Body() body: unknown, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/org-units/${encodeURIComponent(orgUnitId)}`, request, response, body); }

  @Patch('org-units/:orgUnitId/manager')
  updateOrgUnitManager(@Param('orgUnitId') orgUnitId: string, @Body() body: unknown, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/org-units/${encodeURIComponent(orgUnitId)}/manager`, request, response, body); }

  @Get('roles')
  roles(@Req() request: Request, @Res() response: Response) { return this.forward('/admin/roles', request, response); }

  @Get('permissions')
  permissions(@Req() request: Request, @Res() response: Response) { return this.forward('/admin/permissions', request, response); }

  @Patch('roles/:roleId/permissions')
  syncRolePermissions(@Param('roleId') roleId: string, @Body() body: unknown, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/roles/${encodeURIComponent(roleId)}/permissions`, request, response, body); }

  @Post('users/:userId/role-assignments')
  assignRole(@Param('userId') userId: string, @Body() body: unknown, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/users/${encodeURIComponent(userId)}/role-assignments`, request, response, body); }

  @Delete('users/:userId/role-assignments/:assignmentId')
  removeRoleAssignment(@Param('userId') userId: string, @Param('assignmentId') assignmentId: string, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/users/${encodeURIComponent(userId)}/role-assignments/${encodeURIComponent(assignmentId)}`, request, response); }

  @Get('audit-logs')
  auditLogs(@Query('page') page: string | undefined, @Query('per_page') perPage: string | undefined, @Req() request: Request, @Res() response: Response) { return this.forward(`/admin/audit-logs${this.query({ page, per_page: perPage })}`, request, response); }

  private async forward(path: string, request: Request, response: Response, body?: unknown) {
    const token = /^Bearer ([a-f0-9]{64})$/i.exec(request.header('authorization') ?? '')?.[1];
    if (!token) throw new HttpException({ message: 'Unauthenticated.' }, 401);
    const result = await this.identity.call(path, request.method, token, body, request.header('x-request-id'));
    response.setHeader('Cache-Control', 'no-store');
    return result.status === 204 ? response.status(204).end() : response.status(result.status).json(result.data);
  }

  private query(values: Record<string, string | undefined>): string {
    const params = new URLSearchParams();
    Object.entries(values).forEach(([key, value]) => { if (value) params.set(key, value); });
    return params.size ? `?${params.toString()}` : '';
  }
}
