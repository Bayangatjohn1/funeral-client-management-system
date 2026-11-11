const panels=new Map();
window.positionReportDatePanel=(panel,anchor)=>{
    const rect=anchor.closest('.worklist-filter-panel')?.getBoundingClientRect()||anchor.getBoundingClientRect();
    const width=Math.min(352,innerWidth-24),right=rect.right+8,left=right+width<=innerWidth-12?right:Math.max(12,rect.left-width-8);
    Object.assign(panel.style,{position:'fixed',inset:'auto',margin:'0',width:width+'px',left:left+'px',top:Math.max(12,Math.min(anchor.getBoundingClientRect().top,innerHeight-panel.offsetHeight-12))+'px'});
};
window.openReportDatePicker=(panel,anchor)=>{
    if(!panel||!anchor)return;for(const [old]of panels)if(!old.isConnected)panels.delete(old);
    panel.showPopover();panels.set(panel,anchor);
    if(anchor.dataset.appliedRange&&!panel.dataset.rangeCloseBound){panel.dataset.rangeCloseBound='1';panel.addEventListener('toggle',()=>{if(!panel.matches(':popover-open'))anchor.value=anchor.dataset.appliedRange;});}
    window.positionReportDatePanel(panel,anchor);panel.querySelector('input')?.focus({preventScroll:true});
};
document.addEventListener('pointerdown',event=>{for(const [panel,anchor] of panels)if(panel.matches(':popover-open')&&!panel.contains(event.target)&&!anchor.contains(event.target)&&!event.target.closest('.filter-select-menu'))panel.hidePopover();});
document.addEventListener('keydown',event=>{if(event.key==='Escape')for(const [panel,anchor]of panels)if(panel.matches(':popover-open')){panel.hidePopover();anchor.focus({preventScroll:true});}});
window.addEventListener('resize',()=>{for(const [panel,anchor]of panels)if(panel.matches(':popover-open'))window.positionReportDatePanel(panel,anchor);});
