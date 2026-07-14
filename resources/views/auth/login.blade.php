<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>HNT.ROCKS — Anmelden</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/common.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/auth.css') }}">
</head>
<body data-page="auth-login">
<svg aria-hidden="true" class="svg-defs">
  <symbol id="i-check" viewBox="0 0 24 24"><path d="m6 12 4 4 8-8"></path></symbol>
  <symbol id="i-x" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"></path></symbol>
  <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2.8 12s3.3-6 9.2-6 9.2 6 9.2 6-3.3 6-9.2 6-9.2-6-9.2-6"></path><circle cx="12" cy="12" r="2.6"></circle></symbol>
  <symbol id="i-eye-off" viewBox="0 0 24 24"><path d="m4 4 16 16"></path><path d="M10.4 6.2A9.6 9.6 0 0 1 12 6c5.9 0 9.2 6 9.2 6a15.2 15.2 0 0 1-2.5 3.2"></path><path d="M6.2 7.2A15 15 0 0 0 2.8 12s3.3 6 9.2 6c1 0 1.9-.2 2.7-.4"></path><path d="M9.8 9.8a3.1 3.1 0 0 0 4.4 4.4"></path></symbol>
</svg>

<main class="auth-shell">
  <section class="auth-frame">
    <section class="auth-left">
      <header class="auth-header">
        <a class="auth-brand" href="#" data-auth-toast="HNT.ROCKS Startseite wird später angebunden" aria-label="HNT.ROCKS">
          <img src="{{ asset('assets/themes/hnt_preview/images/hnt-brand-logo.svg') }}" alt="" aria-hidden="true">
          <span>HNT.ROCKS</span>
        </a>
        <a class="auth-mobile-close" href="#" data-auth-toast="Schließen wird später angebunden" aria-label="Schließen">
          <svg><use href="#i-x"></use></svg>
        </a>
      </header>

      <div class="auth-center">
        <div class="auth-content">
          <header class="auth-heading">
            <h1>Anmelden</h1>
            <p>Melde dich an und kehre direkt zu deiner Community zurück.</p>
          </header>

          <form class="auth-form" id="loginForm" novalidate>
            <label class="auth-field">
              <span>E-Mail oder Benutzername</span>
              <div><input id="loginIdentity" autocomplete="username" placeholder="name@beispiel.de" value="valentina"></div>
              <small data-error-for="loginIdentity"></small>
            </label>

            <label class="auth-field">
              <span>Passwort</span>
              <div>
                <input id="loginPassword" type="password" autocomplete="current-password" placeholder="••••••••••••" value="hunter123">
                <button type="button" data-password-toggle="loginPassword" aria-label="Passwort anzeigen">
                  <svg><use href="#i-eye"></use></svg>
                </button>
              </div>
              <small data-error-for="loginPassword"></small>
            </label>

            <div class="auth-options">
              <label class="auth-check">
                <input id="rememberLogin" type="checkbox" checked>
                <i><svg><use href="#i-check"></use></svg></i>
                <span>Angemeldet bleiben</span>
              </label>
              <button type="button" data-auth-toast="Passwort-Reset als Demo angefordert">Passwort vergessen?</button>
            </div>

            <button class="auth-primary" type="submit">Anmelden</button>

            <div class="auth-divider"><span>oder weiter mit</span></div>

            <div class="auth-social">
              <button type="button" data-auth-toast="Apple-Anmeldung als Demo geöffnet"><span class="auth-apple">●</span> Apple</button>
              <button type="button" data-auth-toast="Google-Anmeldung als Demo geöffnet"><span class="auth-google">G</span> Google</button>
            </div>
          </form>
        </div>
      </div>

      <footer class="auth-footer">
        <div><span>Noch kein Konto?</span> <a href="#" data-auth-toast="Registrierung wird später angebunden">Jetzt registrieren</a></div>
        <nav><a href="#" data-auth-toast="Datenschutz wird später angebunden">Datenschutz</a><a href="#" data-auth-toast="Impressum wird später angebunden">Impressum</a></nav>
      </footer>
    </section>

    <aside class="auth-photo" aria-label="Community Vorschau">
      <img src="{{ asset('assets/themes/hnt_preview/auth-demo/auth-reference-panel.jpg') }}" alt="HNT.ROCKS Community Motiv">
      <a class="auth-photo-close" href="#" data-auth-toast="Schließen wird später angebunden" aria-label="Schließen"></a>
    </aside>
  </section>

  <div class="toast" id="toast"></div>
</main>

<script src="{{ asset('assets/themes/hnt_preview/auth-demo/app.js') }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/auth-demo/auth.js') }}"></script>
</body>
</html>