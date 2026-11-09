"""Human-reviewed concept curation; every source read is checked by Learning."""
import hashlib
from datetime import datetime, timezone
from typing import Literal
from uuid import UUID, uuid4

from fastapi import APIRouter, Depends, HTTPException, Request
from neo4j import GraphDatabase
from pydantic import BaseModel, Field, field_validator
from sqlalchemy import create_engine, select
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Session

from app.api.routes.assistant import actor, learning_get
from app.core.config import get_settings
from app.models.curated_concept import CuratedConcept

router = APIRouter(prefix="/ai/curation", tags=["AI curation"], dependencies=[Depends(actor)])


def db_session():
    engine = create_engine(get_settings().database_url, pool_pre_ping=True)
    try:
        with Session(engine) as session:
            yield session
    finally:
        engine.dispose()


def source_snapshot(source_type: str, source_id: UUID, identity: dict, request: Request) -> str:
    path = "/questions/" if source_type == "question" else "/knowledge-items/"
    source = learning_get(path + str(source_id), identity, request)["data"]
    text = "\n".join(str(source.get(field) or "") for field in
                     ("title", "body_plaintext", "summary", "updated_at", "version_id", "latest_version_id", "sha256"))
    return hashlib.sha256(text.encode()).hexdigest()


def reviewer(request: Request):
    if "ai.curation.review" not in request.headers.get("x-authenticated-permissions", "").split():
        raise HTTPException(403, "Curation review permission required.")


def record(row: CuratedConcept) -> dict:
    return {"id": row.id, "source_type": row.source_type, "source_id": row.source_id,
            "name": row.name, "description": row.description, "status": row.status,
            "source_hash": row.source_hash, "proposed_by": row.proposed_by,
            "reviewed_by": row.reviewed_by, "graph_synced": row.graph_synced_at is not None}


class Proposal(BaseModel):
    source_type: Literal["question", "document"]
    source_id: UUID
    name: str = Field(min_length=2, max_length=200)
    description: str = Field(min_length=2, max_length=4000)

    @field_validator("name", "description")
    @classmethod
    def nonempty(cls, value: str) -> str:
        value = value.strip()
        if len(value) < 2:
            raise ValueError("At least two non-space characters are required.")
        return value


@router.post("/concepts", status_code=201)
def propose(body: Proposal, request: Request, identity: dict = Depends(actor), db: Session = Depends(db_session)):
    digest = source_snapshot(body.source_type, body.source_id, identity, request)
    row = CuratedConcept(id=str(uuid4()), organization_id=identity["organization"], source_type=body.source_type,
                         source_id=str(body.source_id), source_hash=digest, name=body.name.strip(),
                         description=body.description.strip(), status="pending", proposed_by=identity["user"])
    db.add(row)
    try:
        db.commit()
    except IntegrityError:
        db.rollback()
        raise HTTPException(409, "Concept already proposed for this source.") from None
    return {"data": record(row)}


@router.get("/concepts")
def list_concepts(source_type: Literal["question", "document"], source_id: UUID, request: Request,
                  identity: dict = Depends(actor), db: Session = Depends(db_session)):
    digest = source_snapshot(source_type, source_id, identity, request)
    rows = db.scalars(select(CuratedConcept).where(CuratedConcept.organization_id == identity["organization"],
        CuratedConcept.source_type == source_type, CuratedConcept.source_id == str(source_id),
        CuratedConcept.status == "approved", CuratedConcept.source_hash == digest)
        .order_by(CuratedConcept.name).limit(100)).all()
    return {"data": [record(row) for row in rows]}


@router.get("/pending", dependencies=[Depends(reviewer)])
def pending(identity: dict = Depends(actor), db: Session = Depends(db_session)):
    rows = db.scalars(select(CuratedConcept).where(CuratedConcept.organization_id == identity["organization"],
        CuratedConcept.status == "pending").order_by(CuratedConcept.created_at).limit(100)).all()
    return {"data": [record(row) for row in rows]}


class Decision(BaseModel):
    decision: Literal["approve", "reject"]


@router.post("/concepts/{concept_id}/review", dependencies=[Depends(reviewer)])
def review(concept_id: UUID, body: Decision, request: Request, identity: dict = Depends(actor), db: Session = Depends(db_session)):
    row = db.scalar(select(CuratedConcept).where(CuratedConcept.id == str(concept_id),
        CuratedConcept.organization_id == identity["organization"]).with_for_update())
    if row is None:
        raise HTTPException(404, "Concept not found.")
    if row.status != "pending":
        raise HTTPException(409, "Concept already reviewed.")
    digest = source_snapshot(row.source_type, UUID(row.source_id), identity, request)
    if digest != row.source_hash:
        raise HTTPException(409, "Source changed; submit a new proposal.")
    row.status = "approved" if body.decision == "approve" else "rejected"
    row.reviewed_by = identity["user"]
    row.reviewed_at = datetime.now(timezone.utc)
    db.commit()
    return {"data": record(row)}


@router.post("/concepts/{concept_id}/sync", dependencies=[Depends(reviewer)])
def sync(concept_id: UUID, request: Request, identity: dict = Depends(actor), db: Session = Depends(db_session)):
    row = db.scalar(select(CuratedConcept).where(CuratedConcept.id == str(concept_id),
        CuratedConcept.organization_id == identity["organization"]))
    if row is None:
        raise HTTPException(404, "Concept not found.")
    if row.status != "approved":
        raise HTTPException(409, "Only approved concepts can be synced.")
    digest = source_snapshot(row.source_type, UUID(row.source_id), identity, request)
    if digest != row.source_hash:
        raise HTTPException(409, "Source changed; graph sync stopped.")
    settings = get_settings()
    try:
        with GraphDatabase.driver(settings.neo4j_uri, auth=(settings.neo4j_username, settings.neo4j_password)) as driver:
            driver.execute_query("""
                MERGE (s:KnowledgeSource {organization_id: $org, source_type: $source_type, source_id: $source_id})
                SET s.source_hash = $source_hash
                MERGE (c:CuratedConcept {organization_id: $org, concept_id: $concept_id})
                SET c.name = $name, c.description = $description
                MERGE (s)-[:HAS_CONCEPT]->(c)
            """, org=row.organization_id, source_type=row.source_type, source_id=row.source_id,
                source_hash=row.source_hash, concept_id=row.id, name=row.name, description=row.description)
    except Exception:
        raise HTTPException(502, "Neo4j unavailable; retry sync.") from None
    row.graph_synced_at = datetime.now(timezone.utc)
    db.commit()
    return {"data": record(row)}
