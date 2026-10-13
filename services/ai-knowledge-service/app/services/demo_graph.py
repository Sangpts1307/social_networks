from app.schemas.graph import DemoGraphResponse, GraphEdge, GraphNode


def build_demo_graph() -> DemoGraphResponse:
    """Return deterministic fixtures, not an AI inference or production graph query."""

    nodes = [
        GraphNode(
            id="30000000-0000-4000-8000-000000000002",
            label="User",
            name="Trần Chuyên Gia",
            properties={"role": "qa_expert"},
        ),
        GraphNode(
            id="30000000-0000-4000-8000-000000000003",
            label="User",
            name="Lê Thành Viên",
            properties={"role": "member"},
        ),
        GraphNode(
            id="40000000-0000-4000-8000-000000000001",
            label="Document",
            name="Hướng dẫn xây dựng Knowledge Graph",
            properties={"status": "approved"},
        ),
        GraphNode(id="50000000-0000-4000-8000-000000000001", label="Concept", name="Knowledge Graph"),
        GraphNode(id="50000000-0000-4000-8000-000000000002", label="Concept", name="Hybrid Search"),
    ]
    edges = [
        GraphEdge(
            source="30000000-0000-4000-8000-000000000002",
            target="40000000-0000-4000-8000-000000000001",
            relationship="AUTHORED",
        ),
        GraphEdge(
            source="40000000-0000-4000-8000-000000000001",
            target="50000000-0000-4000-8000-000000000001",
            relationship="MENTIONS",
        ),
        GraphEdge(
            source="40000000-0000-4000-8000-000000000001",
            target="50000000-0000-4000-8000-000000000002",
            relationship="MENTIONS",
        ),
        GraphEdge(
            source="30000000-0000-4000-8000-000000000003",
            target="50000000-0000-4000-8000-000000000001",
            relationship="INTERESTED_IN",
        ),
    ]
    return DemoGraphResponse(nodes=nodes, edges=edges)
