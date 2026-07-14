(() => {
  const shell = document.querySelector('.cup-create-page-shell');
  const form = document.querySelector('#cupCreateForm');
  if (!shell || !form) return;

  const config = window.HNT_CUP_CREATE || {};
  const tabs = Array.from(shell.querySelectorAll('[data-cup-create-tab]'));
  const panels = Array.from(shell.querySelectorAll('[data-cup-create-panel]'));
  const panelTitle = shell.querySelector('#cupCreatePanelTitle');
  const saveState = shell.querySelector('#cupSaveState');
  const scroll = shell.querySelector('#cupCreateScroll');
  const submitButtons = [
    shell.querySelector('#cupSaveTop'),
    shell.querySelector('#cupPreviewSubmit'),
  ].filter(Boolean);

  const field = (selector) => shell.querySelector(selector);
  const fields = {
    title: field('#cupTitle'),
    summaryDe: field('#cupSummaryDe'),
    descriptionDe: field('#cupDescriptionDe'),
    registrationOpens: field('#registrationOpens'),
    registrationCloses: field('#registrationCloses'),
    starts: field('#cupStarts'),
    ends: field('#cupEnds'),
    rulesPreset: field('#cupRulesPreset'),
    aiPreset: field('#cupAiPreset'),
    rulesDe: field('#cupRulesDe'),
    scoringDe: field('#cupScoringDe'),
    teamSize: field('#cupTeamSize'),
    maxTeams: field('#cupMaxTeams'),
    region: field('#cupRegion'),
    language: field('#cupLanguage'),
    maxUploads: field('#cupMaxUploads'),
    maxScored: field('#cupMaxScored'),
    communityActions: field('#cupCommunityActions'),
    profileRequired: field('#cupProfileRequired'),
    status: field('#cupStatus'),
    visibility: field('#cupVisibility'),
    cover: field('#cupCoverInput'),
  };

  const routeMap = {
    'Aktive Cups': shell.dataset.cupsActiveUrl,
    'Meine Cup-Teams': shell.dataset.cupsMineUrl,
    'Einreichungen': shell.dataset.cupsSubmissionsUrl,
    'Hall of Fame': shell.dataset.cupsHallUrl,
  };

  const cupsNav = shell.querySelector('.nav-cups');
  cupsNav?.classList.add('is-current');
  cupsNav?.querySelector(':scope > .main-nav-trigger')?.classList.add('is-current');
  cupsNav?.querySelectorAll('[data-navigation-label]').forEach((control) => {
    const target = routeMap[control.dataset.navigationLabel];
    if (!target) return;
    control.removeAttribute('aria-disabled');
    control.removeAttribute('data-unavailable');
    control.addEventListener('click', (event) => {
      event.preventDefault();
      window.location.assign(target);
    });
  });

  const activateTab = (name, focus = false) => {
    const activeTab = tabs.find((tab) => tab.dataset.cupCreateTab === name) || tabs[0];
    if (!activeTab) return;

    tabs.forEach((tab) => {
      const active = tab === activeTab;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
    });

    panels.forEach((panel) => {
      const active = panel.dataset.cupCreatePanel === activeTab.dataset.cupCreateTab;
      panel.classList.toggle('active', active);
      panel.hidden = !active;
    });

    if (panelTitle) panelTitle.textContent = activeTab.dataset.title || activeTab.textContent.trim();
    if (focus) scroll?.scrollTo({ top: Math.max(0, shell.querySelector('.cup-create-workspace')?.offsetTop - 8), behavior: 'smooth' });
  };

  tabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.dataset.cupCreateTab, true)));

  const normalize = (value) => String(value ?? '').trim();
  const isFilled = (node) => normalize(node?.value) !== '';
  const selectedPlatforms = () => Array.from(form.querySelectorAll('input[name="allowed_platforms[]"]:checked'));

  const completionRules = {
    general: [() => isFilled(fields.title), () => isFilled(fields.summaryDe), () => isFilled(fields.descriptionDe)],
    schedule: [() => isFilled(fields.registrationOpens), () => isFilled(fields.registrationCloses), () => isFilled(fields.starts), () => isFilled(fields.ends)],
    scoring: [() => isFilled(fields.rulesPreset), () => isFilled(fields.aiPreset), () => isFilled(fields.rulesDe), () => isFilled(fields.scoringDe)],
    participation: [() => isFilled(fields.teamSize), () => isFilled(fields.maxTeams), () => isFilled(fields.region), () => selectedPlatforms().length > 0],
    prizes: [
      () => isFilled(form.elements.prize_first_de),
      () => isFilled(form.elements.prize_second_de),
      () => isFilled(form.elements.prize_third_de),
    ],
    submissions: [() => isFilled(fields.maxUploads), () => isFilled(fields.maxScored), () => isFilled(fields.communityActions) || fields.profileRequired?.checked],
    publish: [() => isFilled(fields.status), () => isFilled(fields.visibility)],
  };

  const sectionCompletion = (name) => {
    const rules = completionRules[name] || [];
    if (!rules.length) return 0;
    return Math.round((rules.filter((rule) => rule()).length / rules.length) * 100);
  };

  const updateCompletion = () => {
    const values = Object.keys(completionRules).map((name) => {
      const value = sectionCompletion(name);
      shell.querySelector(`[data-cup-section-completion="${name}"]`)?.replaceChildren(`${value}%`);
      return value;
    });
    const total = Math.round(values.reduce((sum, value) => sum + value, 0) / Math.max(1, values.length));

    ['#cupCompletionNav', '#cupCompletionText', '#cupCompletionFooter', '#cupLiveCompletion'].forEach((selector) => {
      const node = shell.querySelector(selector);
      if (node) node.textContent = `${total}%`;
    });
    ['#cupCompletionBar', '#cupLiveCompletionBar'].forEach((selector) => {
      const node = shell.querySelector(selector);
      if (node) node.style.width = `${total}%`;
    });
  };

  const teamLabels = { 1: 'Solo', 2: 'Duo', 3: 'Trio', 4: 'Quartet' };
  const platformLabels = config.platformLabels || {};
  const statusLabels = config.statusLabels || {};

  const updatePreview = () => {
    const title = normalize(fields.title?.value);
    const summary = normalize(fields.summaryDe?.value);
    const teamSize = Number(fields.teamSize?.value || 1);
    const platforms = selectedPlatforms().map((input) => platformLabels[input.value] || input.value);
    const visibility = fields.visibility?.value || 'public';
    const status = fields.status?.value || 'planned';

    const liveTitle = title || document.title.split('·')[0].trim();
    const livePlatforms = platforms.length ? platforms.join(' / ') : 'Alle Plattformen';

    const setText = (selector, value) => {
      const node = shell.querySelector(selector);
      if (node) node.textContent = value;
    };

    setText('#cupCoverTitle', liveTitle.toUpperCase());
    setText('#cupCoverMeta', livePlatforms);
    setText('#cupLiveTitle', liveTitle.toUpperCase());
    setText('#cupLiveName', liveTitle);
    setText('#cupLiveSummary', summary || shell.querySelector('#cupLiveSummary')?.dataset.fallback || '—');
    setText('#cupLivePlatforms', livePlatforms);
    setText('#cupLiveTeamSize', teamLabels[teamSize] || String(teamSize));
    setText('#cupLiveHunters', String(teamSize));
    setText('#cupLiveMaxTeams', normalize(fields.maxTeams?.value) || '∞');
    setText('#cupLiveRegion', normalize(fields.region?.value) || '—');
    setText('#cupLiveUploads', normalize(fields.maxUploads?.value) || '∞');
    setText('#cupLiveVisibility', visibility === 'public' ? (config.publicLabel || 'Public') : (config.privateLabel || 'Private'));
    setText('#cupLiveStatus', statusLabels[status] || status);
    setText('#cupLiveRules', fields.rulesPreset?.selectedOptions?.[0]?.textContent?.trim() || '—');
  };

  const updateCounts = () => {
    shell.querySelectorAll('[data-count-for]').forEach((counter) => {
      const input = shell.querySelector(`#${CSS.escape(counter.dataset.countFor || '')}`);
      if (input) counter.textContent = String(input.value.length);
    });
  };

  const initialSnapshot = new URLSearchParams(new FormData(form)).toString();
  let dirty = false;
  let submitting = false;

  const updateDirty = () => {
    const snapshot = new URLSearchParams(new FormData(form)).toString();
    dirty = snapshot !== initialSnapshot || Boolean(fields.cover?.files?.length);
    saveState?.classList.toggle('dirty', dirty);
    const label = saveState?.querySelector('span');
    if (label) label.textContent = dirty ? (config.dirty || 'Unsaved changes') : (config.clean || 'No unsaved changes');
  };

  const refresh = () => {
    updateCounts();
    updateCompletion();
    updatePreview();
    updateDirty();
  };

  form.addEventListener('input', refresh);
  form.addEventListener('change', refresh);

  shell.querySelector('[data-cup-cover-trigger]')?.addEventListener('click', () => fields.cover?.click());
  fields.cover?.addEventListener('change', () => {
    const file = fields.cover.files?.[0];
    if (!file || !file.type.startsWith('image/')) return;
    const url = URL.createObjectURL(file);
    const background = `linear-gradient(180deg,rgba(20,20,18,.05),rgba(20,20,18,.70)),url("${url}")`;
    const editor = shell.querySelector('#cupCoverPreview');
    const preview = shell.querySelector('#cupLiveCover');
    if (editor) editor.style.backgroundImage = background;
    if (preview) preview.style.backgroundImage = background;
    refresh();
  });

  submitButtons.forEach((button) => button.addEventListener('click', () => form.requestSubmit()));
  shell.querySelector('#cupDiscardTop')?.addEventListener('click', () => {
    window.location.assign(window.location.pathname);
  });

  form.addEventListener('submit', () => {
    submitting = true;
    dirty = false;
    submitButtons.forEach((button) => { button.disabled = true; });
    form.querySelectorAll('[data-cup-create-submit]').forEach((button) => { button.disabled = true; });
  });

  window.addEventListener('beforeunload', (event) => {
    if (!dirty || submitting) return;
    event.preventDefault();
    event.returnValue = config.leaveWarning || '';
  });

  const firstError = shell.querySelector('[data-field-error]');
  if (firstError) {
    const errorPanel = firstError.closest('[data-cup-create-panel]');
    if (errorPanel) activateTab(errorPanel.dataset.cupCreatePanel);
  }

  refresh();
})();
