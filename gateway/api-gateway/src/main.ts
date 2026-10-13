import { existsSync } from 'node:fs';
import { loadEnvFile } from 'node:process';
import { ValidationPipe } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import type { NestExpressApplication } from '@nestjs/platform-express';
import { AppModule } from './app.module';
import { correlationIdMiddleware } from './common/middleware/correlation-id.middleware';
import { gatewayConfig } from './config/gateway.config';

async function bootstrap(): Promise<void> {
  // Native Node loader preserves environment variables supplied by the caller.
  if (existsSync('.env')) loadEnvFile('.env');
  const config = gatewayConfig();
  const app = await NestFactory.create<NestExpressApplication>(AppModule, { cors: false });

  // 50,000 Unicode characters can exceed Express's default 100 KB JSON limit.
  app.useBodyParser('json', { limit: '1mb' });
  app.use(correlationIdMiddleware);
  app.enableCors({
    origin: config.allowedOrigins,
    credentials: true,
    exposedHeaders: ['X-Request-Id', 'Location', 'Idempotency-Replayed', 'Retry-After', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'],
  });
  app.setGlobalPrefix('api');
  app.useGlobalPipes(new ValidationPipe({ transform: true, whitelist: true }));

  await app.listen(config.port, config.host);
}

void bootstrap();
