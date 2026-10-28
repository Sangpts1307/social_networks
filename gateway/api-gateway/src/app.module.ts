import { AdminRewardController } from './proxy/admin-reward.controller';
import { AiClient } from './proxy/ai-client';
import { AiController } from './proxy/ai.controller';
import { IdentityExtensionController } from './proxy/identity-extension.controller';
import { SocialExtensionController } from './proxy/social-extension.controller';
import { LearningExtensionController } from './proxy/learning-extension.controller';
import { NotificationClient } from './proxy/notification-client';
import { NotificationController } from './proxy/notification.controller';
import { ChatClient } from './proxy/chat-client';
import { ChatController } from './proxy/chat.controller';
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
  controllers: [AdminRewardController, AiController, IdentityExtensionController, SocialExtensionController, LearningExtensionController, NotificationController, ChatController, SocialController, HealthController, IdentityController, AdminIdentityController, AdminLearningController, LearningController],
  providers: [AiClient, NotificationClient, ChatClient, SocialClient, ServiceRegistry, IdentityClient, IdentitySessionGuard, LearningClient],
})
export class AppModule {}
