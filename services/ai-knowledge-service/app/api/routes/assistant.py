import hmac
import json
from typing import Literal
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import Request as UrlRequest, build_opener
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Request
from pydantic import BaseModel
from app.core.config import get_settings
from app.services.provider import generate_suggestion, NoRedirect


def actor(request: Request) -> dict:
    settings = get_settings()
    if len(settings.ai_gateway_key) < 32:
        raise HTTPException(503, "AI gateway is not configured.")
    if not hmac.compare_digest(settings.ai_gateway_key, request.headers.get("x-ai-gateway-key", "")):
        raise HTTPException(401, "Unauthenticated.")
    try:
        user = str(UUID(request.headers.get("x-authenticated-user-id", "")))
        organization = str(UUID(request.headers.get("x-authenticated-organization-id", "")))
        context = json.loads(request.headers.get("x-authorization-context", ""))
        if context.get("version") != 1 or not isinstance(context.get("grants"), list) or not isinstance(context.get("org_unit_ids"), list):
            raise ValueError()
    except (ValueError, AttributeError, TypeError):
        raise HTTPException(401, "Invalid identity context.") from None
    return {"user": user, "organization": organization, "context": context}


router = APIRouter(prefix="/ai", tags=["AI base"], dependencies=[Depends(actor)])


def learning_get(path: str, identity: dict, request: Request) -> dict:
    settings = get_settings()
    if len(settings.learning_gateway_key) < 32:
        raise HTTPException(503, "Learning integration is not configured.")
    headers = {"Accept": "application/json", "x-learning-gateway-key": settings.learning_gateway_key,
               "x-authenticated-user-id": identity["user"], "x-authenticated-organization-id": identity["organization"],
               "x-authorization-context": json.dumps(identity["context"]),
               "x-authenticated-permissions": request.headers.get("x-authenticated-permissions", ""),
               "x-request-id": request.headers.get("x-request-id", "")}
    try:
        with build_opener(NoRedirect).open(UrlRequest(settings.learning_service_url.rstrip('/') + '/api/v1' + path, headers=headers), timeout=5) as response:
            raw = response.read(2_097_153)
            if len(raw) > 2_097_152:
                raise ValueError()
            return json.loads(raw)
    except HTTPError as error:
        raise HTTPException(error.code if error.code in (403, 404, 422) else 502, "Learning source unavailable.") from None
    except (URLError, TimeoutError, ValueError):
        raise HTTPException(502, "Learning source unavailable.") from None


@router.get('/status')
def status():
    settings = get_settings()
    return {"data": {"provider": "openai", "model": settings.ai_model, "configured": bool(settings.openai_api_key),
                     "external_processing_enabled": settings.ai_external_processing_enabled,
                     "capabilities": ["summary", "concepts", "keyword_search"], "hybrid_search_ready": False}}


class SuggestionInput(BaseModel):
    source_type: Literal['question', 'document']
    source_id: UUID
    task: Literal['summary', 'concepts'] = 'summary'


@router.post('/suggestions')
def suggest(body: SuggestionInput, request: Request, identity: dict = Depends(actor)):
    settings = get_settings()
    if not settings.ai_external_processing_enabled:
        raise HTTPException(503, detail={"code": "AI_EXTERNAL_PROCESSING_DISABLED", "message": "Enable external processing in environment after approving data policy."})
    path = '/questions/' if body.source_type == 'question' else '/knowledge-items/'
    source = learning_get(path + str(body.source_id), identity, request)['data']
    # Learning enforces tenant, publication and document ACL. No file URLs, IDs, authors or chat are sent to the provider.
    text = '\n'.join(str(source.get(field) or '') for field in ('title', 'body_plaintext', 'summary'))
    return {"data": {"source_type": body.source_type, "source_id": str(body.source_id), "task": body.task,
                     "suggestion": generate_suggestion(text, body.task), "requires_human_review": True}}


@router.get('/search')
def search(request: Request, q: str, identity: dict = Depends(actor)):
    if not q.strip() or len(q) > 120:
        raise HTTPException(422, "Query must contain 1 to 120 characters.")
    query = urlencode({'q': q})
    questions = learning_get('/questions?' + query, identity, request)
    documents = learning_get('/knowledge-items?' + query, identity, request)
    return {"data": {"questions": questions['data'], "documents": documents['data']},
            "meta": {"mode": "keyword", "question_next_cursor": questions.get('meta', {}).get('next_cursor'), "hybrid_search_ready": False}}
