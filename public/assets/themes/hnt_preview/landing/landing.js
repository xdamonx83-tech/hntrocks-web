(() => {
  if (!document.querySelector('link[data-landing-header-parity]')) {
    const parityStyle = document.createElement('link');
    parityStyle.rel = 'stylesheet';
    parityStyle.href = '/assets/themes/hnt_preview/landing/landing-header-parity.css?v=20260720-1';
    parityStyle.dataset.landingHeaderParity = '1';
    document.head.appendChild(parityStyle);
  }

  const toast = document.getElementById('landingToast');
  let toastTimer = null;

  const showToast = (message) => {
    if (!toast || !message) return;
    toast.textContent = message;
    toast.classList.add('show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('show'), 1700);
  };

  const isEnglish = document.documentElement.lang === 'en';
  const guestAnchors = isEnglish
    ? [
        ['#start', 'Start'],
        ['#community', 'Community'],
        ['#lfg', 'LFG'],
        ['#moments', 'Moments'],
        ['#cups', 'Cups'],
        ['#teams', 'Teams'],
        ['#more', 'More'],
      ]
    : [
        ['#start', 'Start'],
        ['#community', 'Community'],
        ['#lfg', 'LFG'],
        ['#moments', 'Moments'],
        ['#cups', 'Cups'],
        ['#teams', 'Teams'],
        ['#more', 'Mehr'],
      ];

  const allNavLinks = [...document.querySelectorAll('.landing-nav a')];
  allNavLinks.forEach((link, index) => {
    const item = guestAnchors[index];
    if (!item) return;
    link.href = item[0];
    link.textContent = item[1];
    link.dataset.scrollspy = '';
  });

  const navLinks = [...document.querySelectorAll('.landing-nav a[data-scrollspy][href^="#"]')];
  const sections = navLinks
    .map((link) => document.querySelector(link.getAttribute('href')))
    .filter(Boolean);

  const updateNavigation = () => {
    const marker = window.scrollY + Math.min(220, window.innerHeight * 0.26);
    let current = sections[0] || null;

    sections.forEach((section) => {
      if (section.offsetTop <= marker) current = section;
    });

    navLinks.forEach((link) => {
      link.classList.toggle('active', Boolean(current) && link.getAttribute('href') === `#${current.id}`);
    });
  };

  window.addEventListener('scroll', updateNavigation, { passive: true });
  window.addEventListener('resize', updateNavigation, { passive: true });
  updateNavigation();

  document.querySelectorAll('.landing-tabs button').forEach((button) => {
    button.addEventListener('click', () => {
      button.parentElement?.querySelectorAll('button').forEach((candidate) => candidate.classList.remove('active'));
      button.classList.add('active');
      showToast(button.textContent.trim());
    });
  });

  document.querySelectorAll('.map-dot').forEach((button) => {
    button.addEventListener('click', () => showToast(button.textContent.trim()));
  });

  document.querySelectorAll('video[data-moment-preview]').forEach((video) => {
    const revealFrame = () => {
      if (!Number.isFinite(video.duration) || video.duration <= 0) return;
      try {
        video.currentTime = Math.min(0.35, Math.max(0.05, video.duration * 0.03));
      } catch (_) {
        // Der Browser kann das Setzen vor dem ersten seekable-Frame ablehnen.
      }
    };

    if (video.readyState >= 1) revealFrame();
    else video.addEventListener('loadedmetadata', revealFrame, { once: true });
  });
})();
