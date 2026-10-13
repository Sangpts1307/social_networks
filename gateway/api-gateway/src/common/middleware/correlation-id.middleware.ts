import { randomUUID } from 'node:crypto';
import type { NextFunction, Request, Response } from 'express';

export const correlationIdMiddleware = (request: Request, response: Response, next: NextFunction): void => {
  const requestId = request.header('x-request-id') ?? randomUUID();
  response.setHeader('x-request-id', requestId);
  request.headers['x-request-id'] = requestId;
  next();
};
