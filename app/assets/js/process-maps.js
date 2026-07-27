(() => {
  'use strict';
  const shells = document.querySelectorAll('[data-process-map-shell]');
  shells.forEach((shell) => {
    const svg = shell.querySelector('[data-process-svg]');
    if (!svg) return;
    const base = svg.viewBox.baseVal;
    const original = { x: base.x, y: base.y, width: base.width, height: base.height };
    let zoom = 1;
    const applyZoom = () => {
      const width = original.width / zoom;
      const height = original.height / zoom;
      const x = original.x + (original.width - width) / 2;
      const y = original.y + (original.height - height) / 2;
      svg.setAttribute('viewBox', `${x} ${y} ${width} ${height}`);
    };
    shell.querySelector('[data-map-zoom="in"]')?.addEventListener('click', () => { zoom = Math.min(2.5, zoom + 0.2); applyZoom(); });
    shell.querySelector('[data-map-zoom="out"]')?.addEventListener('click', () => { zoom = Math.max(0.55, zoom - 0.2); applyZoom(); });
    shell.querySelector('[data-map-fit]')?.addEventListener('click', () => { zoom = 1; svg.setAttribute('viewBox', `${original.x} ${original.y} ${original.width} ${original.height}`); });
    shell.querySelector('[data-map-fullscreen]')?.addEventListener('click', async () => {
      if (document.fullscreenElement) await document.exitFullscreen(); else await shell.requestFullscreen();
    });
  });

  const inspector = document.querySelector('[data-process-inspector]');
  const inspect = (node) => {
    if (!inspector || !node) return;
    const put = (name, value) => { const target = inspector.querySelector(`[data-inspector-${name}]`); if (target) target.textContent = value || '—'; };
    const title = inspector.querySelector('[data-inspector-title]');
    if (title) title.textContent = node.dataset.stepName || 'Process step';
    put('type', node.dataset.stepType);
    put('status', node.dataset.stepStatus);
    put('permission', node.dataset.permission);
    put('sla', Number(node.dataset.sla || 0) ? `${node.dataset.sla} minutes` : 'No timed SLA');
    put('evidence', node.dataset.evidence);
    put('record', node.dataset.recordType);
  };
  document.querySelectorAll('[data-process-node]').forEach((node) => {
    node.addEventListener('click', () => inspect(node));
    node.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); inspect(node); } });
  });

  const svg = document.querySelector('[data-process-svg][data-designer="1"]');
  const layoutInput = document.querySelector('[data-layout-json]');
  if (svg && layoutInput) {
    const positions = {};
    const point = svg.createSVGPoint();
    let active = null;
    let offset = { x: 0, y: 0 };
    const svgPoint = (event) => {
      point.x = event.clientX; point.y = event.clientY;
      return point.matrixTransform(svg.getScreenCTM().inverse());
    };
    svg.querySelectorAll('[data-process-node][data-draggable="true"]').forEach((node) => {
      node.addEventListener('pointerdown', (event) => {
        active = node; active.setPointerCapture(event.pointerId);
        const p = svgPoint(event);
        const transform = active.transform.baseVal.consolidate()?.matrix;
        offset = { x: p.x - (transform?.e || 0), y: p.y - (transform?.f || 0) };
      });
      node.addEventListener('pointermove', (event) => {
        if (active !== node) return;
        const p = svgPoint(event); const x = Math.max(190, Math.min(1800, Math.round(p.x - offset.x))); const y = Math.max(40, Math.min(1200, Math.round(p.y - offset.y)));
        node.setAttribute('transform', `translate(${x} ${y})`);
        positions[node.dataset.stepId] = { x, y };
        layoutInput.value = JSON.stringify(positions);
      });
      node.addEventListener('pointerup', () => { active = null; });
      node.addEventListener('pointercancel', () => { active = null; });
    });
  }

  document.querySelectorAll('[data-palette-type]').forEach((button) => {
    button.addEventListener('click', () => {
      const type = button.dataset.paletteType || 'task';
      window.alert(`New ${type} nodes are created through a governed draft version. Select a draft process before adding structural steps.`);
    });
  });
})();
