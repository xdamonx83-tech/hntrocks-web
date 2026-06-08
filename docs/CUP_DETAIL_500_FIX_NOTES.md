# Patch 64: Cup Detail 500 Fix

Fixes a Blade compile error in `resources/views/cups/show.blade.php` introduced by an inline `@php ... @endphp` expression inside JavaScript.

The affected line now uses a safe Blade echo for the translated loading text.

No migration required.
