# Phase 31 – Referrals 500 Fix

Fixes a Blade syntax error in `resources/views/referrals/index.blade.php`.

Problem:
Two ternary expressions had an incomplete empty string branch:

```blade
{{ $summary['profile'] <= 0 ? 'is-locked' : ' }}
{{ $summary['referral'] <= 0 ? 'is-locked' : ' }}
```

Fix:

```blade
{{ $summary['profile'] <= 0 ? 'is-locked' : '' }}
{{ $summary['referral'] <= 0 ? 'is-locked' : '' }}
```

No migration is required.
