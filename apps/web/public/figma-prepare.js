window.prepareFigmaCapture = async function () {
  await Promise.race([document.fonts.ready,new Promise(resolve=>setTimeout(resolve,8000))]);
  await Promise.all([...document.querySelectorAll('.material-symbols-outlined')].map(async el => {
    const name=el.textContent.trim();
    if(!/^[a-z_0-9]+$/.test(name))return;
    try {
      const response=await fetch('/figma-icons/'+name+'.svg');
      if(!response.ok)return;
      const svg=await response.text();if(!svg.includes('<svg'))return;
      const size=getComputedStyle(el).fontSize;
      el.textContent='';el.innerHTML=svg;
      el.style.width=size;el.style.height=size;el.style.minWidth=size;el.style.fontSize=size;el.style.lineHeight='1';
      el.style.display='inline-flex';el.style.alignItems='center';el.style.justifyContent='center';
      el.dataset.figmaIcon=name;
      const child=el.querySelector('svg');child.style.width='100%';child.style.height='100%';child.style.fill='currentColor';
      child.setAttribute('aria-label','icon:'+name);
    }catch(e){}
  }));
  await Promise.race([Promise.all([...document.images].map(img=>img.decode().catch(()=>{}))),new Promise(resolve=>setTimeout(resolve,8000))]);
  const script=document.createElement('script');script.src='https://mcp.figma.com/mcp/html-to-design/capture.js';script.async=true;document.head.appendChild(script);
};
