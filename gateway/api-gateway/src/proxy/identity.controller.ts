import { Controller, Get, Post, Req, Res, UseGuards } from '@nestjs/common';
import type { Request, Response } from 'express';
import { IdentityClient } from './identity-client';
import { IdentitySessionGuard } from './identity-session.guard';

@Controller('v1')
export class IdentityController {
  constructor(private readonly identity: IdentityClient) {}
  private async forward(path: string, request: Request, response: Response) {
    const token = /^Bearer ([a-f0-9]{64})$/i.exec(request.header('authorization') ?? '')?.[1];
    const result = await this.identity.call(path, request.method, token,
      request.method === 'GET' ? undefined : request.body, request.header('x-request-id'));
    response.setHeader('Cache-Control', 'no-store');
    return result.status === 204 ? response.status(204).end() : response.status(result.status).json(result.data);
  }
  @Post('auth/login')
  login(@Req() req: Request, @Res() res: Response) { return this.forward('/auth/login', req, res); }
  @Get('auth/session') @UseGuards(IdentitySessionGuard)
  session(@Req() req: Request, @Res() res: Response) { return this.forward('/auth/session', req, res); }
  @Get('me') @UseGuards(IdentitySessionGuard)
  me(@Req() req: Request, @Res() res: Response) { return this.forward('/me', req, res); }
  @Post('auth/logout') @UseGuards(IdentitySessionGuard)
  logout(@Req() req: Request, @Res() res: Response) { return this.forward('/auth/logout', req, res); }
  @Post('auth/logout-all') @UseGuards(IdentitySessionGuard)
  logoutAll(@Req() req: Request, @Res() res: Response) { return this.forward('/auth/logout-all', req, res); }
  @Post('auth/password') @UseGuards(IdentitySessionGuard)
  password(@Req() req: Request, @Res() res: Response) { return this.forward('/auth/password', req, res); }
}
