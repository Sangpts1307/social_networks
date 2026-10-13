# AI Knowledge Service

This FastAPI service is deliberately a **deterministic Knowledge Graph demo** in Phase 0. It is not an AI inference pipeline, semantic index, moderation workflow, Expert Finder, or production hybrid search implementation.

## What is included

- `GET /api/v1/health` and `GET /api/v1/graph/demo`.
- SQLAlchemy model and Alembic migration for `demo_graph_seed_runs`, which records only demo seed metadata in `ai_knowledge_db`.
- Idempotent Neo4j seed at `scripts/seed_neo4j.cypher`, linked to the seeded Identity UUIDs.
- A Python 3.12 Dockerfile and pinned runtime requirements.

## Local setup

Copy `.env.example` to `.env`, then run Alembic after MySQL is available:

`alembic upgrade head`

Apply the Cypher file in Neo4j Browser or `cypher-shell`. Run the API with:

`uvicorn app.main:app --reload --port 8090`
