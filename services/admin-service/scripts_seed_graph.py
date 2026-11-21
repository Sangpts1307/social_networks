"""Build a deterministic knowledge graph preview from catalog metadata and DOCX text."""
import json
import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

root = Path(__file__).resolve().parents[3]
corpus = root / 'knowledge_learning'
records = json.loads((corpus / 'catalog.json').read_text(encoding='utf-8'))['documents']
namespace = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
nodes = {}
edges = {}


def add_node(identifier, kind, name, **properties):
    nodes.setdefault(identifier, {'id': identifier, 'type': kind, 'name': name, **properties})


def add_edge(source, target, relation, **properties):
    edges.setdefault((source, target, relation), {'source': source, 'target': target, 'relation': relation, **properties})


for record in records:
    code = record['document_code']
    document_id = f'document:{code}'
    department_id = f"department:{record['department']}"
    group = record['relative_path'].split('/')[0]
    taxonomy_id = f'taxonomy:{group}'
    with zipfile.ZipFile(corpus / record['relative_path']) as archive:
        document = ET.fromstring(archive.read('word/document.xml'))
    paragraphs = [
        ''.join(text.text or '' for text in paragraph.findall('.//w:t', namespace)).strip()
        for paragraph in document.findall('.//w:p', namespace)
    ]
    paragraphs = [paragraph for paragraph in paragraphs if paragraph]
    summary = next((paragraphs[index + 1] for index, value in enumerate(paragraphs[:-1])
                    if value == 'Mục đích và kết luận chính'), '')
    add_node(taxonomy_id, 'taxonomy', group[3:].replace('_', ' ').title(), provenance='catalog.json')
    add_node(department_id, 'department', record['department'], provenance='catalog.json')
    add_edge(taxonomy_id, department_id, 'BAO_GỒM', provenance='catalog.json')
    add_node(document_id, 'document', record['title'], code=code, department=record['department'],
             status=record['status'], classification=record['classification'], version=record['version'],
             effective_date=record['effective_date'], review_date=record['review_date'],
             document_type=record['document_type'], audience=record['audience'],
             summary=summary, content='\n'.join(paragraphs[:120]), path=record['relative_path'],
             short_title=' '.join(record['title'].split()[:6]), poster='Quản trị viên demo (dữ liệu mẫu)',
             owner='Chưa phân công', provenance='DOCX + catalog.json')
    add_edge(department_id, document_id, 'SỞ_HỮU', provenance='catalog.json')
    add_edge(taxonomy_id, document_id, 'PHÂN_LOẠI', provenance='catalog.json')
    for tag in record['tags']:
        tag_id = 'tag:' + tag.casefold()
        add_node(tag_id, 'tag', tag, provenance='catalog.json')
        add_edge(document_id, tag_id, 'GẮN_THẺ', provenance='catalog.json')
    for entity in record['entities']:
        concept_id = 'concept:' + entity.casefold()
        add_node(concept_id, 'concept', entity, provenance='catalog.json')
        add_edge(document_id, concept_id, 'ĐỀ_CẬP', provenance='catalog.json')
    for related in record['related_documents']:
        add_edge(document_id, f'document:{related}', 'LIÊN_QUAN', provenance='catalog.json')
    for relationship in record['relationships']:
        source_id = 'concept:' + relationship['source'].casefold()
        target_id = 'concept:' + relationship['target'].casefold()
        add_node(source_id, 'concept', relationship['source'], provenance='catalog.json')
        add_node(target_id, 'concept', relationship['target'], provenance='catalog.json')
        add_edge(source_id, target_id, relationship['predicate'], evidence=code, provenance='catalog.json')

output = Path(__file__).resolve().parent / 'resources/data/knowledge_graph_demo.json'
output.parent.mkdir(parents=True, exist_ok=True)
output.write_text(json.dumps({'nodes': list(nodes.values()), 'edges': list(edges.values())},
                             ensure_ascii=False), encoding='utf-8')
print(f'{len(records)} documents, {len(nodes)} nodes, {len(edges)} edges -> {output}')
