# Changelog

## 0.3.0-beta.1 — first public beta

- Project name **knthlz PDF Flipper**, Composer `knthlz/pdf-flipper`, namespace `Knthlz`.
- Independent paper/slide/immediate effects and continuous scroll view.
- Configurable corner sensitivity, delay, turn duration and click behavior.
- Page overview, local PDF search, source outline, exact-page links, explicit
  local bookmarks and a return trail for navigation jumps.
- Optional plain support link, disabled by default.
- Public documentation, contribution/security guidance, CI and reproducible releases.

Private 0.1/0.2 prototypes introduced native FAL editing, page selections, mixed
spreads/covers, image generation, quality/colors/control settings and direct PDF fallback.

### Upgrade from private prototypes

The CType `pdf_flipper` and database fields remain. Replace the complete extension,
update the schema and rebuild classic autoload/compiled caches for the new namespace.
Existing explicit control selections are retained; enable new tools in the native
checkbox list where desired.
