# Contributing

Report the TYPO3/PHP version, image processor, selected PDF layout and a minimal
publicly shareable example. Keep copyrighted or private magazines out of issues.
Use a synthetic PDF when possible. Say what happened and what you expected.

Run the JS tests, both PHP test scripts and PHP syntax checks. Preserve upstream
vendor bytes and licenses; use `Build/vendor.py` and its pinned lock to update them.
New PDF features need cases for portrait pages, inside spreads, separate covers,
selected excerpts, reduced motion and direct-PDF fallback.

Keep editorial controls in normal TYPO3 fields. Avoid site-specific file paths,
CDNs, hidden analytics, forced branding and unnecessary services. Scope changes
for review; include actual validation and remaining limits.

Contributions are supplied under GPL-2.0-or-later. No separate contributor agreement
is required. Security reports belong in the private process described in SECURITY.md.
