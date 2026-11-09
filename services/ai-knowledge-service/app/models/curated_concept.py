from sqlalchemy import DateTime, Index, String, Text, UniqueConstraint, func
from sqlalchemy.orm import Mapped, mapped_column

from app.models.base import Base


class CuratedConcept(Base):
    __tablename__ = "curated_concepts"
    __table_args__ = (
        UniqueConstraint("organization_id", "source_type", "source_id", "name", name="uq_concept_source_name"),
        Index("ix_concept_source_status", "organization_id", "source_type", "source_id", "status"),
    )

    id: Mapped[str] = mapped_column(String(36), primary_key=True)
    organization_id: Mapped[str] = mapped_column(String(36), nullable=False)
    source_type: Mapped[str] = mapped_column(String(20), nullable=False)
    source_id: Mapped[str] = mapped_column(String(36), nullable=False)
    source_hash: Mapped[str] = mapped_column(String(64), nullable=False)
    name: Mapped[str] = mapped_column(String(200), nullable=False)
    description: Mapped[str] = mapped_column(Text, nullable=False)
    status: Mapped[str] = mapped_column(String(20), nullable=False, default="pending")
    proposed_by: Mapped[str] = mapped_column(String(36), nullable=False)
    reviewed_by: Mapped[str | None] = mapped_column(String(36))
    created_at: Mapped[object] = mapped_column(DateTime(timezone=True), server_default=func.now())
    reviewed_at: Mapped[object | None] = mapped_column(DateTime(timezone=True))
    graph_synced_at: Mapped[object | None] = mapped_column(DateTime(timezone=True))
