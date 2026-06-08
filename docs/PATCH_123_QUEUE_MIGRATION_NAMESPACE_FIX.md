# Patch 123 — Queue Migration Namespace Fix

Fixes the migration import in:

`database/migrations/2026_05_10_000122_create_queue_tables_for_feed_video_transcoding.php`

The original file used:

```php
use Illuminate\Database\Migration;
```

Correct Laravel namespace is:

```php
use Illuminate\Database\Migrations\Migration;
```

No schema logic changed. No composer dependency changed. No mail or web functionality changed.

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 123_hnt_queue_migration_namespace_fix.zip -d .
php artisan migrate
php artisan config:clear
php artisan optimize:clear
```

If the migration prompt appears because the app is in production, confirm with `yes`.
