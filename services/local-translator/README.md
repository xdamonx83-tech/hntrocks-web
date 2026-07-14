# HNT.ROCKS local translator

Small localhost-only German/English translation service based on Argos Translate.

## Endpoints

- `GET http://127.0.0.1:8787/health`
- `POST http://127.0.0.1:8787/translate`

Example request:

```json
{
  "q": "Gute Jagd euch allen!",
  "source": "de",
  "target": "en"
}
```

## Laravel switches

```env
HH_TRANSLATION_PROVIDER=local_first
HH_TRANSLATION_LOCAL_URL=http://127.0.0.1:8787/translate
HH_TRANSLATION_LOCAL_TIMEOUT=12
HH_TRANSLATION_OPENAI_FALLBACK=true
```

Rollback to the previous behavior:

```env
HH_TRANSLATION_PROVIDER=openai
```

Already cached post/comment translations stay in the database and are not translated again.
