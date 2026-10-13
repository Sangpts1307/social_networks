"""create demo graph seed runs

Revision ID: 20260912_0001
Revises:
Create Date: 2026-09-12
"""

from collections.abc import Sequence

import sqlalchemy as sa
from alembic import op

revision: str = "20260912_0001"
down_revision: str | None = None
branch_labels: Sequence[str] | None = None
depends_on: Sequence[str] | None = None


def upgrade() -> None:
    op.create_table(
        "demo_graph_seed_runs",
        sa.Column("id", sa.String(length=36), nullable=False),
        sa.Column("source_version", sa.String(length=32), nullable=False),
        sa.Column("status", sa.String(length=32), nullable=False),
        sa.Column("nodes_count", sa.Integer(), nullable=False),
        sa.Column("relationships_count", sa.Integer(), nullable=False),
        sa.Column("seeded_at", sa.DateTime(), server_default=sa.text("CURRENT_TIMESTAMP"), nullable=False),
        sa.PrimaryKeyConstraint("id"),
    )


def downgrade() -> None:
    op.drop_table("demo_graph_seed_runs")
