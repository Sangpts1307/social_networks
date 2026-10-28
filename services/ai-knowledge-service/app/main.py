from fastapi import FastAPI

from app.api.routes import graph, health, assistant

app = FastAPI(
    title="AI Knowledge Service",
    version="0.1.0",
    description="Knowledge Graph demo, permission-aware keyword search and provider-backed AI suggestions.",
)
app.include_router(health.router, prefix="/api/v1")
app.include_router(graph.router, prefix="/api/v1")

app.include_router(assistant.router, prefix="/api/v1")
