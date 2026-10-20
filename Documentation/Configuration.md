
## Global defaults, presets and local overrides (0.4)

Settings are resolved in this order: built-in safe defaults → global `default_*`
values → selected preset → explicitly checked content-element overrides.
The configuration lives in TYPO3 **Settings → Extension Configuration → pdf_flipper**.
It is persistent and versionable through the normal TYPO3 configuration mechanism.
Both frontend output and `pdf-flipper:render` use the same resolver.

- New content elements choose **Globaler Standard** (`inherit`). `defaultPreset`
  selects the global template: `standard`, `frameless`, `quiet`, `compact`, or a
  custom key. Changing a global value affects inheriting readers on the next
  uncached render. Flush the page cache after configuration changes.
- Editors can choose another preset and check only the fields they need to change.
  Save after choosing a preset or changing the override list. Other fields show their effective inherited values read-only. Explicit zero,
  disabled support and an empty button list remain valid local overrides.
- Existing 0.1–0.3 records use **Bisherige CE-Einstellungen** (`legacy`) after the
  database update. Their values stay local until an editor chooses inheritance.
  Database/import-created records should set `tx_pdfflipper_preset=inherit`
  explicitly when inheritance is intended. No mass migration is performed.
- PDF file, relevant pages, excerpt selection, physical PDF layout and initial
  page remain document-specific; they are never shared through a visual preset.

Administrators can define or replace named templates with the optional `presets`
JSON configuration. This is data, with no executable code or external requests:

```json
{"school":{"label":"Schule","settings":{"frame":"frameless","effect":"slide","accent":"#00718e","corner":20}}}
```

Allowed settings keys are `format`, `quality`, `width`, `hdwidth`, `background`,
`buttonbg`, `buttoncolor`, `reading`, `effect`, `corner`, `delay`, `duration`,
`clickmode`, `support`, `buttons`, `accent`, `frame`, `sizeaxis`, `sizevalue`.
Global fields have the same names prefixed with `default_`. `buttons` is a
comma-separated list. Unknown fields are ignored; malformed preset JSON falls
back to built-in templates. Unknown template keys use global values. The
configuration and CE values pass the same bounds/color/enum validation.

## Frame and size

`frame=frameless` removes the outer border, padding and background. Controls,
keyboard focus indicators, page shadows and the original PDF link stay available.
Fullscreen uses an opaque background for legibility.

`sizeaxis=auto` is the default: the reader follows its container, preserves the
PDF aspect ratio and uses single pages in narrow automatic layouts.
Alternatively choose **one** axis: `width` caps the reader width, or `height`
sets the leaf height. `sizevalue` is 200–3000 CSS pixels. A narrow container
always limits the result, including a requested fixed height. Height excludes
the toolbar. Both dimensions cannot be specified at once; zoom may deliberately
pan inside the reader. Image `width` / `hdwidth` are independent raster resolution
settings, not CSS display dimensions.
