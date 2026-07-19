(() => {
  const form = document.getElementById("teamEditForm");
  if (!form) return;

  const scroll = document.getElementById("teamFormScroll");
  const tabs = [...document.querySelectorAll("[data-team-form-tab]")];
  const panels = [...document.querySelectorAll("[data-team-form-panel]")];
  const title = document.getElementById("teamFormPanelTitle");
  const saveState = document.getElementById("teamSaveState");
  const saveTop = document.getElementById("teamSaveTop");
  const discardTop = document.getElementById("teamDiscardTop");
  const initialAvatarUrl = form.dataset.avatarUrl || "";
  const initialCoverUrl = form.dataset.coverUrl || "";
  const hadAvatar = form.dataset.hasAvatar === "1";
  const hadCover = form.dataset.hasCover === "1";
  const copy = {
    clean: form.dataset.cleanLabel || "Keine offenen Änderungen",
    dirty: form.dataset.dirtyLabel || "Ungespeicherte Änderungen",
    saving: form.dataset.savingLabel || "Wird gespeichert …",
    general: form.dataset.generalLabel || "Allgemein",
    leave: form.dataset.leaveWarning || "Du hast ungespeicherte Teamänderungen.",
    nameRequired: form.dataset.nameRequired || "Bitte gib einen Teamnamen ein.",
    discard: form.dataset.discardMessage || "Änderungen verworfen.",
    complete: form.dataset.completeLabel || "Alle Teamangaben sind vollständig.",
    nearly: form.dataset.nearlyLabel || "Nur noch wenige Angaben fehlen.",
    incomplete: form.dataset.incompleteLabel || "Vervollständige Teamprofil und Medien.",
    profileComplete: form.dataset.profileCompleteLabel || "Vollständig",
    profileIncomplete: form.dataset.profileIncompleteLabel || "Unvollständig",
    public: form.dataset.publicLabel || "Öffentlich",
    private: form.dataset.privateLabel || "Privat",
    open: form.dataset.openLabel || "Offen",
    closed: form.dataset.closedLabel || "Geschlossen",
    platformOpen: form.dataset.platformOpenLabel || "Plattform offen",
    regionOpen: form.dataset.regionOpenLabel || "Region offen",
    languageOpen: form.dataset.languageOpenLabel || "Sprache offen",
    playstyleOpen: form.dataset.playstyleOpenLabel || "Spielstil offen",
    newTeam: form.dataset.newTeamLabel || "Team",
    tagline: form.dataset.taglineFallback || "Deine Team-Tagline",
    description: form.dataset.descriptionFallback || "Beschreibe kurz, wofür dein Team steht."
  };

  let dirty = false;
  let submitting = false;

  function toast(message) {
    if (typeof window.showToast === "function") {
      window.showToast(message);
      return;
    }
    const node = document.getElementById("toast");
    if (!node) return;
    node.textContent = message;
    node.classList.add("visible");
    window.setTimeout(() => node.classList.remove("visible"), 2200);
  }

  function showPanel(name, updateHash = true) {
    const valid = panels.some((panel) => panel.dataset.teamFormPanel === name);
    const target = valid ? name : "general";

    tabs.forEach((tab) => tab.classList.toggle("active", tab.dataset.teamFormTab === target));
    panels.forEach((panel) => {
      const active = panel.dataset.teamFormPanel === target;
      panel.hidden = !active;
      panel.classList.toggle("active", active);
    });

    const activeTab = tabs.find((tab) => tab.dataset.teamFormTab === target);
    if (title) title.textContent = activeTab?.dataset.title || copy.general;

    if (updateHash) {
      history.replaceState(null, "", `#${target}`);
    }
    scroll?.scrollTo({ top: 0, behavior: "smooth" });
  }

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => showPanel(tab.dataset.teamFormTab));
  });

  const fieldValue = (id) => document.getElementById(id)?.value.trim() || "";
  const radioValue = (name) => form.querySelector(`input[name="${name}"]:checked`)?.value || "";

  function setText(id, value) {
    const node = document.getElementById(id);
    if (node) node.textContent = String(value);
  }

  function initialsFromName() {
    return (fieldValue("teamName")
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0))
      .join("") || "HT")
      .slice(0, 4)
      .toUpperCase();
  }

  function setAvatarLetters() {
    const letters = initialsFromName();
    setText("teamShortcode", letters);
    document.querySelectorAll(
      "#teamEditorAvatarMonogram, #teamAvatarEditorMonogram, #teamLiveAvatarMonogram"
    ).forEach((node) => {
      node.textContent = letters;
    });
  }

  function calculateCompletion() {
    const generalValues = [fieldValue("teamName"), fieldValue("teamTagline"), fieldValue("teamDescription")];
    const profileValues = [
      document.getElementById("teamPlatform")?.value || "",
      document.getElementById("teamPlaystyle")?.value || "",
      document.getElementById("teamRegion")?.value || "",
      document.getElementById("teamLanguage")?.value || ""
    ];
    const avatarComplete = hadAvatar || (document.getElementById("teamAvatarInput")?.files?.length || 0) > 0;
    const coverComplete = hadCover || (document.getElementById("teamCoverInput")?.files?.length || 0) > 0;
    const mediaPercent = Math.round(([avatarComplete, coverComplete].filter(Boolean).length / 2) * 100);
    const generalPercent = Math.round((generalValues.filter(Boolean).length / generalValues.length) * 100);
    const profilePercent = Math.round((profileValues.filter(Boolean).length / profileValues.length) * 100);
    const settingsPercent = radioValue("visibility") && radioValue("recruitment_status") ? 100 : 0;
    const total = Math.round(
      generalPercent * 0.38 +
      mediaPercent * 0.16 +
      profilePercent * 0.30 +
      settingsPercent * 0.16
    );

    setText("teamSectionGeneral", `${generalPercent}%`);
    setText("teamSectionMedia", `${mediaPercent}%`);
    setText("teamSectionProfile", `${profilePercent}%`);
    setText("teamSectionSettings", `${settingsPercent}%`);

    ["teamCompletionNav", "teamCompletionText", "teamCompletionFooter", "teamPreviewCompletionText"]
      .forEach((id) => setText(id, `${total}%`));

    const completionBar = document.getElementById("teamCompletionBar");
    const previewBar = document.getElementById("teamPreviewCompletionBar");
    if (completionBar) completionBar.style.width = `${total}%`;
    if (previewBar) previewBar.style.width = `${total}%`;

    setText(
      "teamCompletionHint",
      total === 100 ? copy.complete : total >= 70 ? copy.nearly : copy.incomplete
    );

    const profileChip = document.getElementById("teamProfileStatusChip");
    if (profileChip) {
      profileChip.textContent = profilePercent === 100 ? copy.profileComplete : copy.profileIncomplete;
      profileChip.classList.toggle("complete", profilePercent === 100);
    }

    return total;
  }

  function updatePreview() {
    const name = fieldValue("teamName") || copy.newTeam;
    const tagline = fieldValue("teamTagline") || copy.tagline;
    const description = fieldValue("teamDescription") || copy.description;

    ["teamLiveName", "teamEditorCoverName", "teamAvatarEditorName"].forEach((id) => setText(id, name));
    ["teamLiveTagline", "teamEditorCoverTagline"].forEach((id) => setText(id, tagline));
    setText("teamLiveDescription", description);

    setText("teamLivePlatform", document.getElementById("teamPlatform")?.value || copy.platformOpen);
    setText("teamLiveRegion", document.getElementById("teamRegion")?.value || copy.regionOpen);
    setText("teamLiveLanguage", document.getElementById("teamLanguage")?.value || copy.languageOpen);
    setText("teamLivePlaystyle", document.getElementById("teamPlaystyle")?.value || copy.playstyleOpen);
    setText("teamLiveVisibility", radioValue("visibility") === "private" ? copy.private : copy.public);
    setText("teamLiveRecruitment", radioValue("recruitment_status") === "closed" ? copy.closed : copy.open);

    setText("teamNameCount", fieldValue("teamName").length);
    setText("teamTaglineCount", fieldValue("teamTagline").length);
    setText("teamDescriptionCount", fieldValue("teamDescription").length);
    setAvatarLetters();
    calculateCompletion();
  }

  function setSaveState(mode) {
    if (!saveState) return;
    saveState.classList.toggle("dirty", mode === "dirty");
    saveState.innerHTML = `<i></i>${mode === "saving" ? copy.saving : mode === "dirty" ? copy.dirty : copy.clean}`;
  }

  function markDirty() {
    dirty = true;
    setSaveState("dirty");
  }

  form.addEventListener("input", () => {
    updatePreview();
    markDirty();
  });

  form.addEventListener("change", () => {
    updatePreview();
    markDirty();
  });

  function bindImageInput(inputId, targets, backgrounds = []) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener("change", () => {
      const file = input.files?.[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (event) => {
        const url = String(event.target?.result || "");

        targets.forEach((id) => {
          const image = document.getElementById(id);
          if (!image) return;
          image.src = url;
          image.hidden = false;
        });

        backgrounds.forEach((id) => {
          const node = document.getElementById(id);
          if (!node) return;
          node.style.backgroundImage =
            `linear-gradient(135deg,rgba(32,33,30,.22),rgba(32,33,30,.42)),url("${url}")`;
          node.classList.add("has-current-cover");
        });

        if (targets.length) {
          document.querySelectorAll(
            "#teamEditorAvatarMonogram, #teamAvatarEditorMonogram, #teamLiveAvatarMonogram"
          ).forEach((node) => {
            node.hidden = true;
          });
        }

        updatePreview();
        markDirty();
      };
      reader.readAsDataURL(file);
    });
  }

  bindImageInput(
    "teamAvatarInput",
    ["teamEditorAvatarImage", "teamAvatarEditorImage", "teamLiveAvatarImage"]
  );
  bindImageInput(
    "teamCoverInput",
    [],
    ["teamCoverEditorPreview", "teamLiveCover"]
  );

  function restoreMedia() {
    const avatarTargets = [
      document.getElementById("teamEditorAvatarImage"),
      document.getElementById("teamAvatarEditorImage"),
      document.getElementById("teamLiveAvatarImage")
    ].filter(Boolean);

    avatarTargets.forEach((image) => {
      if (hadAvatar && initialAvatarUrl) {
        image.src = initialAvatarUrl;
        image.hidden = false;
      } else {
        image.removeAttribute("src");
        image.hidden = true;
      }
    });

    document.querySelectorAll(
      "#teamEditorAvatarMonogram, #teamAvatarEditorMonogram, #teamLiveAvatarMonogram"
    ).forEach((node) => {
      node.hidden = hadAvatar;
    });

    ["teamCoverEditorPreview", "teamLiveCover"].forEach((id) => {
      const node = document.getElementById(id);
      if (!node) return;
      node.style.backgroundImage = hadCover && initialCoverUrl
        ? `linear-gradient(135deg,rgba(32,33,30,.22),rgba(32,33,30,.42)),url("${initialCoverUrl}")`
        : "";
      node.classList.toggle("has-current-cover", hadCover);
    });
  }

  function restoreInitialValues() {
    form.reset();
    const avatarInput = document.getElementById("teamAvatarInput");
    const coverInput = document.getElementById("teamCoverInput");
    if (avatarInput) avatarInput.value = "";
    if (coverInput) coverInput.value = "";
    restoreMedia();
    dirty = false;
    setSaveState("clean");
    updatePreview();
    toast(copy.discard);
  }

  discardTop?.addEventListener("click", restoreInitialValues);
  saveTop?.addEventListener("click", () => form.requestSubmit());

  form.addEventListener("submit", (event) => {
    if (!fieldValue("teamName")) {
      event.preventDefault();
      showPanel("general");
      document.getElementById("teamName")?.focus();
      toast(copy.nameRequired);
      return;
    }

    submitting = true;
    dirty = false;
    setSaveState("saving");
    saveTop?.setAttribute("disabled", "disabled");
  });

  window.addEventListener("beforeunload", (event) => {
    if (!dirty || submitting) return;
    event.preventDefault();
    event.returnValue = copy.leave;
  });

  restoreMedia();
  updatePreview();
  setSaveState("clean");
  showPanel(location.hash.replace("#", "") || "general", false);
})();