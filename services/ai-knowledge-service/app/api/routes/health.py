from fastapi import APIRouter

router = APIRouter(tags=["health"])


@router.get("/health")
def health() -> dict[str, str]:
    return {"service": "ai-knowledge-service", "status": "ok", "mode": "deterministic-demo"}
