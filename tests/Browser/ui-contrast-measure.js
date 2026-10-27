function (selector) {
    const parse = value => {
        const n = value.match(/[\d.]+/g)?.map(Number) || [];
        if (value.startsWith('color(srgb')) return [n[0]*255,n[1]*255,n[2]*255,n[3] ?? 1];
        return /^rgba?\(/.test(value) ? [n[0],n[1],n[2],n[3] ?? 1] : null;
    };
    const over = (a,b) => [0,1,2].map(i=>a[i]*a[3]+b[i]*(1-a[3])).concat(1);
    const luminance = c => c.slice(0,3).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((a,v,i)=>a+v*[.2126,.7152,.0722][i],0);
    const samples=[];
    for (const e of document.querySelectorAll(selector)) {
        if (!e.checkVisibility({checkOpacity:true,checkVisibilityCSS:true}) || e.closest('[hidden],[inert]')) continue;
        const details=e.closest('details:not([open])');if(details&&!e.closest('summary'))continue;
        const icon=e.matches('i.bi,.topbar-notification-card__icon');
        const text=[...e.childNodes].filter(n=>n.nodeType===3).map(n=>n.textContent).join('').trim() || (icon ? '[icon]' : '');if(!text)continue;
        let chain=[],uncertain=false,p=e;
        while(p){let s=getComputedStyle(p),c=parse(s.backgroundColor);if(!c||s.opacity!=='1'||s.filter!=='none')uncertain=true;chain.unshift({c:c||[0,0,0,0],image:s.backgroundImage!=='none'});p=p.parentElement;}
        let bg=[255,255,255,1],image=false;for(const v of chain){if(v.c[3]===1)image=false;bg=over(v.c,bg);if(v.image)image=true;}
        const s=getComputedStyle(e),fg=parse(s.color);if(!fg)continue;const a=luminance(over(fg,bg)),b=luminance(bg);
        samples.push({text:text.slice(0,90),class:e.className,color:s.color,background:bg.slice(0,3).map(Math.round),ratio:+((Math.max(a,b)+.05)/(Math.min(a,b)+.05)).toFixed(2),threshold:icon?3:(parseFloat(s.fontSize)>=24||(parseFloat(s.fontSize)>=18.66&&parseFloat(s.fontWeight)>=700)?3:4.5),uncertain:uncertain||image,disabled:e.matches(':disabled')});
    }
    return {url:location.href,theme:document.documentElement.dataset.theme,width:innerWidth,overflow:document.documentElement.scrollWidth>innerWidth+1,samples};
}
