# Malas Favicon — Usage Guide

Option 4 (M + Book), monochrome: `#1f2937` / `#9ca3af` / `#f3f4f6` (+ `#6b7280` accent dot).

## Files
- `favicon.svg` — source, scalable, transparent bg (use as primary favicon)
- `favicon-dark.svg` — inverted palette for dark-mode contexts (e.g. `prefers-color-scheme`)
- `favicon-16.png`, `favicon-32.png`, `favicon-64.png` — browser tab / bookmarks
- `favicon-128.png`, `favicon-180.png` (apple-touch-icon), `favicon-192.png`, `favicon-512.png` (PWA manifest)

## Blade (resources/views/layouts/app.blade.php or similar)

```blade
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/favicon-180.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
```

## Dark-mode variant (optional, via media query)

```blade
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}"
      media="(prefers-color-scheme: light)">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon-dark.svg') }}"
      media="(prefers-color-scheme: dark)">
```

## Laravel setup
Copy the `favicon/` folder into `public/` so paths resolve via `asset('favicon/...')`.

## site.webmanifest (optional, for PWA/mobile home-screen icon)

```json
{
  "icons": [
    { "src": "/favicon/favicon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/favicon/favicon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```
