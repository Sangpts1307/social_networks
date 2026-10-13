export interface GatewayConfig {
  host: string;
  port: number;
  allowedOrigins: string[];
  services: Record<string, string>;
}

export const gatewayConfig = (): GatewayConfig => ({
  host: process.env.HOST ?? '127.0.0.1',
  port: Number.parseInt(process.env.PORT ?? '8080', 10),
  allowedOrigins: (process.env.ALLOWED_ORIGINS ?? 'http://localhost:5173,http://127.0.0.1:5173,http://localhost:5174,http://127.0.0.1:5174,http://localhost:5180,http://127.0.0.1:5180,http://localhost:8086,http://127.0.0.1:8086')
    .split(',')
    .map((origin) => origin.trim())
    .filter(Boolean),
  services: {
    identity: process.env.IDENTITY_SERVICE_URL ?? 'http://localhost:8081',
    social: process.env.SOCIAL_SERVICE_URL ?? 'http://localhost:8082',
    learning: process.env.LEARNING_SERVICE_URL ?? 'http://localhost:8083',
    chat: process.env.CHAT_SERVICE_URL ?? 'http://localhost:8084',
    notification: process.env.NOTIFICATION_SERVICE_URL ?? 'http://localhost:8085',
    aiKnowledge: process.env.AI_KNOWLEDGE_SERVICE_URL ?? 'http://localhost:8090',
  },
});
