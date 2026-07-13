(() => {
  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const staticEnglish = {
    'Interaktive Marker':'Interactive markers','Community-Daten':'Community data','Kommentare':'Comments',
    'KARTEN':'MAPS','Map wechseln':'Switch map','Alle':'All','Marker suchen':'Search markers',
    'Bosse':'Bosses','Türme':'Towers','EBENEN':'LAYERS','Darstellung':'Display',
    'Verbindungslinien':'Connection lines','Routen zwischen Compounds':'Routes between compounds',
    'Beschriftungen':'Labels','Namen direkt auf der Karte':'Names directly on the map',
    'INTERAKTIVE KARTE':'INTERACTIVE MAP','Ansicht':'View','Messen':'Measure','Teilen':'Share',
    'Cash Spot einreichen':'Submit cash spot','AUSGEWÄHLTER MARKER':'SELECTED MARKER',
    'Bereich':'Area','Koordinaten':'Coordinates','Bestätigt':'Verified',
    'War dieser Fundort hilfreich?':'Was this location helpful?','Auch Gäste können abstimmen.':'Guests can vote too.',
    'Kommentare ansehen':'View comments','Schließen':'Close','Kommentar schreiben …':'Write a comment …',
    'Neuen Fundort einreichen':'Submit a new location',
    'Wähle den Punkt auf der Karte und lade einen gut erkennbaren Screenshot hoch. Neue Fundorte werden vor der Freischaltung geprüft.':'Choose the point on the map and upload a clear screenshot. New locations are reviewed before publication.',
    'Kurze Beschreibung':'Short description','Ausgewählte Position':'Selected position','Abbrechen':'Cancel','Einreichen':'Submit'
  };
  const translateStatic = () => {
    if (!isEnglish) return;
    const root = document.querySelector('.map-detail-page-shell');
    if (!root) return;
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach((node) => {
      const raw = node.nodeValue || '';
      const trimmed = raw.trim();
      if (!staticEnglish[trimmed]) return;
      node.nodeValue = raw.replace(trimmed, staticEnglish[trimmed]);
    });
    const searchInput = document.getElementById('mapMarkerSearch');
    if (searchInput) searchInput.placeholder = 'Search markers';
    const commentInput = document.getElementById('markerCommentInput');
    if (commentInput) commentInput.placeholder = 'Write a comment …';
  };

  const notify = (message) => {
    if (typeof window.showToast === 'function') {
      window.showToast(message);
      return;
    }
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    window.setTimeout(() => toast.classList.remove('show'), 2200);
  };

  const maps = {"stillwater-bayou": {"name": "Stillwater Bayou", "asset": "/assets/themes/hnt_preview/dashboard-maps/demo/stillwater-bayou.svg", "subtitle": "Klassischer Bayou · 2048 × 2048", "subtitleEn": "Classic bayou · 2048 × 2048", "stats": {"markers": 128, "cash": 38, "comments": 64}, "selected": {"title": "Oben in der Scheune", "type": "cash", "meta": "Cash Spot · Healing-Waters Church", "votes": 18, "comments": 6, "coords": "X 942 · Y 1455"}}, "lawson-delta": {"name": "Lawson Delta", "asset": "/assets/themes/hnt_preview/dashboard-maps/demo/lawson-delta.svg", "subtitle": "Industriegebiet · 2048 × 2048", "subtitleEn": "Industrial area · 2048 × 2048", "stats": {"markers": 116, "cash": 31, "comments": 42}, "selected": {"title": "Unter dem Bahnsteig", "type": "cash", "meta": "Cash Spot · Lawson Station", "votes": 14, "comments": 4, "coords": "X 1188 · Y 902"}}, "desalle": {"name": "DeSalle", "asset": "/assets/themes/hnt_preview/dashboard-maps/demo/desalle.svg", "subtitle": "Höhenunterschiede · 2048 × 2048", "subtitleEn": "Elevation changes · 2048 × 2048", "stats": {"markers": 121, "cash": 35, "comments": 51}, "selected": {"title": "Tarotkarte am Nordpfad", "type": "tarot", "meta": "Tarot · Upper DeSalle", "votes": 11, "comments": 3, "coords": "X 776 · Y 318"}}, "mammons-gulch": {"name": "Mammon's Gulch", "asset": "/assets/themes/hnt_preview/dashboard-maps/demo/mammons-gulch.svg", "subtitle": "Gebirgiges Terrain · 2048 × 2048", "subtitleEn": "Mountainous terrain · 2048 × 2048", "stats": {"markers": 134, "cash": 41, "comments": 73}, "selected": {"title": "Im Lair unter der Treppe", "type": "cash", "meta": "Cash Spot · Terminus Railyard", "votes": 22, "comments": 8, "coords": "X 926 · Y 1080"}}};
  const title = document.getElementById("mapDetailTitle");
  const canvasTitle = document.getElementById("mapCanvasTitle");
  const subtitle = document.getElementById("mapDetailSubtitle");
  const image = document.getElementById("mapCanvasImage");
  const detailImage = document.getElementById("markerDetailImage");
  const markers = [...document.querySelectorAll(".map-detail-marker")];
  const chips = [...document.querySelectorAll("[data-filter-chip]")];
  const search = document.getElementById("mapMarkerSearch");
  const labels = document.getElementById("mapLabelLayer");
  const lines = document.getElementById("mapLineOverlay");
  const visibleCount = document.getElementById("mapVisibleCount");
  const zoomText = document.getElementById("mapZoomText");
  const surface = document.getElementById("mapCanvasSurface");
  const measureLayer = document.getElementById("mapMeasureLayer");
  const popover = document.getElementById("mapSelectedPopover");
  const cashModal = document.getElementById("mapCashModal");
  let zoom = 1;
  let currentMap = "stillwater-bayou";
  let measureActive = false;

  function queryMap() {
    const routeSlug = document.body.dataset.mapSlug || "";
    if (maps[routeSlug]) return routeSlug;
    const value = new URLSearchParams(location.search).get("map");
    return maps[value] ? value : "stillwater-bayou";
  }

  function setMap(slug) {
    const data = maps[slug];
    if (!data) return;
    currentMap = slug;

    title.textContent = data.name;
    canvasTitle.textContent = data.name;
    subtitle.textContent = isEnglish ? data.subtitleEn : data.subtitle;
    image.src = data.asset;
    image.alt = `${data.name} Karte`;
    detailImage.src = data.asset;
    document.title = `HNT.ROCKS — ${data.name}`;

    document.getElementById("mapStatMarkers").textContent = data.stats.markers;
    document.getElementById("mapStatCash").textContent = data.stats.cash;
    document.getElementById("mapStatComments").textContent = data.stats.comments;

    document.querySelectorAll("[data-map-switch]").forEach((button) => {
      button.classList.toggle("active", button.dataset.mapSwitch === slug);
    });

    const selected = data.selected;
    document.getElementById("mapPopoverTitle").textContent = selected.title;
    document.getElementById("mapPopoverMeta").textContent = `${selected.meta.split(" · ").slice(-1)[0]} · ${selected.votes} hilfreich`;
    document.getElementById("markerDetailTitle").textContent = selected.title;
    document.getElementById("markerDetailArea").textContent = selected.meta.split(" · ").slice(-1)[0];
    document.getElementById("markerDetailCoords").textContent = selected.coords;
    document.getElementById("mapCoordinates").textContent = selected.coords;
    document.getElementById("modalCoords").textContent = selected.coords;
    document.getElementById("markerVoteCount").textContent = selected.votes;
    document.getElementById("markerCommentCount").textContent = selected.comments;
    document.getElementById("markerDetailType").textContent = selected.type === "tarot" ? "Tarotkarte" : "Cash Spot";

    zoom = 1;
    updateZoom();
    notify(isEnglish ? `${data.name} loaded` : `${data.name} geladen`);
  }

  function updateZoom() {
    surface.style.transform = `scale(${zoom})`;
    zoomText.textContent = `${Math.round(zoom * 100)}%`;
  }

  document.querySelectorAll("[data-map-switch]").forEach((button) => {
    button.addEventListener("click", () => setMap(button.dataset.mapSwitch));
  });

  document.getElementById("mapZoomIn").addEventListener("click", () => {
    zoom = Math.min(1.35, zoom + .1);
    updateZoom();
  });

  document.getElementById("mapZoomOut").addEventListener("click", () => {
    zoom = Math.max(.85, zoom - .1);
    updateZoom();
  });

  document.getElementById("mapResetView").addEventListener("click", () => {
    zoom = 1;
    updateZoom();
    notify(isEnglish ? "Map view reset" : "Kartenansicht zurückgesetzt");
  });

  document.getElementById("toggleMapLines").addEventListener("change", (event) => {
    lines.hidden = !event.target.checked;
  });

  document.getElementById("toggleMapLabels").addEventListener("change", (event) => {
    labels.hidden = !event.target.checked;
  });

  function applyMarkerFilters() {
    const enabled = new Set(chips.filter((chip) => chip.querySelector("input").checked).map((chip) => chip.dataset.filterChip));
    const query = search.value.trim().toLowerCase();
    let visible = 0;

    markers.forEach((marker) => {
      const matchesType = enabled.has(marker.dataset.markerType);
      const matchesQuery = !query || marker.dataset.markerLabel.toLowerCase().includes(query);
      const show = matchesType && matchesQuery;
      marker.hidden = !show;
      if (show) visible += 1;
    });

    visibleCount.textContent = isEnglish ? `${visible} markers visible` : `${visible} Marker sichtbar`;
  }

  chips.forEach((chip) => {
    const input = chip.querySelector("input");
    input.addEventListener("change", () => {
      chip.classList.toggle("active", input.checked);
      applyMarkerFilters();
    });
  });

  search.addEventListener("input", applyMarkerFilters);

  document.getElementById("resetMapFilters").addEventListener("click", () => {
    chips.forEach((chip) => {
      chip.querySelector("input").checked = true;
      chip.classList.add("active");
    });
    search.value = "";
    applyMarkerFilters();
  });

  markers.forEach((marker) => {
    marker.addEventListener("click", () => {
      markers.forEach((item) => item.classList.remove("selected"));
      marker.classList.add("selected");
      const rect = marker.getBoundingClientRect();
      const parent = marker.parentElement.getBoundingClientRect();
      popover.style.left = `${((rect.left - parent.left) / parent.width) * 100 + 2}%`;
      popover.style.top = `${((rect.top - parent.top) / parent.height) * 100 - 1}%`;
      document.getElementById("mapPopoverTitle").textContent = marker.dataset.markerLabel;
      document.getElementById("markerDetailTitle").textContent = marker.dataset.markerLabel;
      document.getElementById("markerDetailType").textContent = marker.dataset.markerType === "cash" ? "Cash Spot" : marker.dataset.markerType === "tarot" ? "Tarotkarte" : marker.dataset.markerType.charAt(0).toUpperCase() + marker.dataset.markerType.slice(1);
      popover.hidden = false;
    });
  });

  document.getElementById("mapMeasureToggle").addEventListener("click", (event) => {
    measureActive = !measureActive;
    event.currentTarget.classList.toggle("active", measureActive);
    measureLayer.hidden = !measureActive;
    notify(measureActive ? (isEnglish ? "Measure mode enabled" : "Messmodus aktiviert") : (isEnglish ? "Measure mode disabled" : "Messmodus beendet"));
  });

  document.getElementById("mapShareView").addEventListener("click", async () => {
    const url = `${location.origin}${location.pathname}?map=${currentMap}&x=942&y=1455&z=${zoom.toFixed(1)}`;
    try {
      await navigator.clipboard.writeText(url);
      notify(isEnglish ? "Map view copied" : "Kartenansicht kopiert");
    } catch {
      notify(isEnglish ? "Demo link prepared" : "Demo-Link wurde vorbereitet");
    }
  });

  document.getElementById("markerVoteUp").addEventListener("click", (event) => {
    const counter = document.getElementById("markerVoteCount");
    counter.textContent = Number(counter.textContent) + 1;
    event.currentTarget.classList.add("active");
    notify(isEnglish ? "Thanks for your rating" : "Danke für deine Bewertung");
  });

  document.getElementById("markerVoteDown").addEventListener("click", (event) => {
    event.currentTarget.classList.add("active");
    notify(isEnglish ? "Rating saved" : "Bewertung gespeichert");
  });

  document.getElementById("markerCommentForm").addEventListener("submit", (event) => {
    event.preventDefault();
    const input = document.getElementById("markerCommentInput");
    const value = input.value.trim();
    if (!value) return;

    const item = document.createElement("article");
    item.innerHTML = `<img src="/assets/vikinger/img/default-avatar.svg" alt=""><div><strong>Valentina</strong><p></p><small>${isEnglish ? 'just now' : 'gerade eben'}</small></div>`;
    item.querySelector("p").textContent = value;
    document.getElementById("markerCommentList").append(item);
    input.value = "";
    const count = document.getElementById("markerCommentCount");
    count.textContent = Number(count.textContent) + 1;
    notify(isEnglish ? "Comment added" : "Kommentar hinzugefügt");
  });

  const markerDetailModal = document.getElementById("markerDetailModal");
  const markerCommentsModal = document.getElementById("markerCommentsModal");

  function openCashModal() {
    cashModal.hidden = false;
    document.body.classList.add("map-modal-open", "map-overlay-open");
  }

  function closeCashModal() {
    cashModal.hidden = true;
    document.body.classList.remove("map-modal-open");
    if (markerDetailModal.hidden && markerCommentsModal.hidden) document.body.classList.remove("map-overlay-open");
  }

  document.getElementById("submitCashSpot").addEventListener("click", openCashModal);
  document.getElementById("closeCashModal").addEventListener("click", closeCashModal);
  document.getElementById("cancelCashModal").addEventListener("click", closeCashModal);
  document.getElementById("sendCashModal").addEventListener("click", () => {
    closeCashModal();
    notify(isEnglish ? "Cash spot submitted for review" : "Cash Spot wurde zur Prüfung eingereicht");
  });

  function openOverlay(modal) {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add("map-overlay-open");
  }

  function closeOverlay(modal) {
    if (!modal) return;
    modal.hidden = true;
    if (markerDetailModal.hidden && markerCommentsModal.hidden && cashModal.hidden) document.body.classList.remove("map-overlay-open");
  }

  function openMarkerDetailsModal() { openOverlay(markerDetailModal); }
  function openMarkerCommentsModal() {
    closeOverlay(markerDetailModal);
    openOverlay(markerCommentsModal);
  }

  document.getElementById("openMarkerDetails").addEventListener("click", openMarkerDetailsModal);
  document.getElementById("mapOpenMarkerDetails").addEventListener("click", openMarkerDetailsModal);
  document.getElementById("mapOpenMarkerComments").addEventListener("click", openMarkerCommentsModal);
  document.getElementById("openCommentsFromDetails").addEventListener("click", openMarkerCommentsModal);
  document.getElementById("closeMarkerDetails").addEventListener("click", () => closeOverlay(markerDetailModal));
  document.getElementById("closeMarkerDetailsFooter").addEventListener("click", () => closeOverlay(markerDetailModal));
  document.getElementById("closeMarkerComments").addEventListener("click", () => closeOverlay(markerCommentsModal));

  [markerDetailModal, markerCommentsModal].forEach((modal) => {
    modal.addEventListener("click", (event) => {
      if (event.target === modal) closeOverlay(modal);
    });
  });

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    closeOverlay(markerDetailModal);
    closeOverlay(markerCommentsModal);
    closeCashModal();
  });

  document.getElementById("closeMarkerDetails").addEventListener("click", () => { popover.hidden = true; });

  translateStatic();
  setMap(queryMap());
  applyMarkerFilters();
})();
