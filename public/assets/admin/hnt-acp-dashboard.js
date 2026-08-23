(() => {
  const dataNode = document.getElementById('hntAcpMemberGrowthData');
  const chart = document.querySelector('.hnt-acp-growth-chart');
  if (!dataNode || !chart) return;
  let payload;
  try {
    payload = JSON.parse(dataNode.textContent || '{}');
  } catch (_) {
    return;
  }
  const svg = chart.querySelector('svg');
  const grid = svg?.querySelector('[data-growth-grid]');
  const labelsGroup = svg?.querySelector('[data-growth-labels]');
  const pointsGroup = svg?.querySelector('[data-growth-points]');
  const currentLine = svg?.querySelector('[data-growth-line="current"]');
  const previousLine = svg?.querySelector('[data-growth-line="previous"]');
  const currentArea = svg?.querySelector('[data-growth-area="current"]');
  const previousArea = svg?.querySelector('[data-growth-area="previous"]');
  const tooltip = chart.querySelector('[data-growth-tooltip]');
  const buttons = [...document.querySelectorAll('[data-growth-mode]')];
  if (!svg || !grid || !labelsGroup || !pointsGroup || !currentLine || !previousLine || !currentArea || !previousArea) return;
  const NS = 'http://www.w3.org/2000/svg';
  const bounds = { left: 48, right: 746, top: 20, bottom: 190 };
  const createSvg = (tag, attrs = {}) => {
    const node = document.createElementNS(NS, tag);
    Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, String(value)));
    return node;
  };
  const linePath = (points) => points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`).join(' ');
  const areaPath = (points) => points.length
    ? `${linePath(points)} L ${points[points.length - 1].x.toFixed(2)} ${bounds.bottom} L ${points[0].x.toFixed(2)} ${bounds.bottom} Z`
    : '';
  const render = (mode) => {
    const source = payload?.[mode] || payload?.daily || {};
    const labels = Array.isArray(source.labels) ? source.labels : [];
    const current = Array.isArray(source.current) ? source.current.map(Number) : [];
    const previous = Array.isArray(source.previous) ? source.previous.map(Number) : [];
    const count = Math.max(labels.length, current.length, previous.length);
    if (!count) return;
    const maxValue = Math.max(1, ...current, ...previous);
    const roundedMax = Math.max(2, Math.ceil(maxValue / 2) * 2);
    const width = bounds.right - bounds.left;
    const height = bounds.bottom - bounds.top;
    const xAt = (index) => count <= 1 ? bounds.left : bounds.left + (index / (count - 1)) * width;
    const yAt = (value) => bounds.bottom - (Math.max(0, value) / roundedMax) * height;
    const currentPoints = Array.from({ length: count }, (_, index) => ({ x: xAt(index), y: yAt(current[index] || 0), value: current[index] || 0, index }));
    const previousPoints = Array.from({ length: count }, (_, index) => ({ x: xAt(index), y: yAt(previous[index] || 0), value: previous[index] || 0, index }));
    currentLine.setAttribute('d', linePath(currentPoints));
    previousLine.setAttribute('d', linePath(previousPoints));
    currentArea.setAttribute('d', areaPath(currentPoints));
    previousArea.setAttribute('d', areaPath(previousPoints));
    grid.replaceChildren();
    labelsGroup.replaceChildren();
    pointsGroup.replaceChildren();
    [1, .66, .33, 0].forEach((ratio) => {
      const y = bounds.bottom - ratio * height;
      grid.appendChild(createSvg('line', { x1: bounds.left, x2: bounds.right, y1: y, y2: y, class: 'hnt-acp-chart-grid-line' }));
      const text = createSvg('text', { x: 0, y: y + 4, class: 'hnt-acp-chart-grid-label' });
      text.textContent = String(Math.round(roundedMax * ratio));
      grid.appendChild(text);
    });
    const wantedLabels = mode === 'monthly' ? 6 : 6;
    const labelStep = Math.max(1, Math.floor(count / wantedLabels));
    labels.forEach((label, index) => {
      if (index !== 0 && index !== count - 1 && index % labelStep !== 0) return;
      const text = createSvg('text', { x: xAt(index), y: 214, class: 'hnt-acp-chart-x-label' });
      text.textContent = label;
      labelsGroup.appendChild(text);
    });
    const addPoint = (point, series, color) => {
      const circle = createSvg('circle', {
        cx: point.x,
        cy: point.y,
        r: 4.2,
        fill: color,
        class: 'hnt-acp-chart-point',
        'data-index': point.index,
        'data-series': series,
      });
      const title = createSvg('title');
      title.textContent = `${labels[point.index] || ''}: ${point.value}`;
      circle.appendChild(title);
      pointsGroup.appendChild(circle);
    };
    currentPoints.forEach((point) => addPoint(point, 'current', '#FA2256'));
    previousPoints.forEach((point) => addPoint(point, 'previous', '#246CF9'));
    buttons.forEach((button) => button.classList.toggle('is-active', button.dataset.growthMode === mode));
  };
  buttons.forEach((button) => {
    button.addEventListener('click', () => render(button.dataset.growthMode === 'monthly' ? 'monthly' : 'daily'));
  });
  pointsGroup.addEventListener('pointermove', (event) => {
    const point = event.target.closest?.('.hnt-acp-chart-point');
    if (!point || !tooltip) return;
    const mode = buttons.find((button) => button.classList.contains('is-active'))?.dataset.growthMode || 'daily';
    const source = payload?.[mode] || {};
    const index = Number(point.dataset.index || 0);
    const currentValue = Number(source.current?.[index] || 0);
    const previousValue = Number(source.previous?.[index] || 0);
    tooltip.innerHTML = `<strong>${source.labels?.[index] || ''}</strong><span>Aktuell: ${currentValue}<br>Vorperiode: ${previousValue}</span>`;
    tooltip.hidden = false;
    const rect = chart.getBoundingClientRect();
    tooltip.style.left = `${Math.min(rect.width - 160, Math.max(8, event.clientX - rect.left + 12))}px`;
    tooltip.style.top = `${Math.max(8, event.clientY - rect.top - 70)}px`;
  });
  pointsGroup.addEventListener('pointerleave', () => {
    if (tooltip) tooltip.hidden = true;
  });
  render('daily');
})();
