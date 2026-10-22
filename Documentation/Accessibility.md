# Accessibility and privacy

The reader provides named controls, keyboard navigation, a calm reading mode,
reduced-motion handling and an original-PDF link. Corner hover is suppressed for
coarse pointers, reduced motion and zoom/pan. Native HTML remains the appropriate
way to publish accessible editorial text. Canvas page images do not provide a
complete text equivalent. This beta has no WCAG/BITV certification.

PDF text search is not OCR. It can fail on scans, unusual font mappings or reading
order. Extracted snippets are inserted as text, never as trusted HTML. Interactive
PDF annotation links, a selectable text layer and custom printed page labels are
not implemented. Source PDFs must be checked separately for accessibility.

No reader analytics or remote conversion requests are built in. Files, fonts,
CMaps and WASM assets are loaded from the same site. Standard web-server logs and
any site-level analytics remain the hosting site's responsibility.

A bookmark writes only the selected logical page and timestamp to localStorage
when the reader clicks **Hier merken**. It is scoped to the PDF source hash (or its
public path in direct mode), stays in that browser and can be removed through
**Lesezeichen löschen**. It does not sync to an account. The in-session return trail
is not persisted. View/effect/corner choices are not stored.

The support option loads no external script, image or payment widget. Clicking its
plain link navigates to the configured external Buy-Me-a-Coffee profile. Site owners
should account for that link and explicit local bookmarking in their own privacy information.

## Keyboard checks, 22 October 2026

The labelled page containers have explicit group roles. When focus is inside the enlarged scrollable page area, arrow keys and Home/End retain native scrolling. Use the toolbar to turn pages while zoomed; the reader region also retains its page-turn shortcuts. Live checks confirmed horizontal panning without a page change and page turning without losing zoom. This is a focused functional check, not a screen-reader or conformance certification.
