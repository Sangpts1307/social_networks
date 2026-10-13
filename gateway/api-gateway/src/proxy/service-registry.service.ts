import { Injectable } from '@nestjs/common';
import { gatewayConfig } from '../config/gateway.config';

/**
 * Central registry for internal service base URLs. HTTP proxy routes are added only
 * when the corresponding service contract is approved; the gateway owns no business data.
 */
@Injectable()
export class ServiceRegistry {
  private readonly services = gatewayConfig().services;

  get(name: keyof typeof this.services): string {
    return this.services[name];
  }
}
