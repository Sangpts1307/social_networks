import { randomUUID } from 'node:crypto';
import type { Server } from 'node:http';
import { WebSocket, WebSocketServer } from 'ws';
import { IdentityClient } from '../proxy/identity-client';
import { ChatClient } from '../proxy/chat-client';
import { NotificationClient } from '../proxy/notification-client';
import type { GatewayIdentityUser } from '../proxy/identity-session.guard';

/** Local realtime bridge. Domain services remain authoritative; clients fetch messages through REST. */
export function attachRealtime(server: Server, identity: IdentityClient, chat: ChatClient, notifications: NotificationClient, allowedOrigins: string[]) {
  const wss = new WebSocketServer({ noServer: true, maxPayload: 8192, perMessageDeflate: false });
  const sessions = new Map<WebSocket, string>();
  server.on('upgrade', (request, socket, head) => {
    if (request.url !== '/api/v1/realtime' || (request.headers.origin && !allowedOrigins.includes(request.headers.origin)) || wss.clients.size >= 100) {
      socket.write('HTTP/1.1 403 Forbidden\r\nConnection: close\r\n\r\n'); socket.destroy(); return;
    }
    wss.handleUpgrade(request, socket, head, ws => wss.emit('connection', ws));
  });
  wss.on('connection', ws => {
    let token = '';
    let conversationId: string | null = null;
    let fingerprint = '';
    let busy = false;
    let alive = true;
    let messages = 0;
    const opened = Date.now();
    const authDeadline = setTimeout(() => { if (!token) ws.close(4401, 'Authentication required.'); }, 5000);
    const send = (value: unknown) => {
      if (ws.readyState !== WebSocket.OPEN) return;
      if (ws.bufferedAmount > 65536) { ws.close(4429, 'Client is too slow.'); return; }
      ws.send(JSON.stringify(value));
    };
    async function user(): Promise<GatewayIdentityUser> {
      const result = await identity.call('/auth/session', 'GET', token, undefined, randomUUID());
      const current = result.data?.user as GatewayIdentityUser | undefined;
      if (result.status !== 200 || !current || current.must_change_password) throw new Error('Session unavailable.');
      return current;
    }
    async function tick() {
      if (!token || busy || ws.readyState !== WebSocket.OPEN) return;
      busy = true;
      try {
        const current = await user();
        const requestId = randomUUID();
        const [count, latest, conversation] = await Promise.all([
          notifications.request('GET', '/notifications/unread-count', current, undefined, requestId),
          notifications.request('GET', '/notifications', current, undefined, requestId),
          conversationId ? chat.request('GET', `/conversations/${encodeURIComponent(conversationId)}`, current, undefined, requestId) : Promise.resolve(null),
        ]);
        if (conversation && conversation.status !== 200) {
          send({ type: 'subscription.revoked', conversation_id: conversationId }); conversationId = null;
        }
        if (count.status !== 200 || latest.status !== 200) throw new Error('Notification service unavailable.');
        const state = { unread_count: count.data?.data?.unread_count ?? 0, latest_notification_id: latest.data?.data?.[0]?.id ?? null,
          conversation_id: conversationId, conversation_version: conversation?.status === 200 ? conversation.data?.data?.writeVersion ?? 0 : null,
          last_message_id: conversation?.status === 200 ? conversation.data?.data?.lastMessage?.id ?? null : null };
        const next = JSON.stringify(state);
        if (next !== fingerprint) { fingerprint = next; send({ type: 'state.changed', data: state }); }
      } catch {
        // Fail closed on revocation or service errors; reconnecting always rechecks the session and resource access.
        ws.close(4401, 'Session or service unavailable.');
      } finally { busy = false; }
    }
    ws.on('error', () => ws.terminate());
    ws.on('pong', () => { alive = true; });
    ws.on('message', async (raw, binary) => {
      if (binary || ++messages > 20 + Math.floor((Date.now() - opened) / 60000) * 20) { ws.close(4429, 'Message limit exceeded.'); return; }
      if (busy) return;
      busy = true;
      try {
        const message = JSON.parse(raw.toString());
        if (!token) {
          if (message.type !== 'authenticate' || typeof message.token !== 'string' || !/^[a-f0-9]{64}$/i.test(message.token)) throw new Error();
          token = message.token;
          const current = await user();
          const sessionKey = `${current.organization_id}:${current.id}`;
          if ([...sessions.values()].filter(key => key === sessionKey).length >= 2) throw new Error();
          sessions.set(ws, sessionKey); clearTimeout(authDeadline); send({ type: 'ready', poll_interval_ms: 5000 });
        } else if (message.type === 'subscribe' && typeof message.conversation_id === 'string' && /^[0-9a-f-]{36}$/i.test(message.conversation_id)) {
          const current = await user();
          const result = await chat.request('GET', `/conversations/${encodeURIComponent(message.conversation_id)}`, current, undefined, randomUUID());
          if (result.status !== 200) { send({ type: 'subscription.denied' }); return; }
          conversationId = message.conversation_id; fingerprint = ''; send({ type: 'subscribed', conversation_id: conversationId });
        } else if (message.type === 'unsubscribe') { conversationId = null; fingerprint = ''; }
        else throw new Error();
      } catch { ws.close(4401, 'Invalid or unauthorized message.'); }
      finally { busy = false; }
    });
    const timer = setInterval(() => {
      if (!alive) { ws.terminate(); return; }
      alive = false; ws.ping(); void tick();
    }, 5000);
    ws.on('close', () => { clearInterval(timer); clearTimeout(authDeadline); sessions.delete(ws); token = ''; });
  });
  return wss;
}
