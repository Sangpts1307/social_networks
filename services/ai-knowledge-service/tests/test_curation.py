import asyncio
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

from sqlalchemy import create_engine
from sqlalchemy.orm import Session

from app.main import app
from app.models import Base
from app.api.routes.assistant import actor
from app.api.routes.curation import db_session
from test_assistant import http_request


class CurationTests(unittest.TestCase):
    def setUp(self):
        self.file = tempfile.TemporaryDirectory()
        self.engine = create_engine('sqlite:///' + str(Path(self.file.name) / 'curation.sqlite'))
        Base.metadata.create_all(self.engine)
        app.dependency_overrides[actor] = lambda: {'user': '30000000-0000-4000-8000-000000000001',
            'organization': '10000000-0000-4000-8000-000000000001', 'context': {}}
        app.dependency_overrides[db_session] = self.session
        self.source = {'data': {'title': 'Quy trình', 'body_plaintext': 'Nội dung', 'summary': ''}}

    def tearDown(self):
        app.dependency_overrides.clear()
        self.engine.dispose()
        self.file.cleanup()

    def session(self):
        with Session(self.engine) as db:
            yield db

    def call(self, path, method='GET', body=None, reviewer=False):
        headers = {'x-authenticated-permissions': 'ai.curation.review'} if reviewer else {}
        return asyncio.run(http_request(path, headers, body, method))

    def test_proposal_review_acl_and_stale_source(self):
        source_id = '70000000-0000-4000-8000-000000000001'
        body = {'source_type': 'document', 'source_id': source_id, 'name': 'Quy trình', 'description': 'Mô tả khái niệm'}
        with patch('app.api.routes.curation.learning_get', return_value=self.source):
            status, result = self.call('/api/v1/ai/curation/concepts', 'POST', body)
            self.assertEqual(status, 201)
            concept_id = result['data']['id']
            self.assertEqual(self.call('/api/v1/ai/curation/pending')[0], 403)
            self.assertEqual(len(self.call('/api/v1/ai/curation/pending', reviewer=True)[1]['data']), 1)
            self.assertEqual(self.call('/api/v1/ai/curation/concepts/' + concept_id + '/review', 'POST', {'decision': 'approve'})[0], 403)
            self.source['data']['body_plaintext'] = 'Đã sửa'
            self.assertEqual(self.call('/api/v1/ai/curation/concepts/' + concept_id + '/review', 'POST', {'decision': 'approve'}, True)[0], 409)
            self.source['data']['body_plaintext'] = 'Nội dung'
            status, result = self.call('/api/v1/ai/curation/concepts/' + concept_id + '/review', 'POST', {'decision': 'approve'}, True)
            self.assertEqual(status, 200)
            self.assertEqual(result['data']['status'], 'approved')
            status, result = self.call('/api/v1/ai/curation/concepts?source_type=document&source_id=' + source_id)
            self.assertEqual(status, 200)
            self.assertEqual(len(result['data']), 1)
            with patch('app.api.routes.curation.GraphDatabase.driver') as driver:
                status, result = self.call('/api/v1/ai/curation/concepts/' + concept_id + '/sync', 'POST', {}, True)
                self.assertEqual(status, 200)
                self.assertTrue(result['data']['graph_synced'])
                self.assertEqual(driver.return_value.__enter__.return_value.execute_query.call_count, 1)


if __name__ == '__main__':
    unittest.main()
