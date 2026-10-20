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
