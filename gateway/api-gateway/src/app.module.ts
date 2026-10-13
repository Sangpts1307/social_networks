import { SocialClient } from './proxy/social-client';
import { SocialController } from './proxy/social.controller';
import { IdentityController } from './proxy/identity.controller';
import { AdminIdentityController } from './proxy/admin-identity.controller';
import { AdminLearningController } from './proxy/admin-learning.controller';
import { IdentityClient } from './proxy/identity-client';
import { IdentitySessionGuard } from './proxy/identity-session.guard';
import { LearningClient } from './proxy/learning-client';
import { LearningController } from './proxy/learning.controller';
import { Module } from '@nestjs/common';
import { HealthController } from './health/health.controller';
import { ServiceRegistry } from './proxy/service-registry.service';

@Module({
  controllers: [SocialController, HealthController, IdentityController, AdminIdentityController, AdminLearningController, LearningController],
  providers: [SocialClient, ServiceRegistry, IdentityClient, IdentitySessionGuard, LearningClient],
})
export class AppModule {}
