(() => {
  const toast = document.getElementById('landingToast');
  let toastTimer = null;

  const showToast = (message) => {
    if (!toast || !message) return;
    toast.textContent = message;
    toast.classList.add('show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('show'), 1700);
  };

  const navLinks = [...document.querySelectorAll('.landing-nav a[href^="#"]')];
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
})();
