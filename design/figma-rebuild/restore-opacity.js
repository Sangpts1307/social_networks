const source=await figma.getNodeByIdAsync(SOURCE_ID),page=await figma.getNodeByIdAsync('8:4');
await figma.setCurrentPageAsync(page);
const fonts=await figma.listAvailableFontsAsync();
for(const f of fonts.filter(f=>['Inter','JetBrains Mono'].includes(f.fontName.family)))await figma.loadFontAsync(f.fontName);
const changed=new Set(),masterDone=new Set(),problems=[];
function restore(a,b){
  if(!a||!b)return;
  for(const prop of ['fills','strokes'])if(prop in a&&prop in b&&Array.isArray(a[prop])){b[prop]=a[prop];changed.add(b.id)}
  if(b.type==='INSTANCE'&&b.mainComponent&&!masterDone.has(b.mainComponent.id)){masterDone.add(b.mainComponent.id);restore(a,b.mainComponent)}
  if('children'in a&&'children'in b){if(a.children.length!==b.children.length)problems.push({a:a.id,b:b.id,ac:a.children.length,bc:b.children.length});for(let i=0;i<Math.min(a.children.length,b.children.length);i++)restore(a.children[i],b.children[i])}
}
const sections=source.children[0].children.map(w=>w.name==='Section:margin'?w.children[0]:w);
for(const s of sections.slice(START,END)){const title=s.children[0].findAllWithCriteria({types:['TEXT']})[0].characters;const target=page.children.find(n=>n.name===title);if(target)restore(s.children[1],target.children[0])}
return {mutatedNodeIds:[...changed],masterCount:masterDone.size,problems};
