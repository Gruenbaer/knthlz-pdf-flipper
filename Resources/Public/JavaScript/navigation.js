/** Pure navigation helpers, shared by the reader and its tests. */
export function pageFromFragment(fragment, contentId) {
  const match = /^#pf-(\d+)-p(\d{1,5})$/.exec(fragment);
  return match && Number(match[1]) === Number(contentId) && Number(match[2]) > 0 ? Number(match[2]) : null;
}
export function cornerZone(pageWidth, height, sensitivity) {
  if (sensitivity <= 0) return 0;
  return Math.min(pageWidth * .45, Math.max(16, Math.min(pageWidth, height) * (.04 + Math.min(100, sensitivity) * .0028)));
}
export function visiblePages(index, count, double, cover) {
  return [index, ...(double && !(cover && index === 0) ? [index + 1] : [])].filter(i => i >= 0 && i < count);
}
export function selectedLeavesForSheet(pages, physicalPage) {
  return pages.filter(page => page.pdfPage === physicalPage).map(page => page.number);
}
export function plainSearchText(items) {
  return items.map(item => typeof item.str === 'string' ? item.str + (item.hasEOL ? '\n' : ' ') : '').join('').normalize('NFKC').toLocaleLowerCase('de');
}
export function bookmarkPage(raw, availablePages) {
  try {
    const value = JSON.parse(raw);
    return Number.isInteger(value.page) && availablePages.includes(value.page) ? value.page : null;
  } catch { return null; }
}

// One sizing axis only. The container always wins over a requested fixed size.
export function readerDimensions(container, ratio, mode, axis = 'auto', value = 800) {
  const available = Math.max(1, Math.floor(Number(container) || 1));
  const width = axis === 'width' ? Math.min(available, Math.max(200, Math.min(3000, Number(value) || 800))) : available;
  const single = ['read','single','scroll'].includes(mode) || (mode !== 'double' && width < 640);
  const leaves = single ? 1 : 2;
  const naturalHeight = width / (ratio * leaves);
  const height = axis === 'height' ? Math.min(naturalHeight, Math.max(200, Math.min(3000, Number(value) || 800))) : naturalHeight;
  return {height, bookWidth: Math.min(width, height * ratio * leaves), single};
}

/** Turn only after a deliberate outward drag at a zoomed horizontal edge. */
export function zoomEdgeTurn({dx, dy, left, maximum, edgeDistance}) {
  if (Math.abs(dx) < 70 || Math.abs(dx) < Math.abs(dy) * 1.4 || edgeDistance < 70) return 0;
  if (dx < 0 && left >= maximum - 2) return 1;
  if (dx > 0 && left <= 2) return -1;
  return 0;
}
