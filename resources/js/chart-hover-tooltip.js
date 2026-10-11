// One presentation-only tooltip for all analytics charts. Chart.js callbacks own the values.
let card;
let activeCanvas;
let contentKey = '';

function hide() {
    if (card) card.hidden = true;
    activeCanvas = null;
}

function element(tag, className, text) {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = text;
    return node;
}

function render({ chart, tooltip }) {
    const canvas = chart.canvas;
    if (!canvas?.isConnected || !tooltip || tooltip.opacity === 0) {
        if (activeCanvas === canvas) hide();
        return;
    }
    if (!card) {
        card = element('div', 'analytics-hover-tooltip', '');
        card.setAttribute('role', 'tooltip');
        card.id = 'analyticsHoverTooltip';
        card.hidden = true;
        document.body.appendChild(card);
    }

    const rows = (tooltip.body || []).flatMap((body, index) =>
        [...(body.before || []), ...(body.lines || []), ...(body.after || [])].map(text => ({
            text: String(text),
            color: tooltip.labelColors?.[index]?.backgroundColor,
        }))
    );
    const title = (tooltip.title || []).join(' · ');
    const scope = canvas.dataset.tooltipScope || '';
    const key = JSON.stringify([title, scope, rows, tooltip.footer]);
    if (key !== contentKey) {
        card.replaceChildren(element('div', 'analytics-hover-tooltip__title', title));
        if (scope) card.appendChild(element('div', 'analytics-hover-tooltip__subtitle', scope));
        rows.forEach(({ text, color }) => {
            const row = element('div', 'analytics-hover-tooltip__row', '');
            const marker = element('span', 'analytics-hover-tooltip__marker', '');
            if (typeof color === 'string' && CSS.supports('color', color)) marker.style.backgroundColor = color;
            marker.setAttribute('aria-hidden', 'true');
            const separator = text.indexOf(':');
            row.append(marker, element('span', 'analytics-hover-tooltip__label', separator < 0 ? text : text.slice(0, separator)));
            if (separator >= 0) row.appendChild(element('strong', 'analytics-hover-tooltip__value', text.slice(separator + 1).trim()));
            card.appendChild(row);
        });
        (tooltip.footer || []).forEach(text => card.appendChild(element('div', 'analytics-hover-tooltip__subtitle', text)));
        contentKey = key;
    }

    activeCanvas = canvas;
    card.hidden = false;
    const rect = canvas.getBoundingClientRect();
    const width = document.documentElement.clientWidth;
    const height = window.innerHeight;
    const x = rect.left + tooltip.caretX * rect.width / chart.width;
    const y = rect.top + tooltip.caretY * rect.height / chart.height;
    const cardRect = card.getBoundingClientRect();
    const left = Math.max(12, Math.min(x - cardRect.width / 2, width - cardRect.width - 12));
    const preferredTop = y - cardRect.height - 12;
    const top = Math.max(12, Math.min(preferredTop >= 12 ? preferredTop : y + 12, height - cardRect.height - 12));
    card.style.left = `${left}px`;
    card.style.top = `${top}px`;
}

document.addEventListener('scroll', hide, { capture: true, passive: true });
document.addEventListener('pointerdown', hide, { passive: true });
document.addEventListener('pointerout', event => {
    if (event.target === activeCanvas && event.relatedTarget !== activeCanvas) hide();
}, { passive: true });
document.addEventListener('keydown', event => { if (event.key === 'Escape') hide(); });
document.addEventListener('panel-ui:reset', hide);
window.addEventListener('resize', hide, { passive: true });
window.addEventListener('pagehide', hide);

window.SabanganChartHoverTooltip = { render, hide };
