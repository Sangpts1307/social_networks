async function compose(SOURCE_ID,TARGET_ID,START,END,OFFSET){
// Executed through the Figma connector. Parameters are supplied per batch.
const source = await figma.getNodeByIdAsync(SOURCE_ID);
const library = await figma.getNodeByIdAsync('0:1');
const target = await figma.getNodeByIdAsync(TARGET_ID);
await figma.setCurrentPageAsync(target);
const created = [], mutated = [], removed = [], screens = [], errors = [];
const track = n => {created.push(n.id);if('findAll'in n)created.push(...n.findAll(()=>true).map(c=>c.id));return n};
const textNodes = source.findAllWithCriteria({types:['TEXT']});
const fonts = new Map();
for(const t of textNodes)for(const s of t.getStyledTextSegments(['fontName']))fonts.set(s.fontName.family+'|'+s.fontName.style,{family:s.fontName.family,style:s.fontName.style});
for(const f of fonts.values())if(f.family!=='Menlo')await figma.loadFontAsync(f);
await figma.loadFontAsync({family:'JetBrains Mono',style:'Regular'});
await figma.loadFontAsync({family:'JetBrains Mono',style:'Bold'});
const variables = await figma.variables.getLocalVariablesAsync('COLOR');
const colorMap = new Map();
const colorKey = c => [c.r,c.g,c.b].map(x=>Math.round(x*255)).join(',');
for(const v of variables){const value=Object.values(v.valuesByMode)[0];if(value&&typeof value==='object'&&'r'in value)colorMap.set(colorKey(value),v)}
const hash=s=>{let h=2166136261;for(let i=0;i<s.length;i++)h=Math.imul(h^s.charCodeAt(i),16777619);return(h>>>0).toString(36)};
const signature=n=>JSON.stringify({w:Math.round(n.width),h:Math.round(n.height),nodes:[n,...n.findAll(()=>true)].map(d=>({t:d.type,n:d.name,w:Math.round(d.width),h:Math.round(d.height),f:'fills'in d?d.fills:null,s:'strokes'in d?d.strokes:null,txt:d.type==='TEXT'?d.characters:null,font:d.type==='TEXT'?d.fontName:null,fs:d.type==='TEXT'?d.fontSize:null}))});
const masters = new Map(library.findAllWithCriteria({types:['COMPONENT']}).map(c=>[c.name,c]));
let masterIndex=masters.size;
function bindPaints(n){for(const field of ['fills','strokes'])if(field in n&&Array.isArray(n[field])){let changed=false;const paints=n[field].map(p=>{if(p.type!=='SOLID'||(p.opacity??1)<0.9999)return p;const v=colorMap.get(colorKey(p.color));if(!v)return p;changed=true;return {...figma.variables.setBoundVariableForPaint(p,'color',v),opacity:p.opacity??1}});if(changed){n[field]=paints;mutated.push(n.id)}}}
function prepare(n){for(const d of [n,...n.findAll(()=>true)]){if(d.type==='TEXT'&&typeof d.fontName==='object'&&d.fontName.family==='Menlo'){const current=d.fontName.style;d.fontName={family:'JetBrains Mono',style:/Bold|Black/.test(current)?'Bold':'Regular'};mutated.push(d.id)}bindPaints(d)}}
function reuse(n){
  const key='Shared/'+n.name+' · '+hash(signature(n));
  let master=masters.get(key);
  if(!master){const cloned=track(n.clone());master=figma.createComponentFromNode(cloned);track(master);master.name=key;master.description='Thành phần được dựng từ giao diện hiện tại trong mã nguồn. Chỉnh sửa thành phần chính để cập nhật các màn hình sử dụng.';library.appendChild(master);master.x=100+(masterIndex%6)*400;master.y=1400+Math.floor(masterIndex/6)*180;masterIndex++;masters.set(key,master)}
  const parent=n.parent,index=parent.children.indexOf(n),x=n.x,y=n.y,w=n.width,h=n.height;
  const hs=n.layoutSizingHorizontal,vs=n.layoutSizingVertical,lp=n.layoutPositioning;
  const instance=track(master.createInstance());parent.insertChild(index,instance);instance.name=n.name;instance.resize(w,h);instance.x=x;instance.y=y;
  if(lp==='ABSOLUTE')instance.layoutPositioning='ABSOLUTE';
  try{instance.layoutSizingHorizontal=hs;instance.layoutSizingVertical=vs}catch(e){}
  removed.push(n.id);n.remove();return instance;
}
const references = source.children[0].children.map(w=>w.name==='Section:margin'?w.children[0]:w);
for(const section of references.slice(START,END)){
  const title=section.children[0].findAllWithCriteria({types:['TEXT']})[0].characters;
  if(target.children.some(n=>n.name===title))continue;
  const original=section.children[1];
  const content=track(original.clone());prepare(content);
  const headerNodes=content.findAllWithCriteria({types:['FRAME']}).filter(n=>n.name==='Header');
  const controls=content.findAllWithCriteria({types:['FRAME']}).filter(n=>['Button','Text Input','Password Input','Textarea','Select'].includes(n.name)&&!headerNodes.some(p=>{let a=n.parent;while(a&&a!==content){if(a===p)return true;a=a.parent}return false}));
  for(const n of controls)if(!n.removed)reuse(n);
  for(const n of headerNodes)if(!n.removed)reuse(n);
  const frame=track(createAutoLayout('VERTICAL',{name:title,itemSpacing:0}));
  target.appendChild(frame);frame.resize(original.width,original.height);frame.fills=[];frame.clipsContent=true;
  frame.primaryAxisSizingMode='FIXED';frame.counterAxisSizingMode='FIXED';
  frame.appendChild(content);content.x=0;content.y=0;
  const num=parseInt(title,10)-OFFSET;
  frame.x=100+(num%4)*1640;frame.y=100+Math.floor(num/4)*1800;
  Object.assign(frame,{layoutSizingHorizontal:'FIXED',layoutSizingVertical:'FIXED'});
  screens.push({id:frame.id,title,w:frame.width,h:frame.height,texts:frame.findAllWithCriteria({types:['TEXT']}).length,instances:frame.findAllWithCriteria({types:['INSTANCE']}).length});
}
return {createdNodeIds:[...new Set(created)],mutatedNodeIds:[...new Set(mutated)],removedNodeIds:removed,screens,masterCount:masters.size,errors};

}
function createAutoLayout(direction,props={}){
  const n=figma.createFrame();n.layoutMode=direction;n.primaryAxisSizingMode='AUTO';n.counterAxisSizingMode='AUTO';
  Object.assign(n,props);return n;
}
async function loadFontsFor(root){
  const available=await figma.listAvailableFontsAsync();
  const known=new Set(available.map(f=>f.fontName.family+'|'+f.fontName.style));
  const wanted=new Map();
  for(const t of root.findAllWithCriteria({types:['TEXT']}))for(const s of t.getStyledTextSegments(['fontName']))wanted.set(s.fontName.family+'|'+s.fontName.style,{family:s.fontName.family,style:s.fontName.style});
  for(const [key,f]of wanted)if(known.has(key))await figma.loadFontAsync(f);
  for(const style of ['Regular','Bold'])await figma.loadFontAsync({family:'JetBrains Mono',style});
}
async function restoreTransparency(sourceId){
  const source=await figma.getNodeByIdAsync(sourceId),page=await figma.getNodeByIdAsync('8:4');
  if(!source)return;
  await loadFontsFor(source);
  const done=new Set();
  function copy(a,b){
    for(const field of ['fills','strokes'])if(field in a&&field in b&&Array.isArray(a[field]))b[field]=a[field];
    if(b.type==='INSTANCE'&&b.mainComponent&&!done.has(b.mainComponent.id)){done.add(b.mainComponent.id);copy(a,b.mainComponent)}
    if('children'in a&&'children'in b){if(a.children.length!==b.children.length)throw new Error('Cấu trúc màn hình đã thay đổi tại '+b.id);for(let i=0;i<a.children.length;i++)copy(a.children[i],b.children[i])}
  }
  for(const w of source.children[0].children){const s=w.name==='Section:margin'?w.children[0]:w;const name=s.children[0].findAllWithCriteria({types:['TEXT']})[0].characters;const target=page.children.find(n=>n.name===name);if(target)copy(s.children[1],target.children[0])}
}
async function addPrototype(pageId){
  const page=await figma.getNodeByIdAsync(pageId);await figma.setCurrentPageAsync(page);
  const screens=page.children.filter(n=>n.type==='FRAME'&&/^\d\d —/.test(n.name));
  const byNumber=new Map(screens.map(n=>[parseInt(n.name,10),n]));let total=0;
  const routes=new Map([['Câu hỏi',9],['Tri thức',10],['Thư viện',11],['Bảng xếp hạng',12],['Cá nhân',13],['Tùy chỉnh',14],['Tìm kiếm',15],['ĐẶT CÂU HỎI',16],['Base Inside',4],['Base Message',6],['Base Square',9],['Phân hệ Quản trị',8],['Nhóm dự án',7],['Tất cả',6],['Trang chủ',20],['Quản lý người dùng',21],['Phòng ban & Vai trò',22],['Kiểm duyệt bài viết',23],['Quản lý Hỏi đáp',24],['Quản lý tài liệu',25],['Nhật ký hệ thống',26],['Cài đặt hệ thống',27]]);
  for(const screen of screens){
    for(const t of screen.findAllWithCriteria({types:['TEXT']})){
      const label=t.characters.trim();let dest=routes.get(label);
      if(/Đăng nhập/.test(label)&&parseInt(screen.name,10)===1)dest=3;
      if(/Đăng xuất/.test(label))dest=1;
      if(label.startsWith('Quay lại danh sách câu hỏi'))dest=9;
      if(label.startsWith('Quay lại thư viện'))dest=11;
      if(label==='Knowledge Graph dùng để làm gì trong quản trị tri thức doanh nghiệp?')dest=17;
      if(label==='Khởi đầu với Knowledge Graph & Kiến trúc Tri thức Doanh nghiệp')dest=18;
      const target=byNumber.get(dest);if(!target||target===screen)continue;
      let clickable=t;
      for(let a=t.parent;a&&a!==screen;a=a.parent)if(a.name==='Button'||a.name==='Link'){clickable=a;break}
      const x=clickable.absoluteTransform[0][2]-screen.absoluteTransform[0][2],y=clickable.absoluteTransform[1][2]-screen.absoluteTransform[1][2];
      const key='Prototype / '+label;if(screen.children.some(n=>n.name===key))continue;
      const hot=figma.createRectangle();hot.name=key;screen.appendChild(hot);hot.layoutPositioning='ABSOLUTE';hot.x=x;hot.y=y;hot.resize(Math.max(16,clickable.width),Math.max(16,clickable.height));hot.fills=[{type:'SOLID',color:{r:0,g:0,b:0},opacity:0.001}];
      await hot.setReactionsAsync([{trigger:{type:'ON_CLICK'},actions:[{type:'NODE',destinationId:target.id,navigation:'NAVIGATE',transition:null,resetScrollPosition:true}]}]);total++;
    }
  }
  page.flowStartingPoints=[{nodeId:(byNumber.get(pageId==='8:4'?1:20)||screens[0]).id,name:pageId==='8:4'?'Luồng ứng dụng':'Luồng quản trị'}];
  return total;
}
async function organizeAndDocument(){
  const page=await figma.getNodeByIdAsync('0:1');await figma.setCurrentPageAsync(page);
  let x=100,y=1400,rowHeight=0;
  for(const c of page.findAllWithCriteria({types:['COMPONENT']})){
    if(x+c.width>6500){x=100;y+=rowHeight+64;rowHeight=0}c.x=x;c.y=y;x+=c.width+64;rowHeight=Math.max(rowHeight,c.height);
  }
  if(!page.children.some(n=>n.name==='Hướng dẫn bản thiết kế')){
    await figma.loadFontAsync({family:'Inter',style:'Regular'});await figma.loadFontAsync({family:'Inter',style:'Bold'});
    const cover=createAutoLayout('VERTICAL',{name:'Hướng dẫn bản thiết kế',itemSpacing:24,paddingTop:48,paddingBottom:48,paddingLeft:48,paddingRight:48});page.appendChild(cover);cover.x=100;cover.y=100;cover.resize(1300,1000);cover.counterAxisSizingMode='FIXED';cover.fills=[{type:'SOLID',color:{r:0.059,g:0.09,b:0.165}}];
    const lines=[['ĐATN / SOCIAL NETWORKS',40,'Bold'],['27 màn hình và trạng thái · Web & Quản trị',28,'Bold'],['01 — Ứng dụng Web: 19 màn hình/trạng thái từ code Vue hiện tại.\n02 — Quản trị: 8 trang HTML quản trị hiện có.\n00 — Hướng dẫn & Thành phần: các nút, trường nhập và điều hướng dùng chung.',20,'Regular'],['CHỈNH SỬA\nNhấp đúp để sửa chữ. Chọn một lớp để đổi màu, kích thước hoặc bố cục.\nThành phần có biểu tượng hình thoi là instance: dùng Go to main component để sửa phần gốc.',20,'Regular'],['XEM LUỒNG\nChọn màn hình Đăng nhập hoặc Trang chủ Quản trị rồi nhấn Present (▶).\nCác liên kết mô phỏng việc chuyển màn hình, không thực thi nghiệp vụ hay gọi API.',20,'Regular'],['NGUỒN & PHẠM VI\nBố cục từ code và các bản HTML đang có trong dự án, dùng dữ liệu mẫu của repository.\nBiểu tượng là vector; ảnh là tài nguyên nội dung riêng. Không có ảnh chụp toàn màn hình trong bản thiết kế.\nKiểu chữ monospace Menlo được thay bằng JetBrains Mono do giới hạn font.',17,'Regular']];
    for(const [text,size,style]of lines){const t=figma.createText();t.fontName={family:'Inter',style};t.characters=text;t.fontSize=size;t.lineHeight={unit:'PERCENT',value:150};t.fills=[{type:'SOLID',color:{r:1,g:1,b:1}}];cover.appendChild(t);t.textAutoResize='HEIGHT';t.resize(1204,t.height)}
  }
}
async function main(){
  if(figma.fileKey&&figma.fileKey!=='EDb4fkxGn3GtlDVuJ2flBg')throw new Error('Hãy chạy trong file ĐATN đã tạo, không phải file khác.');
  await restoreTransparency('11:2');await restoreTransparency('9:2');
  await compose('13:2','8:5',0,8,20);
  const totals=[];
  for(const id of ['8:4','8:5']){const page=await figma.getNodeByIdAsync(id);await figma.setCurrentPageAsync(page);const frames=page.children.filter(n=>n.type==='FRAME'&&/^\d\d —/.test(n.name));for(const f of frames){const fonts=f.findAllWithCriteria({types:['TEXT']}).filter(t=>t.hasMissingFont);if(fonts.length)throw new Error('Còn thiếu font tại '+f.name)}totals.push(frames.length)}
  if(totals[0]!==19||totals[1]!==8)throw new Error('Số màn hình chưa đủ: '+totals.join('/'));
  const links=await addPrototype('8:4')+await addPrototype('8:5');
  await organizeAndDocument();
  for(const id of ['2:2','3:2','9:2','11:2','13:2']){const n=await figma.getNodeByIdAsync(id);if(n)n.remove()}
  const page=await figma.getNodeByIdAsync('8:4');await figma.setCurrentPageAsync(page);figma.currentPage.selection=[await figma.getNodeByIdAsync('12:2680')];figma.viewport.scrollAndZoomIntoView(figma.currentPage.selection);
  figma.closePlugin('Đã hoàn tất 27 màn hình chỉnh sửa được và '+links+' liên kết trình diễn.');
}
main().catch(error=>figma.closePlugin('Chưa hoàn tất: '+error.message));
