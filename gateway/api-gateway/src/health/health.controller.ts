import { Controller, Get } from '@nestjs/common';

@Controller('health')
export class HealthController {
  @Get()
  health(): { service: string; status: string } {
    return { service: 'api-gateway', status: 'ok' };
  }
}
