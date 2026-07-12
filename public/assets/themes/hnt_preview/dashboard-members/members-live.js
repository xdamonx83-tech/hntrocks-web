(() => {
  const rows = [...document.querySelectorAll('[data-member-row]')];
  const search = document.getElementById('membersSearch');
  const platform = document.getElementById('membersPlatformFilter');
  const playstyle = document.getElementById('membersPlaystyleFilter');
  const region = document.getElementById('membersRegionFilter');
  const language = document.getElementById('membersLanguageFilter');
  const ready = document.getElementById('membersReadyFilter');
  const reset = document.getElementById('membersResetFilters');
  const tabs = [...document.querySelectorAll('[data-relationship-filter]')];
  const empty = document.getElementById('membersEmptyState');
  const resultCount = document.getElementById('membersResultCount');

  let relationship = 'all';
  let readyOnly = false;

  const normalize = (value) => (value || '').toLocaleLowerCase('de');

  const applyMembersFilters = () => {
    const term = normalize(search?.value);
    let visible = 0;

    rows.forEach((row) => {
      const matchesSearch = !term || normalize(row.dataset.name).includes(term);
      const matchesPlatform = !platform?.value || row.dataset.platform === platform.value;
      const matchesPlaystyle = !playstyle?.value || row.dataset.playstyle === playstyle.value;
      const matchesRegion = !region?.value || row.dataset.region === region.value;
      const matchesLanguage = !language?.value || row.dataset.language === language.value;
      const matchesRelationship = relationship === 'all' || row.dataset.relationship === relationship;
      const matchesReady = !readyOnly || ['ready', 'open'].includes(row.dataset.lfg);
      const show = matchesSearch && matchesPlatform && matchesPlaystyle && matchesRegion && matchesLanguage && matchesRelationship && matchesReady;

      row.hidden = !show;
      if (show) visible += 1;
    });

    if (empty) empty.hidden = visible !== 0;
    if (resultCount) resultCount.textContent = `${visible} ${visible === 1 ? 'Mitglied' : 'Mitglieder'} angezeigt`;
  };

  [search, platform, playstyle, region, language].forEach((control) => {
    control?.addEventListener(control === search ? 'input' : 'change', applyMembersFilters);
  });

  tabs.forEach((button) => {
    button.addEventListener('click', () => {
      tabs.forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      relationship = button.dataset.relationshipFilter || 'all';
      applyMembersFilters();
    });
  });

  ready?.addEventListener('click', () => {
    readyOnly = !readyOnly;
    ready.classList.toggle('active', readyOnly);
    ready.setAttribute('aria-pressed', String(readyOnly));
    applyMembersFilters();
  });

  reset?.addEventListener('click', () => {
    if (search) search.value = '';
    if (platform) platform.value = '';
    if (playstyle) playstyle.value = '';
    if (region) region.value = '';
    if (language) language.value = '';
    readyOnly = false;
    ready?.classList.remove('active');
    ready?.setAttribute('aria-pressed', 'false');
    relationship = 'all';
    tabs.forEach((item) => item.classList.toggle('active', item.dataset.relationshipFilter === 'all'));
    applyMembersFilters();
  });

  rows.forEach((row) => {
    row.addEventListener('click', (event) => {
      if (event.target.closest('button')) return;
      rows.forEach((item) => item.classList.remove('is-highlighted'));
      row.classList.add('is-highlighted');
    });
  });

  document.querySelectorAll('.members-pagination button').forEach((button) => {
    button.addEventListener('click', () => {
      if (/^\d+$/.test(button.textContent.trim())) {
        document.querySelectorAll('.members-pagination button').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
      }
      if (typeof window.showToast === 'function') window.showToast(`Demo-Seite ${button.textContent.trim()}`);
    });
  });

  const filterStrip = document.getElementById('membersFilterStrip');
  const mobileBackdrop = document.getElementById('membersMobileFilterBackdrop');
  const mobileMount = document.getElementById('membersMobileFilterMount');
  const mobileTrigger = document.getElementById('mobileMembersFiltersTrigger');
  const desktopTrigger = document.getElementById('membersFilterButton');
  const mobileClose = document.getElementById('membersMobileFilterClose');
  const stripHome = filterStrip?.parentElement;
  const stripNextSibling = filterStrip?.nextElementSibling;

  const openMobileFilters = () => {
    if (!filterStrip || !mobileBackdrop || !mobileMount) return;
    mobileMount.appendChild(filterStrip);
    mobileBackdrop.classList.add('is-open');
    mobileBackdrop.setAttribute('aria-hidden', 'false');
    document.body.classList.add('members-filter-open');
  };

  const closeMobileFilters = () => {
    if (!filterStrip || !mobileBackdrop || !stripHome) return;
    if (stripNextSibling) stripHome.insertBefore(filterStrip, stripNextSibling);
    else stripHome.appendChild(filterStrip);
    mobileBackdrop.classList.remove('is-open');
    mobileBackdrop.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('members-filter-open');
  };

  const handleFilterTrigger = () => {
    if (window.matchMedia('(max-width: 899px)').matches) openMobileFilters();
    else filterStrip?.classList.toggle('is-collapsed');
  };

  mobileTrigger?.addEventListener('click', openMobileFilters);
  desktopTrigger?.addEventListener('click', handleFilterTrigger);
  mobileClose?.addEventListener('click', closeMobileFilters);
  mobileBackdrop?.addEventListener('click', (event) => {
    if (event.target === mobileBackdrop) closeMobileFilters();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMobileFilters();
  });

  applyMembersFilters();
})();
