# Feature decisions — 20 October 2026

This is a bounded review of vendors' own published features, not a benchmark or
an exhaustive market survey. No claim is made that the return trail is a new invention.

| Product / source | Relevant features described by the vendor | Decision here |
|---|---|---|
| [DearFlip](https://js.dearflip.com/page-modes-and-customizations/) | Automatic, single and double views; normal and booklet variants for single pages | Keep layout choices separate from effects |
| [Heyzine](https://heyzine.com/) | Magazine/book turns, slider, coverflow, single-page flip, sharing and offline output | Add paper/slide/immediate plus scroll; leave coverflow out |
| [3D FlipBook](https://3dflipbook.net/) | Interactive corners, PDF bookmarks, thumbnails, PDF search, RTL, skins, sound and media layers | Add useful navigation and corner controls; defer RTL; skip sound/media layers |
| [FlippingBook](https://flippingbook.com/features) | Table of contents, thumbnails, search, notes and distribution/engagement tools | Add local navigation; omit tracking and lead capture |

Our priority is returning to useful content: share one page, find a passage, jump
elsewhere, then return without losing the previous place. A local bookmark is
explicit and does not require registration. These are convenience choices, not
novelty claims.

## Implemented in 0.3

Paper turns, horizontal slide, immediate switch, continuous scroll; corner sensitivity
and dwell delay; overview; source-PDF text search and outline; exact-page links;
explicit local bookmarks; a bounded session return trail. Native TYPO3 configuration,
local images, original PDF fallback and selected-page handling remain central.

## Deliberately omitted

Autoplay, page-turn sound, decorative coverflow, AI summaries, engagement tracking,
lead forms and a forced payment overlay. They add distraction, dependencies or
privacy cost without helping the main magazine-reading task.

## Remaining work

A full fresh Composer/editor installation acceptance on each declared TYPO3 version,
physical touch/hover and screen-reader acceptance, translations, RTL, a selectable
text/annotation layer, optional custom chapter records and printed page labels.
TER/Packagist registration is separate from the public GitHub beta.
