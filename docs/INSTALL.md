# Hunthub Laravel Installation

## Webroot

Die Domain/Subdomain muss auf diesen Ordner zeigen:

```text
/home/users/hunthub/www/social.hunthub.online/public
```

Nicht auf den Projektordner selbst.

## Nach dem Upload

```bash
cd /home/users/hunthub/www/social.hunthub.online
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan optimize:clear
```

Falls `public/storage` bereits existiert, ist die Meldung bei `php artisan storage:link` nicht kritisch. Bei Bedarf:

```bash
rm -rf public/storage
php artisan storage:link
```

## Rechte nach Root-Arbeit

Wenn Dateien als root hochgeladen oder Composer als root ausgeführt wurde:

```bash
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

## Seed-Testnutzer optional

```bash
php artisan db:seed
```

Testnutzer:

- E-Mail: `admin@hunthub.local`
- Benutzername: `admin`
- Passwort: `ChangeMe123!`

Nicht öffentlich so stehen lassen.
