# Patch 137: Android App Links

Flutter now declares verified Android App Links for `https://hnt.rocks` with package name `rocks.hnt.app`.

The web side still needs a valid Digital Asset Links file at:

```text
public/.well-known/assetlinks.json
```

Do not publish this file with a placeholder fingerprint. The required value is the SHA-256 certificate fingerprint of the Android App Signing Certificate from Google Play Console > App integrity.

Expected JSON once the real Play App Signing SHA-256 value is known:

```json
[
  {
    "relation": [
      "delegate_permission/common.handle_all_urls"
    ],
    "target": {
      "namespace": "android_app",
      "package_name": "rocks.hnt.app",
      "sha256_cert_fingerprints": [
        "REPLACE_WITH_REAL_PLAY_APP_SIGNING_SHA256"
      ]
    }
  }
]
```

Until the real fingerprint is available, `assetlinks.json` is intentionally not committed so production does not advertise invalid Android App Link verification data.
