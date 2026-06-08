# Patch 487: Global Cup Feedback Center

Adds a private global Cup Feedback Center for post-cup surveys, improvement ideas and ticket-style feedback.

## User page

- `/cup-feedback`
- Requires login.
- Lets users choose a specific Cup or submit general Cup feedback.
- Stores structured ratings, quick option checkboxes and a private free-text ticket.
- Feedback is not public.

## Admin page

- `/admin/cup-feedback`
- Admin-only via existing admin guard.
- Filter by status, category and Cup.
- Update status and internal admin note.

## Database

New table: `cup_feedback_entries`

Run:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan view:clear
```

If routes/classes are cached, also run:

```bash
composer dump-autoload
php artisan route:clear
php artisan optimize:clear
php artisan view:clear
```
