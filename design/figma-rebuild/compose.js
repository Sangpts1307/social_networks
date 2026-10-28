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
  const frame=track(figma.createAutoLayout('VERTICAL',{name:title,itemSpacing:0}));
  target.appendChild(frame);frame.resize(original.width,original.height);frame.fills=[];frame.clipsContent=true;
  frame.primaryAxisSizingMode='FIXED';frame.counterAxisSizingMode='FIXED';
  frame.appendChild(content);content.x=0;content.y=0;
  const num=parseInt(title,10)-OFFSET;
  frame.x=100+(num%4)*1640;frame.y=100+Math.floor(num/4)*1800;
  frame.set({layoutSizingHorizontal:'FIXED',layoutSizingVertical:'FIXED'});
  screens.push({id:frame.id,title,w:frame.width,h:frame.height,texts:frame.findAllWithCriteria({types:['TEXT']}).length,instances:frame.findAllWithCriteria({types:['INSTANCE']}).length});
}
return {createdNodeIds:[...new Set(created)],mutatedNodeIds:[...new Set(mutated)],removedNodeIds:removed,screens,masterCount:masters.size,errors};
