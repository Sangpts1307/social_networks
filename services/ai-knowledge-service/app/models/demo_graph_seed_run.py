import uuid
from datetime import datetime

from sqlalchemy import DateTime, Integer, String, func
from sqlalchemy.orm import Mapped, mapped_column

from app.models.base import Base


class DemoGraphSeedRun(Base):
    """Operational metadata only; the graph itself remains in Neo4j."""

    __tablename__ = "demo_graph_seed_runs"

    id: Mapped[str] = mapped_column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    source_version: Mapped[str] = mapped_column(String(32), default="v1")
    status: Mapped[str] = mapped_column(String(32), default="completed")
    nodes_count: Mapped[int] = mapped_column(Integer)
    relationships_count: Mapped[int] = mapped_column(Integer)
    seeded_at: Mapped[datetime] = mapped_column(DateTime, server_default=func.now())
