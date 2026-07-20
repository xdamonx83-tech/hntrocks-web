# HNT.ROCKS Mobile Web Templates

The web application supports independent desktop and mobile Blade templates while keeping the same routes, controllers, validation and backend actions.

## View locations

- Desktop: `resources/views/themes/hnt_preview/`
- Mobile web: `resources/views/themes/hnt_mobile/`
- Desktop assets: `public/assets/themes/hnt_preview/`
- Mobile assets: `public/assets/themes/hnt_mobile/`

A desktop view such as:

```text
themes.hnt_preview.cups.index
```

is automatically matched with:

```text
themes.hnt_mobile.cups.index
```

The same conversion applies to current `themes.socialite.*` and `themes.rework.*` views. If the mobile view does not exist, the existing desktop view is rendered unchanged.

Route-specific exceptions belong in `config/hnt_view.php` under `route_views`.

## Testing and persistent switching

Temporary query switches work on every normal page:

```text
?mobile=1
?desktop=1
?view=auto
```

The selected preference is stored in the encrypted `hnt_view_mode` cookie.

Permanent switch routes preserve a local return path:

```text
/view/mobile?return=/feed
/view/desktop?return=/feed
/view/auto?return=/feed
```

`auto` uses `Sec-CH-UA-Mobile` when available and falls back to mobile user-agent detection.

## Debug response headers

Normal HTML responses expose:

```text
X-HNT-View-Preference: auto|desktop|mobile
X-HNT-View-Mode: desktop|mobile
X-HNT-Mobile-Template: loaded|desktop-fallback
```

`desktop-fallback` is expected until a matching mobile Blade file exists.

## Rules for mobile templates

Mobile templates may change layout, navigation, sheets and modal presentation. They must continue using the existing route names, CSRF forms, authorization checks, controller data and server actions. Business logic must not be duplicated in JavaScript or in a second controller solely for mobile presentation.

JSON, AJAX, fragment and dashboard-header requests are never template-swapped.
