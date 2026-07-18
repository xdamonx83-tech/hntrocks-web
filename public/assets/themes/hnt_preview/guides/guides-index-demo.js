(() => {
  const root = document.querySelector('.guides-demo-index');
  if (!root) return;

  const cards = [...document.querySelectorAll('[data-guide-card]')];
  const grid = document.getElementById('guideGrid');
  const empty = document.getElementById('guideEmpty');
  const count = document.getElementById('guideVisibleCount');
  const search = document.getElementById('guideSearch');
  const language = document.getElementById('guideLanguage');
  const platform = document.getElementById('guidePlatform');
  const difficulty = document.getElementById('guideDifficulty');
  const sort = document.getElementById('guideSort');
  let category = 'all';

  const toast = (message) => {
    const target = document.querySelector('[data-guide-toast]');
    if (!target) return;
    target.textContent = message;
    target.classList.add('show', 'is-visible');
    clearTimeout(target.demoTimer);
    target.demoTimer = setTimeout(() => target.classList.remove('show', 'is-visible'), 2400);
  };

  const applyFilters = () => {
    const query = (search?.value || '').trim().toLowerCase();
    const visible = [];

    cards.forEach((card) => {
      const matches =
        (category === 'all' || card.dataset.category === category) &&
        (!query || (card.dataset.search || '').toLowerCase().includes(query)) &&
        (!language || language.value === 'all' || card.dataset.language === language.value) &&
        (!platform || platform.value === 'all' || card.dataset.platform === 'all' || card.dataset.platform === platform.value) &&
        (!difficulty || difficulty.value === 'all' || card.dataset.difficulty === difficulty.value);

      card.hidden = !matches;
      if (matches) visible.push(card);
    });

    const key = sort?.value || 'new';
    visible.sort((a, b) => key === 'helpful'
      ? Number(b.dataset.helpful) - Number(a.dataset.helpful)
      : key === 'popular'
        ? Number(b.dataset.popular) - Number(a.dataset.popular)
        : Number(a.dataset.date) - Number(b.dataset.date));

    visible.forEach((card) => grid?.insertBefore(card, empty));
    if (count) count.textContent = String(visible.length);
    empty?.classList.toggle('show', visible.length === 0);
  };

  document.querySelectorAll('[data-guide-category]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-guide-category]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      category = button.dataset.guideCategory || 'all';
      applyFilters();
    });
  });

  [search, language, platform, difficulty, sort].forEach((control) => {
    control?.addEventListener('input', applyFilters);
    control?.addEventListener('change', applyFilters);
  });

  document.querySelectorAll('[data-guide-save]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      button.classList.toggle('saved');
      button.querySelector('i')?.classList.toggle('ph-fill');
      toast(button.classList.contains('saved') ? 'Guide als Demo gespeichert' : 'Aus Gespeichert entfernt');
    });
  });

  document.querySelectorAll('[data-demo-guide-link]').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (link.getAttribute('href') !== '#') return;
      event.preventDefault();
      toast('Dieser Demo-Guide wird im nächsten Schritt mit echten Inhalten verknüpft.');
    });
  });

  applyFilters();
})();