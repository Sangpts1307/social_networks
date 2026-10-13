from fastapi import FastAPI

from app.api.routes import graph, health

app = FastAPI(
    title="AI Knowledge Service",
    version="0.1.0",
    description="Deterministic Knowledge Graph demo; production AI pipelines are intentionally deferred.",
)
app.include_router(health.router, prefix="/api/v1")
app.include_router(graph.router, prefix="/api/v1")
