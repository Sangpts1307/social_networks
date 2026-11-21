from pathlib import Path
from html import escape
import re
root=Path(__file__).resolve().parents[1]
rows=[]
for line in (root/'docs/api/ROUTE_INVENTORY.md').read_text().splitlines():
 m=re.match(r'^\| (GET|POST|PUT|PATCH|DELETE) \| `([^`]+)` \| `([^`]+)` \|',line)
 if m:rows.append(m.groups())
# Mỗi trang tương ứng đúng một tiểu mục báo cáo. Đây là route tại Gateway, không phải xác nhận nghiệm thu.
groups=[
 ('1.1.1','Dịch vụ định danh và phân quyền',lambda p:'/auth/' in p or '/me' in p or '/directory/' in p or '/admin/users' in p or '/admin/roles' in p or '/admin/permissions' in p or '/admin/org-units' in p or '/admin/audit-logs' in p),
 ('1.1.2','Dịch vụ mạng xã hội nội bộ',lambda p:'/social/' in p),
 ('1.1.3','Dịch vụ tài liệu và hỏi đáp',lambda p:'/learning/' in p or '/admin/learning/' in p),
 ('1.1.4','Dịch vụ chat và thông báo',lambda p:'/chat/' in p or '/notifications' in p),
 ('1.1.5','Tích hợp sự kiện và xử lý lặp',lambda p:'/events' in p or '/outbox' in p or '/jobs' in p),
 ('1.2.1','Tiếp nhận tài liệu và tạo tác vụ',lambda p:'/ai/' in p and ('ingestion' in p or 'jobs' in p or 'sources' in p)),
 ('1.2.2','Trích văn bản, chia đoạn và đề xuất tri thức',lambda p:'/ai/' in p and ('extraction' in p or 'concepts' in p or 'relations' in p or 'chunks' in p)),
 ('1.2.3','Duyệt đề xuất và đồng bộ đồ thị',lambda p:'/ai/' in p and ('curation' in p or 'sync' in p)),
 ('1.2.4','Tìm kiếm và xác minh quyền nguồn',lambda p:'search' in p or ('/ai/' in p and 'query' in p)),
]
colors={'GET':'#61affe','POST':'#49cc90','PUT':'#fca130','PATCH':'#50e3c2','DELETE':'#f93e3e'}
outdir=root/'docs/api/chapter-3';outdir.mkdir(exist_ok=True)
nav=''.join(f'<a href="{num.replace(".","-")}.html">{num} {escape(title)}</a>' for num,title,_ in groups)
for num,title,pred in groups:
 items=[r for r in rows if pred(r[1])]
 cards=''.join(f'<div class="op" style="--c:{colors[m]}"><b>{m}</b><code>{escape(path)}</code><small>{escape(src)}</small></div>' for m,path,src in items)
 if not cards:cards='<p class="note">Không có route Gateway riêng cho bước xử lý nền này trong bản kiểm kê. Minh chứng phù hợp là cấu trúc producer/consumer, bản ghi outbox và log hoặc trạng thái job khi chạy thử.</p>'
 html=f'''<!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{num} {escape(title)}</title><style>body{{font:16px Arial,sans-serif;background:#fafafa;color:#222;margin:0}}header{{background:#17324d;color:white;padding:24px 5%}}h1{{margin:0;font-size:26px}}header p{{margin:10px 0 0}}nav{{background:white;border-bottom:1px solid #ddd;padding:12px 5%;display:flex;flex-wrap:wrap;gap:12px}}nav a{{font-size:13px;color:#2463a6;text-decoration:none}}main{{max-width:1100px;margin:30px auto;padding:0 20px}}.op{{display:flex;gap:15px;align-items:center;border:1px solid var(--c);background:color-mix(in srgb,var(--c) 10%,white);border-radius:4px;margin:8px 0;padding:9px 12px}}.op b{{background:var(--c);color:white;width:76px;text-align:center;padding:6px 0;border-radius:3px;font-size:13px}}code{{font-size:14px;font-weight:bold}}small{{margin-left:auto;color:#555;font-size:11px;text-align:right;max-width:36%}}.note{{background:#fff4d6;padding:15px}}@media print{{nav{{display:none}}.op{{break-inside:avoid}}}}@media(max-width:700px){{.op{{flex-wrap:wrap}}small{{margin-left:90px;max-width:100%}}}}</style><header><h1>{num} {escape(title)}</h1><p>{len(items)} API liên quan tại Gateway · Route trong mã nguồn, chưa đồng nghĩa đã nghiệm thu nghiệp vụ</p></header><nav>{nav}</nav><main>{cards}</main></html>'''
 path=outdir/(num.replace('.','-')+'.html');path.write_text(html);print(num,len(items),path)
index='<!doctype html><html lang="vi"><meta charset="utf-8"><title>API theo mục báo cáo</title><style>body{font:18px Arial;margin:40px;line-height:2}a{display:block}</style><h1>API theo từng mục Chương 3</h1>'+nav.replace('<a ','<a style="display:block" ')+'</html>'
(outdir/'index.html').write_text(index)
