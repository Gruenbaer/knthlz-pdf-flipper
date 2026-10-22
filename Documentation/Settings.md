# Settings

| Group | Setting | Default / range |
|---|---|---|
| Document | Public FAL PDF | Exactly one PDF |
| Selection | Relevant logical leaves | Empty = complete book; e.g. `3–8, 12` |
| Selection | Highlight / excerpt | Highlight keeps the complete magazine |
| Layout | PDF sheet structure | Auto; single, spreads, mixed covers, cover-spread overrides |
| Images | WebP / JPEG / PNG / direct PDF | WebP |
| Images | Quality | 85; 1–100 for WebP/JPEG; PNG remains lossless |
| Images | Preview / HD leaf width | 800 / 1600; 320–1200 / 640–2000 px |
| Reading | Auto / single / double / quiet / scroll | Auto |
| Motion | Paper / slide / immediate | Paper |
| Motion | Corner sensitivity | 35; 0–100, zero disables hover preview |
| Motion | Corner dwell delay | 120 ms; 0–600 ms |
| Motion | Animation duration | 650 ms; 150–1500 ms |
| Mouse | Page click / corners and drag / controls only | Page click |
| Colors | Accent, background, button background/text | Editable hex colors |
| Controls | Main controls and reading tools | Native checkbox list |
| Support | Show configured donation link | Off |

Reading layout and turn effect are independent. Quiet reading and continuous scroll
suppress paper/slide animation. Reduced-motion preferences suppress animated turns
and corner hover. At zoom > 1, dragging pans the page. Continuing a horizontal drag at a page edge
by at least 70 px turns the page on release, retaining zoom. A click on the visible
outer edge also turns; vertical scrolling never does. Controls-only and continuous
scroll modes suppress these gestures. Keyboard navigation and buttons remain available. Coarse pointers do not trigger corner hover.

The page overview uses lazy standard previews; direct-PDF mode shows a numbered grid.
Text search and PDF outline extraction load the original PDF only when requested.
Search has a minimum of two characters and shows at most 30 physical-sheet results.
It stays within the selected excerpt. A search result in a spread identifies both
logical leaves because locating a specific word in a crop requires more than plain
text matching. The source PDF may contain no outline or extractable text.

Exact-page links have the form `#pf-123-p12`, where 123 is the content UID and 12
the logical magazine leaf. They apply only to that reader and its selected pages.
A direct page link takes precedence over the configured start. Local bookmarks are
an explicit convenience; they do not silently override links or the start page.

## Global defaults and presets (0.4)

[Configuration and inheritance](Configuration.md) documents persistent global defaults, named presets, explicit content overrides, frameless mode and single-axis sizing. When enabling a local override, its initial value comes from the current effective preset. Explicit API/import values remain intact.
