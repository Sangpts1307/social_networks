"""Store reviewed concepts with source provenance."""
from alembic import op
import sqlalchemy as sa

revision = "20261105_0002"
down_revision = "20260912_0001"
branch_labels = None
depends_on = None


def upgrade():
    op.create_table("curated_concepts",
        sa.Column("id", sa.String(36), primary_key=True),
        sa.Column("organization_id", sa.String(36), nullable=False),
        sa.Column("source_type", sa.String(20), nullable=False),
        sa.Column("source_id", sa.String(36), nullable=False),
        sa.Column("source_hash", sa.String(64), nullable=False),
        sa.Column("name", sa.String(200), nullable=False),
        sa.Column("description", sa.Text(), nullable=False),
        sa.Column("status", sa.String(20), nullable=False),
        sa.Column("proposed_by", sa.String(36), nullable=False),
        sa.Column("reviewed_by", sa.String(36)),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.func.now()),
        sa.Column("reviewed_at", sa.DateTime(timezone=True)),
        sa.Column("graph_synced_at", sa.DateTime(timezone=True)),
        sa.UniqueConstraint("organization_id", "source_type", "source_id", "name", name="uq_concept_source_name"))
    op.create_index("ix_concept_source_status", "curated_concepts", ["organization_id", "source_type", "source_id", "status"])


def downgrade():
    op.drop_index("ix_concept_source_status", table_name="curated_concepts")
    op.drop_table("curated_concepts")
