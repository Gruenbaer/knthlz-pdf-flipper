/** Logical magazine pages are independent of physical PDF sheets and selected pages. */
export function mapPages(sizes, layout = 'auto') {
  if (!sizes.length || sizes.length > 10000 || sizes.some(s => !Number.isFinite(s.width) || !Number.isFinite(s.height) || s.width <= 0 || s.height <= 0)) throw new Error('Ungültige PDF-Seitenmaße.');
  if (!['auto', 'single', 'mixed', 'spreads', 'cover-spread'].includes(layout)) throw new Error('Unbekannter PDF-Aufbau.');
  const wide = s => s.width / s.height > 1.2;
  const wrap = layout === 'cover-spread' || (layout === 'auto' && sizes.every(wide));
  const parts = [];
  const add = (index, half = null) => parts.push({pdfPage: index + 1, half, width: sizes[index].width / (half ? 2 : 1), height: sizes[index].height});
  if (wrap) {
    if (!wide(sizes[0])) throw new Error('Der Umschlag muss eine Doppelseite sein.');
    add(0, 'right');
    for (let i = 1; i < sizes.length; i++) { add(i, 'left'); add(i, 'right'); }
    add(0, 'left');
  } else {
    sizes.forEach((s, i) => {
      const split = layout === 'spreads' || ((layout === 'mixed' || layout === 'auto') && wide(s) && (layout !== 'mixed' || (i > 0 && i < sizes.length - 1)));
      if (split) { add(i, 'left'); add(i, 'right'); } else add(i);
    });
  }
  return parts.map((p, i) => ({...p, number: i + 1}));
}

export function parsePages(input, count) {
  const value = String(input || '').trim().replace(/[–—]/g, '-');
  if (!value) return [];
  if (value.length > 1024) throw new Error('Die Seitenauswahl ist zu lang.');
  const result = new Set();
  for (const part of value.split(',')) {
    const match = /^\s*([1-9]\d{0,4})(?:\s*-\s*([1-9]\d{0,4}))?\s*$/.exec(part);
    if (!match) throw new Error('Ungültige Seitenauswahl. Beispiel: 3–8, 12.');
    const start = Number(match[1]), end = Number(match[2] || match[1]);
    if (end < start || end > count || end > 10000) throw new Error(`Seitenauswahl außerhalb des Hefts (1–${count}).`);
    for (let n = start; n <= end; n++) result.add(n);
  }
  return [...result].sort((a, b) => a - b);
}

export function selectPages(all, relevant, mode = 'highlight') {
  const selected = new Set(relevant);
  return all.filter(p => mode !== 'only' || !selected.size || selected.has(p.number)).map(p => ({...p, relevant: selected.has(p.number)}));
}
