# Installation and hosting

Use the Composer VCS instructions in the README or install the classic release ZIP.
Keep a recoverable previous extension and database version before an update.
TYPO3 13.4/14.3 and PHP 8.2+ are declared; respect the selected TYPO3 version's own
PHP constraints. Live editor/rendering acceptance currently targets TYPO3 14.3.5.

## Server images

The default `typo3` backend uses the image processor, binary path and processor
setting already configured in TYPO3. PDF decoding normally needs Ghostscript and
a host policy permitting PDF processing. PHP GD crops logical halves and encodes
WebP/JPEG/PNG. When a codec is missing, an available JPEG/PNG codec is selected.
When conversion fails, the reader falls back to local PDF.js rendering.

A host that deliberately disables PDF conversion can select **PDF direkt im Browser**.
Administrators can explicitly select the optional `poppler` backend. Configure
`pdfinfo` and `pdftoppm`, or let the extension find them on PATH. This is never
selected automatically to evade an ImageMagick PDF security policy.

Source files must be public FAL PDFs, local-processable, at most 100 MB and 500
physical PDF sheets. Non-public storage and arbitrary remote URLs are rejected.
Raster edges are bounded to 4000 px. Each converter has a 45-second timeout.

## Cache and pre-generation

Images are created on demand in `var/transient/pdf_flipper`. The cache and HMAC
URLs bind source SHA-256, physical sheet, crop half, backend, size, codec and quality.
Replacing the source invalidates its old image URLs; refresh cached content pages.
TYPO3's encryption key signs these URLs. The endpoint never accepts arbitrary paths.
Generated files remain private; the bounded endpoint streams them with their MIME,
ETag, `nosniff` and immutable cache headers. Per-PDF locks avoid duplicate conversions.

To avoid cold previews on the first visit:

```sh
vendor/bin/typo3 pdf-flipper:render 123
```

`123` is the content UID. This warms the complete PDF in standard and HD size,
even for a selected excerpt. Cold generation may take minutes for larger magazines;
allow enough CLI execution time. No scheduler, separate web service or AI is required.
Operational retention should remove obsolete preview cache files when appropriate;
this beta does not run a background pruning service.

## Support link

Set `supportUrl` in extension configuration to your exact HTTPS Buy-Me-a-Coffee
profile URL. Only `buymeacoffee.com/<profile>` URLs are accepted. Enable the support
field on the intended content element. Both settings are required. No payment is
initiated by the reader; the external site opens only after the link is clicked.

## Troubleshooting

- Direct PDF works, images fail: verify TYPO3 image processing, PDF/Ghostscript policy,
  GD codecs, writable transient directory and processor timeout.
- Wrong split: explicitly choose the PDF layout. All-landscape documents are treated
  as an outer cover spread followed by inside spreads in automatic mode.
- Number mismatch: **Heftseite** means logical leaf after splitting, with cover = 1;
  printed artwork labels can differ. Custom printed labels are not implemented.
- Old class missing after an upgrade: rebuild classic autoload information and
  clear compiled TYPO3 caches. Keep the extension folder name `pdf_flipper`.
- Source rights or privacy: do not use an excerpt to hide confidential pages.
