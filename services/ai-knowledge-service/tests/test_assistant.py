import asyncio
import json
import unittest
from types import SimpleNamespace
from unittest.mock import patch

from fastapi import HTTPException
from app.main import app
from app.api.routes.assistant import actor
from app.services.provider import generate_suggestion


async def http_request(path, headers=None, body=None, method='GET'):
    sent = []
    raw = json.dumps(body).encode() if body is not None else b''
    delivered = False
    async def receive():
        nonlocal delivered
        if not delivered:
            delivered = True
            return {'type': 'http.request', 'body': raw, 'more_body': False}
        await asyncio.sleep(0.001)
        return {'type': 'http.disconnect'}
    async def send(message):
        sent.append(message)
    scope = {'type': 'http', 'asgi': {'version': '3.0'}, 'http_version': '1.1', 'method': method,
             'scheme': 'http', 'path': path, 'raw_path': path.encode(), 'query_string': b'', 'root_path': '',
             'server': ('test', 80), 'client': ('127.0.0.1', 123),
             'headers': [(k.lower().encode(), v.encode()) for k, v in {'content-type': 'application/json', **(headers or {})}.items()]}
    await app(scope, receive, send)
    status = next(item['status'] for item in sent if item['type'] == 'http.response.start')
    result = b''.join(item.get('body', b'') for item in sent if item['type'] == 'http.response.body')
    return status, json.loads(result) if result else None


class AssistantTests(unittest.TestCase):
    def test_gateway_auth_is_required_and_configuration_is_not_leaked(self):
        settings = SimpleNamespace(ai_gateway_key='x' * 40)
        with patch('app.api.routes.assistant.get_settings', return_value=settings):
            status, _ = asyncio.run(http_request('/api/v1/ai/status'))
            self.assertEqual(status, 401)
            status, _ = asyncio.run(http_request('/api/v1/ai/status', {'x-ai-gateway-key': 'x' * 40}))
            self.assertEqual(status, 401)

    def test_forbidden_source_never_reaches_provider(self):
        app.dependency_overrides[actor] = lambda: {'user': 'user', 'organization': 'org', 'context': {}}
        try:
            with patch('app.api.routes.assistant.get_settings', return_value=SimpleNamespace(ai_external_processing_enabled=True)), \
                 patch('app.api.routes.assistant.learning_get', side_effect=HTTPException(404, 'Unavailable')), \
                 patch('app.api.routes.assistant.generate_suggestion') as provider:
                status, _ = asyncio.run(http_request('/api/v1/ai/suggestions', body={'source_type': 'document', 'source_id': '70000000-0000-4000-8000-000000000001'}, method='POST'))
                self.assertEqual(status, 404)
                provider.assert_not_called()
        finally:
            app.dependency_overrides.clear()

    def test_external_processing_disabled_and_chat_source_rejected(self):
        app.dependency_overrides[actor] = lambda: {}
        try:
            with patch('app.api.routes.assistant.get_settings', return_value=SimpleNamespace(ai_external_processing_enabled=False)):
                status, _ = asyncio.run(http_request('/api/v1/ai/suggestions', body={'source_type': 'question', 'source_id': '70000000-0000-4000-8000-000000000001'}, method='POST'))
                self.assertEqual(status, 503)
                status, _ = asyncio.run(http_request('/api/v1/ai/suggestions', body={'source_type': 'chat', 'source_id': '70000000-0000-4000-8000-000000000001'}, method='POST'))
                self.assertEqual(status, 422)
        finally:
            app.dependency_overrides.clear()

    def test_provider_request_is_bounded_and_not_stored(self):
        settings = SimpleNamespace(openai_api_key='test-key', ai_model='test-model', ai_max_output_tokens=2048,
                                   ai_max_input_chars=100, ai_api_base_url='https://api.openai.com/v1', ai_timeout_seconds=5)
        class Response:
            def __enter__(self): return self
            def __exit__(self, *args): pass
            def read(self, size): return json.dumps({'status': 'completed', 'output': [{'type': 'message', 'content': [{'type': 'output_text', 'text': 'Suggestion'}]}]}).encode()
        with patch('app.services.provider.get_settings', return_value=settings), patch('app.services.provider.build_opener') as opener:
            opener.return_value.open.return_value = Response()
            self.assertEqual(generate_suggestion('a' * 200, 'summary'), 'Suggestion')
            request = opener.return_value.open.call_args.args[0]
            payload = json.loads(request.data)
            self.assertFalse(payload['store'])
            self.assertEqual(len(payload['input']), 100)
            self.assertEqual(payload['max_output_tokens'], 2048)


if __name__ == '__main__':
    unittest.main()
