from fastapi import APIRouter

from app.schemas.graph import DemoGraphResponse
from app.services.demo_graph import build_demo_graph

router = APIRouter(prefix="/graph", tags=["knowledge-graph"])


@router.get("/demo", response_model=DemoGraphResponse)
def demo_graph() -> DemoGraphResponse:
    return build_demo_graph()
