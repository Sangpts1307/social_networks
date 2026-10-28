const { test } = require('node:test');
const assert = require('node:assert/strict');
const http = require('node:http');
const WebSocket = require('ws');
const { attachRealtime } = require('../dist/realtime/realtime-server');

const token = 'a'.repeat(64);
const user = { id: '30000000-0000-4000-8000-000000000001', organization_id: '10000000-0000-4000-8000-000000000001', must_change_password: false };
function next(ws, type, timeout = 8000) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => { ws.off('message', handler); reject(new Error('Timed out waiting for ' + type)); }, timeout);
    function handler(raw) { const event = JSON.parse(raw); if (event.type === type) { clearTimeout(timer); ws.off('message', handler); resolve(event); } }
    ws.on('message', handler);
  });
}
function closed(ws) { return new Promise(resolve => ws.once('close', (code) => resolve(code))); }

test('realtime checks sessions and conversation membership before sending state', { timeout: 20000 }, async () => {
  let revoked = false;
  let valid = true;
  const identity = { call: async (_path, _method, supplied) => ({ status: valid && supplied === token ? 200 : 401, data: { user } }) };
  const chat = { request: async (_method, path) => ({ status: path.endsWith('000000000001') && !revoked ? 200 : 404, data: { data: { writeVersion: 1, lastMessage: { id: 'message' } } } }) };
  const notifications = { request: async (_method, path) => ({ status: 200, data: { data: path.endsWith('unread-count') ? { unread_count: 2 } : [{ id: 'notification' }] } }) };
  const server = http.createServer();
  const realtime = attachRealtime(server, identity, chat, notifications, ['http://allowed.test']);
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const url = 'ws://127.0.0.1:' + server.address().port + '/api/v1/realtime';
  const ws = new WebSocket(url, { origin: 'http://allowed.test' });
  try {
    await new Promise(resolve => ws.once('open', resolve));
    let event = next(ws, 'ready'); ws.send(JSON.stringify({ type: 'authenticate', token })); await event;
    event = next(ws, 'subscription.denied'); ws.send(JSON.stringify({ type: 'subscribe', conversation_id: '80000000-0000-4000-8000-000000000099' })); await event;
    event = next(ws, 'subscribed'); ws.send(JSON.stringify({ type: 'subscribe', conversation_id: '80000000-0000-4000-8000-000000000001' })); await event;
    const state = await next(ws, 'state.changed'); assert.equal(state.data.last_message_id, 'message');
    revoked = true;
    const gone = await next(ws, 'subscription.revoked'); assert.equal(gone.conversation_id, '80000000-0000-4000-8000-000000000001');
    valid = false;
    assert.equal(await closed(ws), 4401);
  } finally {
    ws.terminate(); for (const client of realtime.clients) client.terminate();
    await new Promise(resolve => realtime.close(resolve)); await new Promise(resolve => server.close(resolve));
  }
});
