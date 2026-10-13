from pydantic import BaseModel, Field


class GraphNode(BaseModel):
    id: str
    label: str
    name: str
    properties: dict[str, str | int | float | bool] = Field(default_factory=dict)


class GraphEdge(BaseModel):
    source: str
    target: str
    relationship: str


class DemoGraphResponse(BaseModel):
    demo: bool = True
    source_version: str = "v1"
    nodes: list[GraphNode]
    edges: list[GraphEdge]
