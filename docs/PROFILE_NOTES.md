# 04 Profil-Basis

Dieser Schritt ergänzt die erste echte Profilgrundlage.

## Enthalten

- `user_profiles` Tabelle
- `UserProfile` Model
- Beziehung `User -> profile`
- Profilanzeige `/profile`
- interne Profilroute `/u/{username}`
- Profilbearbeitung `/profile/edit`
- Avatar-Upload
- Cover-Upload
- Profilfortschritt
- Basisfelder für spätere LFG-/Team-/Mitgliederfilter

## Nach dem Einspielen

```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
```

Falls Uploads nicht sichtbar sind:

```bash
rm -rf public/storage
php artisan storage:link
```

Danach wieder Rechte prüfen:

```bash
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```
