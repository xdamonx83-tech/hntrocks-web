# HNT.rocks Rework Layout Rules

This file is a guardrail for the Rework theme. Do not invent page-local navigation, headers or right widgets.

## Hard rules

1. Rework pages use the central left sidebar partial:

   ```blade
   @include('themes.rework.partials.sidebar')
   ```

2. Rework pages use the central top header partial:

   ```blade
   @include('themes.rework.partials.topbar')
   ```

3. Rework pages with the normal three-column shell use the central right widget partial:

   ```blade
   @include('themes.rework.partials.right-widgets')
   ```

4. Do not copy/paste or recreate these blocks inside page templates:

   - `<aside class="sidebar">`
   - `<header class="topbar">`
   - `<aside class="right-col">`

5. Maps are the accepted exception. Maps can keep their own fullscreen/special layout and must not be forced into the normal right-widget column.

6. New Rework pages should follow the existing shell shape until a full Blade layout exists:

   ```blade
   <div class="app">
     @include('themes.rework.partials.sidebar')
     <main class="main">
       @include('themes.rework.partials.topbar')
       <section class="content-grid">
         <div class="left-col">
           ...
         </div>
         @include('themes.rework.partials.right-widgets')
       </section>
     </main>
   </div>
   ```

## Current central files

- `resources/views/themes/rework/partials/sidebar.blade.php`
- `resources/views/themes/rework/partials/topbar.blade.php`
- `resources/views/themes/rework/partials/right-widgets.blade.php`

## Before committing layout work

Run:

```bash
bash scripts/check_rework_layout.sh
```

The check should fail when someone adds page-local sidebar, topbar or right widget markup instead of using the central partials.
