// Deterministic Phase 0 demo data. It is intentionally not an AI-generated graph.
CREATE CONSTRAINT demo_user_id IF NOT EXISTS FOR (node:User) REQUIRE node.id IS UNIQUE;
CREATE CONSTRAINT demo_document_id IF NOT EXISTS FOR (node:Document) REQUIRE node.id IS UNIQUE;
CREATE CONSTRAINT demo_concept_id IF NOT EXISTS FOR (node:Concept) REQUIRE node.id IS UNIQUE;

MERGE (expert:User {id: '30000000-0000-4000-8000-000000000002'})
SET expert.name = 'Trần Chuyên Gia', expert.role = 'qa_expert'
MERGE (member:User {id: '30000000-0000-4000-8000-000000000003'})
SET member.name = 'Lê Thành Viên', member.role = 'member'
MERGE (document:Document {id: '40000000-0000-4000-8000-000000000001'})
SET document.title = 'Hướng dẫn xây dựng Knowledge Graph', document.status = 'approved'
MERGE (knowledgeGraph:Concept {id: '50000000-0000-4000-8000-000000000001'})
SET knowledgeGraph.name = 'Knowledge Graph'
MERGE (hybridSearch:Concept {id: '50000000-0000-4000-8000-000000000002'})
SET hybridSearch.name = 'Hybrid Search'
MERGE (expert)-[:AUTHORED]->(document)
MERGE (document)-[:MENTIONS]->(knowledgeGraph)
MERGE (document)-[:MENTIONS]->(hybridSearch)
MERGE (member)-[:INTERESTED_IN]->(knowledgeGraph);
