# Patch 154 - Laravel Profil-Cover API

## Inhalt

Ergänzt eine native API-Route für den Profil-Titelbild-Upload der Android-App.

Neue Route:

```text
POST /api/v1/me/cover
```

Multipart-Feld:

```text
cover
```

Erlaubte Dateitypen:

```text
jpg, jpeg, png, webp
```

## Umsetzung

- nutzt das vorhandene Upload-Limit `hunthub.upload_limits.profile_cover_kb`
- nutzt den bestehenden `MediaService`
- nutzt die bestehende Medienprüfung/Moderation mit Kontext `profile_cover`
- löscht ein vorhandenes altes Cover aus dem public Storage
- speichert das neue Cover in `users.cover_path`
- gibt wieder `user` und `counts` zurück, analog zu `/api/v1/me`

## Geänderte Dateien

- `routes/api.php`
- `app/Http/Controllers/Api/V1/Auth/ApiAuthController.php`

Keine Migration.
Kein Composer-Update.
