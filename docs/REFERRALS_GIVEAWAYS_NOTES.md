# Referrals / Gewinnspiele Basis

Stand: ZIP 13

## Enthalten

- Persönlicher Referral-Link pro Nutzer
- Öffentliche Referral-Route `/ref/{code}`
- Klickzählung
- Registrierung über Referral-Code per Session
- Referral-Signup-Verknüpfung nach Registrierung
- Profilvollständigkeit als erfolgreicher Invite
- Aktives Giveaway-Modell
- Chancenberechnung:
  - 1 Basis-Chance für Registrierung
  - 1 Bonus-Chance für 100%-Profil
  - 1 Bonus-Chance für einen erfolgreichen Invite mit 100%-Profil
- Benachrichtigungen für Referral-Signup und erfolgreiches Referral
- XP-Hooks für Referral-Signup und erfolgreiches Referral

## Neue Tabellen

- `referral_links`
- `referral_signups`
- `giveaways`
- `giveaway_entries`

## Test

1. Als Nutzer einloggen.
2. `/referrals` öffnen.
3. Referral-Link kopieren.
4. In anderem Browser/Inkognito öffnen.
5. Registrieren.
6. Profil des eingeladenen Nutzers auf 100% bringen.
7. Beim Referrer `/referrals` prüfen.

## Hinweis

Die Admin-Verwaltung für Gewinnspiele ist noch nicht enthalten. ZIP 13 legt nur die saubere technische Grundlage.
