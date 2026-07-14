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
<symbol id="i-search" viewbox="0 0 24 24"><circle cx="11" cy="11" r="6.8"></circle><path d="m16.2 16.2 4 4"></path></symbol>
<symbol id="i-plus" viewbox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></symbol>
<symbol id="i-sliders" viewbox="0 0 24 24"><path d="M4 7h9M17 7h3M4 17h3M11 17h9M13 4v6M8 14v6"></path></symbol>
<symbol id="i-export" viewbox="0 0 24 24"><path d="M12 3v11M8 7l4-4 4 4"></path><path d="M5 13v6h14v-6"></path></symbol>
<symbol id="i-settings" viewbox="0 0 24 24"><circle cx="12" cy="12" r="3.1"></circle><path d="M19 13.6v-3.2l-2-.7-.7-1.7.9-1.9-2.3-2.3-1.9.9-1.7-.7-.7-2H8.4l-.7 2-1.7.7-1.9-.9-2.3 2.3.9 1.9-.7 1.7-2 .7v3.2l2 .7.7 1.7-.9 1.9 2.3 2.3 1.9-.9 1.7.7.7 2h3.2l.7-2 1.7-.7 1.9.9 2.3-2.3-.9-1.9.7-1.7z"></path></symbol>
<symbol id="i-bell" viewbox="0 0 24 24"><path d="M6 9a6 6 0 0 1 12 0c0 7 3 6 3 8H3c0-2 3-1 3-8"></path><path d="M9.5 20h5"></path></symbol>
<symbol id="i-user" viewbox="0 0 24 24"><circle cx="12" cy="8" r="3.4"></circle><path d="M5.5 20a6.5 6.5 0 0 1 13 0"></path></symbol>
<symbol id="i-arrow" viewbox="0 0 24 24"><path d="M7 17 17 7M9 7h8v8"></path></symbol>
<symbol id="i-briefcase" viewbox="0 0 24 24"><rect height="12" rx="3" width="18" x="3" y="7"></rect><path d="M9 7V5h6v2M3 12h18M10 12v2h4v-2"></path></symbol>
<symbol id="i-phone" viewbox="0 0 24 24"><path d="M7.4 3.5 4.6 5.2c-.8.5-.9 1.5-.6 2.4 2.1 6.1 6.3 10.3 12.4 12.4.9.3 1.9.2 2.4-.6l1.7-2.8-4-3-1.8 2.1c-2.6-1-5.4-3.8-6.4-6.4l2.1-1.8z"></path></symbol>
<symbol id="i-chevron" viewbox="0 0 24 24"><path d="m8 10 4 4 4-4"></path></symbol>
<symbol id="i-printer" viewbox="0 0 24 24"><path d="M7 9V4h10v5M7 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M7 14h10v7H7z"></path></symbol>
<symbol id="i-users" viewbox="0 0 24 24"><circle cx="9" cy="8.5" r="3"></circle><circle cx="17" cy="9.5" r="2.3"></circle><path d="M3 19a6 6 0 0 1 12 0M14 18a4.5 4.5 0 0 1 7 0"></path></symbol>
<symbol id="i-folder" viewbox="0 0 24 24"><path d="M3 7h7l2 2h9v10H3z"></path><path d="M3 7V5h7l2 2"></path></symbol>
<symbol id="i-check" viewbox="0 0 24 24"><path d="m6 12 4 4 8-8"></path></symbol>
<symbol id="i-male" viewbox="0 0 24 24"><circle cx="10" cy="14" r="5"></circle><path d="m14 10 6-6M15 4h5v5"></path></symbol>
<symbol id="i-female" viewbox="0 0 24 24"><circle cx="12" cy="9" r="5"></circle><path d="M12 14v7M9 18h6"></path></symbol>
<symbol id="i-heart" viewbox="0 0 24 24"><path d="M20.8 4.9a5.5 5.5 0 0 0-7.8 0L12 5.9l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.3 1-1a5.5 5.5 0 0 0 0-7.8Z"></path></symbol>
<symbol id="i-comment" viewbox="0 0 24 24"><path d="M21 12a8 8 0 0 1-8 8H5l-3 2 1-5a8 8 0 1 1 18-5Z"></path></symbol>
<symbol id="i-bookmark" viewbox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4z"></path></symbol>
<symbol id="i-share" viewbox="0 0 24 24"><circle cx="18" cy="5" r="2.5"></circle><circle cx="6" cy="12" r="2.5"></circle><circle cx="18" cy="19" r="2.5"></circle><path d="m8.2 10.8 7.6-4.5M8.2 13.2l7.6 4.5"></path></symbol>
<symbol id="i-more" viewbox="0 0 24 24"><circle cx="5" cy="12" r="1.2"></circle><circle cx="12" cy="12" r="1.2"></circle><circle cx="19" cy="12" r="1.2"></circle></symbol>
<symbol id="i-image" viewbox="0 0 24 24"><rect height="16" rx="3" width="18" x="3" y="4"></rect><circle cx="9" cy="10" r="2"></circle><path d="m5 18 5-5 3 3 2-2 4 4"></path></symbol>
<symbol id="i-x" viewbox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"></path></symbol>
<symbol id="i-send" viewbox="0 0 24 24"><path d="m3 11 18-8-7 18-3-7z"></path><path d="m11 14 4-4"></path></symbol>
<symbol id="i-smile" viewbox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 14.5a4.5 4.5 0 0 0 7 0M9 9h.01M15 9h.01"></path></symbol>
<symbol id="i-reply" viewbox="0 0 24 24"><path d="m9 7-6 5 6 5v-3h4c4 0 6 2 8 5-.5-6-3-9-8-9H9z"></path></symbol>
<symbol id="i-eye" viewbox="0 0 24 24">
<path d="M2.8 12s3.3-6 9.2-6 9.2 6 9.2 6-3.3 6-9.2 6-9.2-6-9.2-6"></path>
<circle cx="12" cy="12" r="2.6"></circle>
</symbol><symbol id="i-eye-off" viewbox="0 0 24 24">
<path d="m4 4 16 16"></path>
<path d="M10.4 6.2A9.6 9.6 0 0 1 12 6c5.9 0 9.2 6 9.2 6a15.2 15.2 0 0 1-2.5 3.2"></path>
<path d="M6.2 7.2A15 15 0 0 0 2.8 12s3.3 6 9.2 6c1 0 1.9-.2 2.7-.4"></path>
<path d="M9.8 9.8a3.1 3.1 0 0 0 4.4 4.4"></path>
</symbol><symbol id="i-arrow-left" viewbox="0 0 24 24">
<path d="M19 12H5"></path>
<path d="m10 7-5 5 5 5"></path>
</symbol><symbol id="i-shield" viewbox="0 0 24 24">
<path d="M12 3 19 6v5c0 4.5-2.8 8-7 10-4.2-2-7-5.5-7-10V6l7-3Z"></path>
<path d="m9 12 2 2 4-4"></path>
</symbol><symbol id="i-key" viewbox="0 0 24 24">
<circle cx="8" cy="12" r="4"></circle>
<path d="m12 12 8-8"></path>
<path d="m17 7 2 2"></path>
<path d="m15 9 2 2"></path>
</symbol></svg>

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
        <input id="loginPassword" type="password" autocomplete="current-password"
          placeholder="••••••••••••" value="hunter123">
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
    <button type="button" data-auth-toast="Apple-Anmeldung als Demo geöffnet">
      <span class="auth-apple">●</span> Apple
    </button>
    <button type="button" data-auth-toast="Google-Anmeldung als Demo geöffnet">
      <span class="auth-google">G</span> Google
    </button>
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
