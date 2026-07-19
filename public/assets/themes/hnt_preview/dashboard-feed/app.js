
const toast = document.getElementById("toast");

function showToast(message) {
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => toast.classList.remove("show"), 1700);
}

const page = document.body.dataset.page;
document.querySelectorAll(".main-nav [data-page]").forEach((link) => {
  link.classList.toggle("active", link.dataset.page === page);
});

document.querySelectorAll("[data-toast]").forEach((element) => {
  element.addEventListener("click", (event) => {
    if (element.getAttribute("href") === "#") event.preventDefault();
    showToast(element.dataset.toast);
  });
});

document.querySelectorAll(".accordion-trigger").forEach((button) => {
  button.addEventListener("click", () => button.classList.toggle("open"));
});

const candidateSearch = document.getElementById("candidateSearch");
if (candidateSearch) {
  candidateSearch.addEventListener("input", () => {
    const query = candidateSearch.value.trim().toLowerCase();
    document.querySelectorAll(".candidate-card").forEach((card) => {
      card.classList.toggle("hidden", !card.dataset.search.toLowerCase().includes(query));
    });
  });
}

const filterButton = document.getElementById("filterCandidates");
if (filterButton) {
  filterButton.addEventListener("click", () => {
    document.body.classList.toggle("candidate-filter");
    showToast(document.body.classList.contains("candidate-filter") ? "Filter enabled" : "Filter disabled");
  });
}

const exportButton = document.getElementById("exportCandidates");
if (exportButton) {
  exportButton.addEventListener("click", () => {
    const rows = [
      ["Name","Role","Match"],
      ["Katy Fuller","Fullstack Engineer","94%"],
      ["Sarah Page","Network engineer","87%"],
      ["Jonathan Kelly","Mobile Lead","91%"],
      ["Erica Wyatt","Head of Design","97%"]
    ];
    const csv = rows.map(row => row.map(value => `"${value}"`).join(",")).join("\n");
    const url = URL.createObjectURL(new Blob([csv], {type:"text/csv;charset=utf-8"}));
    const link = document.createElement("a");
    link.href = url;
    link.download = "crextio-candidates.csv";
    link.click();
    URL.revokeObjectURL(url);
  });
}

const salarySearch = document.getElementById("salarySearch");
if (salarySearch) {
  salarySearch.addEventListener("input", () => {
    const query = salarySearch.value.trim().toLowerCase();
    document.querySelectorAll(".employee-row").forEach((row) => {
      row.classList.toggle("hidden", !row.dataset.search.toLowerCase().includes(query));
    });
  });
}

document.querySelectorAll("button.checkbox").forEach((button) => {
  button.addEventListener("click", () => {
    button.classList.toggle("checked");
    button.innerHTML = button.classList.contains("checked")
      ? '<svg><use href="#i-check"></use></svg>'
      : "";
  });
});

document.querySelectorAll(".year-button").forEach((button) => {
  button.addEventListener("click", () => {
    const year = button.textContent.trim().startsWith("2024") ? "2025" : "2024";
    button.childNodes[0].textContent = `${year} `;
    showToast(`Statistics changed to ${year}`);
  });
});


document.querySelectorAll(".feed-tabs > button:not(.compose-button)").forEach((button) => {
  button.addEventListener("click", () => {
    document.querySelectorAll(".feed-tabs > button:not(.compose-button)").forEach((item) => item.classList.remove("active"));
    button.classList.add("active");
  });
});

document.querySelectorAll(".like-button").forEach((button) => {
  button.addEventListener("click", () => {
    button.classList.toggle("liked");
    const counter = button.querySelector("span");
    if (!counter) return;
    const value = Number.parseInt(counter.textContent, 10) || 0;
    counter.textContent = String(value + (button.classList.contains("liked") ? 1 : -1));
  });
});

document.querySelectorAll(".save-button").forEach((button) => {
  button.addEventListener("click", () => {
    button.classList.toggle("saved");
  });
});

document.querySelectorAll(".poll button").forEach((button) => {
  button.addEventListener("click", () => {
    document.querySelectorAll(".poll button").forEach((item) => item.classList.remove("selected"));
    button.classList.add("selected");
    showToast("Stimme gespeichert");
  });
});


const feedScroll = document.getElementById("feedScroll");
const feedStage = document.querySelector(".feed-stage");
const profilePanel = document.getElementById("profilePanel");
const compositionPanel = document.getElementById("compositionPanel");
const salaryCard = document.querySelector(".salary-attendance-card");
const socialFeedCard = document.querySelector(".social-feed-card");
const compositionActivity = document.getElementById("compositionActivity");
const activityState = document.getElementById("activityState");

if (feedScroll && compositionPanel && salaryCard) {
  let firstActivityBatchLoaded = false;
  let secondActivityBatchLoaded = false;

  const activityBatchOne = [
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg", title: "Katy hat ein LFG erstellt", meta: "EU · Console", time: "jetzt" },
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg", title: "Jonathan hat ein Moment geteilt", meta: "Stillwater Bayou", time: "2m" },
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/sarah.jpg", title: "Sarah erhielt einen Badge", meta: "Bayou Veteran", time: "6m" }
  ];

  const activityBatchTwo = [
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg", title: "Erica ist einem Cup beigetreten", meta: "Summer Hunt", time: "11m" },
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/feed-jonathan.jpg", title: "Noah hat einen Guide gespeichert", meta: "3 Push-Regeln", time: "18m" },
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/feed-erica.jpg", title: "Mara startete eine Umfrage", meta: "Lieblings-Map", time: "24m" }
  ];

  function renderActivity(items, replace = false) {
    if (!compositionActivity) return;

    const markup = items.map((item, index) => `
      <article class="activity-item" style="animation-delay:${index * 55}ms">
        <img src="${item.image}" alt="">
        <div><strong>${item.title}</strong><small>${item.meta}</small></div>
        <span>${item.time}</span>
      </article>
    `).join("");

    if (replace) compositionActivity.innerHTML = markup;
    else compositionActivity.insertAdjacentHTML("beforeend", markup);
  }

  function updateCompositionPanel() {
    const feedShellStyles = getComputedStyle(document.querySelector(".feed-shell"));
    const layoutGap = Number.parseFloat(
      feedShellStyles.getPropertyValue("--layout-gap")
    ) || 3;
    const cssGap = Number.parseFloat(
      feedShellStyles.getPropertyValue("--sticky-gap")
    ) || 12;

    /* Use the real rendered top of the Community Feed instead of
       recalculating it from the card above. This keeps both right and
       center columns pixel-aligned even when card height/gap variables
       change in later desktop refinements. */
    const startTop = socialFeedCard
      ? socialFeedCard.offsetTop
      : salaryCard.offsetTop + salaryCard.offsetHeight + layoutGap;
    const scrollTop = feedScroll.scrollTop;
    const expansionDistance = Math.max(260, Math.min(520, feedScroll.clientHeight * 0.68));
    const progress = Math.min(scrollTop / expansionDistance, 1);
    const top = Math.max(cssGap, startTop * (1 - progress));

    compositionPanel.style.top = `${top}px`;
    compositionPanel.classList.toggle("is-expanded", progress > 0.12);

    if (progress > 0.28 && !firstActivityBatchLoaded) {
      firstActivityBatchLoaded = true;
      if (activityState) activityState.textContent = "Lade Aktivität …";

      window.setTimeout(() => {
        renderActivity(activityBatchOne, true);
        if (activityState) activityState.textContent = "Live";
      }, 260);
    }

    if (progress > 0.72 && !secondActivityBatchLoaded) {
      secondActivityBatchLoaded = true;

      window.setTimeout(() => {
        renderActivity(activityBatchTwo, false);
        if (activityState) activityState.textContent = "6 neue";
      }, 220);
    }
  }

  feedScroll.addEventListener("scroll", updateCompositionPanel, { passive: true });
  window.addEventListener("resize", updateCompositionPanel);
  updateCompositionPanel();
}


const socialFeedHead = document.querySelector(".social-feed-head");

if (feedScroll && socialFeedHead) {
  function updateSocialFeedStickyState() {
    const feedRect = feedScroll.getBoundingClientRect();
    const headRect = socialFeedHead.getBoundingClientRect();
    const cssGap = Number.parseFloat(
      getComputedStyle(document.querySelector(".feed-shell"))
        .getPropertyValue("--sticky-gap")
    ) || 12;

    const isStuck =
      feedScroll.scrollTop > 0 &&
      headRect.top <= feedRect.top + cssGap + 1;

    socialFeedHead.classList.toggle("is-stuck", isStuck);
    feedScroll.classList.toggle("feed-is-stuck", isStuck);
  }

  feedScroll.addEventListener("scroll", updateSocialFeedStickyState, { passive: true });
  window.addEventListener("resize", updateSocialFeedStickyState);
  updateSocialFeedStickyState();
}


if (feedScroll && feedStage && profilePanel) {
  function updateProfilePanel() {
    const isVisible = feedScroll.scrollTop > 96;
    profilePanel.classList.toggle("is-visible", isVisible);
    feedStage.classList.toggle("has-profile-panel", isVisible);
  }

  feedScroll.addEventListener("scroll", updateProfilePanel, { passive: true });
  window.addEventListener("resize", updateProfilePanel);
  updateProfilePanel();
}


/* Post comments modal */
const commentsModal = document.getElementById("commentsModal");
const commentsClose = document.getElementById("commentsClose");
const commentsList = document.getElementById("commentsList");
const commentsComposer = document.getElementById("commentsComposer");
const commentsInput = document.getElementById("commentsInput");
const commentsCounter = document.getElementById("commentsCounter");
const commentsModalCount = document.getElementById("commentsModalCount");
const commentsPostAvatar = document.getElementById("commentsPostAvatar");
const commentsPostAuthor = document.getElementById("commentsPostAuthor");
const commentsPostMeta = document.getElementById("commentsPostMeta");
const commentsPostExcerpt = document.getElementById("commentsPostExcerpt");
const commentsPostBadge = document.getElementById("commentsPostBadge");
const commentsSort = document.getElementById("commentsSort");

let activeCommentButton = null;
let activeCommentData = [];
let commentsSortNewest = false;

const commentSets = [
  [
    { name: "Noah Brandt", handle: "@noah", time: "vor 2 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/feed-jonathan.jpg", text: "Bin dabei. Spiele ruhig und kann ab 20:15 Uhr ins Voice.", likes: 5 },
    { name: "Erica Wyatt", handle: "@erica", time: "vor 6 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg", text: "Klingt gut. Welche MMR-Spanne sucht ihr ungefähr?", likes: 3 },
    { name: "Katy Fuller", handle: "@katy", time: "vor 4 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg", text: "Alles zwischen entspannt und konzentriert passt. Hauptsache Kommunikation.", likes: 8, reply: true },
    { name: "Sarah Page", handle: "@sarah", time: "vor 11 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/sarah.jpg", text: "Ich teile das mal mit zwei Leuten aus meiner Freundesliste.", likes: 2 }
  ],
  [
    { name: "Mara Voss", handle: "@mara", time: "vor 5 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/feed-erica.jpg", text: "Der letzte Schuss war absurd. Ich hätte die Runde längst abgeschrieben.", likes: 12 },
    { name: "Katy Fuller", handle: "@katy", time: "vor 9 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg", text: "Genau solche kurzen Moments funktionieren im Feed richtig gut.", likes: 7 },
    { name: "Jonathan Kelly", handle: "@jonathan", time: "vor 7 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg", text: "Ich hatte selbst keine Ahnung, dass der dritte Hunter dort stand.", likes: 10, reply: true },
    { name: "Noah Brandt", handle: "@noah", time: "vor 14 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/feed-jonathan.jpg", text: "Bitte noch das komplette Loadout unter den Clip schreiben.", likes: 4 }
  ],
  [
    { name: "Sarah Page", handle: "@sarah", time: "vor 3 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/sarah.jpg", text: "Trio auf Konsole passt. Gibt es wieder einen eigenen Teamchat?", likes: 9 },
    { name: "Erica Wyatt", handle: "@erica", time: "vor 2 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg", text: "Ja, der Teamchat wird direkt nach der Anmeldung freigeschaltet.", likes: 6, reply: true },
    { name: "Katy Fuller", handle: "@katy", time: "vor 18 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg", text: "Die Kombination aus Extraktion und Kills klingt deutlich fairer.", likes: 11 }
  ],
  [
    { name: "Jonathan Kelly", handle: "@jonathan", time: "vor 8 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg", text: "Die täglichen Quests sind aktuell der schnellste Weg für Rocks.", likes: 6 },
    { name: "Mara Voss", handle: "@mara", time: "vor 16 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/feed-erica.jpg", text: "Der Rahmen lohnt sich. Im Profil sieht er viel besser aus als im Shop.", likes: 5 },
    { name: "Sarah Page", handle: "@sarah", time: "vor 12 Min.", avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/sarah.jpg", text: "Dann ziehe ich die restlichen Quests heute noch durch.", likes: 3, reply: true }
  ]
];

function escapeCommentText(value) {
  return value.replace(/[&<>"']/g, (character) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;"
  })[character]);
}

function renderModalComments() {
  if (!commentsList) return;

  const comments = commentsSortNewest
    ? [...activeCommentData].reverse()
    : activeCommentData;

  commentsList.innerHTML = comments.map((comment) => `
    <article class="comment-item${comment.reply ? " is-reply" : ""}">
      <img class="comment-avatar" src="${comment.avatar}" alt="">
      <div class="comment-bubble">
        <div class="comment-head">
          <strong>${comment.name}</strong>
          <span>${comment.handle} · ${comment.time}</span>
        </div>
        <p>${escapeCommentText(comment.text)}</p>
        <div class="comment-actions">
          <button type="button" class="comment-like">
            <svg><use href="#i-heart"></use></svg><span>${comment.likes}</span>
          </button>
          <button type="button" class="comment-reply" data-handle="${comment.handle}">
            <svg><use href="#i-reply"></use></svg><span>Antworten</span>
          </button>
        </div>
      </div>
    </article>
  `).join("");
}

function updateModalCommentCount(value) {
  if (!commentsModalCount) return;
  commentsModalCount.textContent = `${value} ${value === 1 ? "Kommentar" : "Kommentare"}`;
}

function openCommentsModal(button) {
  if (!commentsModal) return;

  const post = button.closest(".social-post");
  if (!post) return;

  activeCommentButton = button;
  const posts = [...document.querySelectorAll(".social-post")];
  const postIndex = Math.max(0, posts.indexOf(post));
  activeCommentData = commentSets[postIndex % commentSets.length].map((comment) => ({ ...comment }));
  commentsSortNewest = false;

  const postImage = post.querySelector(".post-head > img");
  const postAuthor = post.querySelector(".post-author strong");
  const postMeta = post.querySelector(".post-author span");
  const postExcerpt = post.querySelector(".post-body > p");
  const postBadge = post.querySelector(".post-badge");
  const count = Number.parseInt(button.querySelector("span")?.textContent || "0", 10);

  if (commentsPostAvatar && postImage) commentsPostAvatar.src = postImage.src;
  if (commentsPostAuthor && postAuthor) commentsPostAuthor.textContent = postAuthor.textContent;
  if (commentsPostMeta && postMeta) commentsPostMeta.textContent = postMeta.textContent;
  if (commentsPostExcerpt && postExcerpt) commentsPostExcerpt.textContent = postExcerpt.textContent;
  if (commentsPostBadge) commentsPostBadge.textContent = postBadge?.textContent || "Post";

  updateModalCommentCount(count);
  renderModalComments();

  commentsModal.classList.add("is-open");
  commentsModal.setAttribute("aria-hidden", "false");
  document.body.classList.add("comments-open");

  window.setTimeout(() => commentsInput?.focus(), 120);
}

function closeCommentsModal() {
  if (!commentsModal) return;
  commentsModal.classList.remove("is-open");
  commentsModal.setAttribute("aria-hidden", "true");
  document.body.classList.remove("comments-open");
  activeCommentButton?.focus();
}

document.querySelectorAll(".comment-button").forEach((button) => {
  button.addEventListener("click", () => openCommentsModal(button));
});

commentsClose?.addEventListener("click", closeCommentsModal);

commentsModal?.addEventListener("click", (event) => {
  if (event.target === commentsModal) closeCommentsModal();
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && commentsModal?.classList.contains("is-open")) {
    closeCommentsModal();
  }
});

commentsSort?.addEventListener("click", () => {
  commentsSortNewest = !commentsSortNewest;
  commentsSort.childNodes[0].textContent = commentsSortNewest ? "Neueste " : "Relevant ";
  renderModalComments();
});

commentsList?.addEventListener("click", (event) => {
  const likeButton = event.target.closest(".comment-like");
  if (likeButton) {
    const counter = likeButton.querySelector("span");
    const value = Number.parseInt(counter?.textContent || "0", 10);
    likeButton.classList.toggle("is-liked");
    if (counter) counter.textContent = String(value + (likeButton.classList.contains("is-liked") ? 1 : -1));
    return;
  }

  const replyButton = event.target.closest(".comment-reply");
  if (replyButton && commentsInput) {
    commentsInput.value = `${replyButton.dataset.handle} `;
    commentsInput.dispatchEvent(new Event("input"));
    commentsInput.focus();
  }
});

commentsInput?.addEventListener("input", () => {
  if (commentsCounter) commentsCounter.textContent = `${commentsInput.value.length}/500`;
  commentsInput.style.height = "auto";
  commentsInput.style.height = `${Math.min(commentsInput.scrollHeight, 120)}px`;
});

commentsComposer?.addEventListener("submit", (event) => {
  event.preventDefault();
  if (!commentsInput || !activeCommentButton) return;

  const text = commentsInput.value.trim();
  if (!text) {
    commentsInput.focus();
    return;
  }

  activeCommentData.push({
    name: "Valentina",
    handle: "@valentina",
    time: "gerade eben",
    avatar: "/assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg",
    text,
    likes: 0
  });

  const counter = activeCommentButton.querySelector("span");
  const nextCount = (Number.parseInt(counter?.textContent || "0", 10) || 0) + 1;
  if (counter) counter.textContent = String(nextCount);
  updateModalCommentCount(nextCount);

  commentsInput.value = "";
  commentsInput.dispatchEvent(new Event("input"));
  renderModalComments();

  window.setTimeout(() => {
    const modalContent = document.querySelector(".comments-modal-content");
    if (modalContent) modalContent.scrollTop = modalContent.scrollHeight;
  }, 20);
});


/* Post composer modal */
const postComposerModal = document.getElementById("postComposerModal");
const openPostComposerButton = document.getElementById("openPostComposer");
const postComposerClose = document.getElementById("postComposerClose");
const postComposerInput = document.getElementById("postComposerInput");
const postComposerCounter = document.getElementById("postComposerCounter");
const publishComposerPost = document.getElementById("publishComposerPost");
const saveComposerDraft = document.getElementById("saveComposerDraft");
const composerAudience = document.getElementById("composerAudience");
const composerTypePanel = document.getElementById("composerTypePanel");
const composerAttachment = document.getElementById("composerAttachment");
const composerMediaButton = document.getElementById("composerMediaButton");
const removeComposerAttachment = document.getElementById("removeComposerAttachment");
const composerEmojiButton = document.getElementById("composerEmojiButton");
const composerLfgButton = document.getElementById("composerLfgButton");

let activeComposerType = "Beitrag";
let composerAudiencePublic = true;
let composerHasAttachment = false;

const composerTypeContent = {
  Beitrag: {
    label: "BEITRAG",
    text: "Teile Gedanken, Updates oder einen kurzen Bericht."
  },
  LFG: {
    label: "LOOKING FOR GROUP",
    text: "Erstelle direkt eine kompakte Gruppensuche für andere Hunter."
  },
  Moment: {
    label: "HNT MOMENT",
    text: "Teile einen Clip oder Screenshot aus deiner letzten Runde."
  },
  Frage: {
    label: "COMMUNITY-FRAGE",
    text: "Starte eine Diskussion oder frage die Community nach ihrer Meinung."
  }
};

function escapeComposerText(value) {
  return value.replace(/[&<>"']/g, (character) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;"
  })[character]);
}

function updateComposerState() {
  const length = postComposerInput?.value.length || 0;
  if (postComposerCounter) postComposerCounter.textContent = `${length}/1000`;
  if (publishComposerPost) publishComposerPost.disabled = length === 0;
}

function setComposerType(type) {
  activeComposerType = type;

  document.querySelectorAll("[data-composer-type]").forEach((button) => {
    button.classList.toggle("active", button.dataset.composerType === type);
  });

  const content = composerTypeContent[type];
  if (composerTypePanel && content) {
    composerTypePanel.innerHTML = `
      <div class="composer-type-copy">
        <span>${content.label}</span>
        <strong>${content.text}</strong>
      </div>
    `;
  }
}

function resetPostComposer() {
  if (postComposerInput) {
    postComposerInput.value = "";
    postComposerInput.style.height = "";
  }

  composerAudiencePublic = true;
  composerHasAttachment = false;
  if (composerAudience) composerAudience.querySelector("span").textContent = "Öffentlich";
  if (composerAttachment) composerAttachment.hidden = true;
  setComposerType("Beitrag");
  updateComposerState();
}

function openPostComposer() {
  if (!postComposerModal) return;
  postComposerModal.classList.add("is-open");
  postComposerModal.setAttribute("aria-hidden", "false");
  document.body.classList.add("composer-open");
  window.setTimeout(() => postComposerInput?.focus(), 120);
}

function closePostComposer() {
  if (!postComposerModal) return;
  postComposerModal.classList.remove("is-open");
  postComposerModal.setAttribute("aria-hidden", "true");
  document.body.classList.remove("composer-open");
  openPostComposerButton?.focus();
}

openPostComposerButton?.addEventListener("click", openPostComposer);
postComposerClose?.addEventListener("click", closePostComposer);

postComposerModal?.addEventListener("click", (event) => {
  if (event.target === postComposerModal) closePostComposer();
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && postComposerModal?.classList.contains("is-open")) {
    closePostComposer();
  }
});

document.querySelectorAll("[data-composer-type]").forEach((button) => {
  button.addEventListener("click", () => setComposerType(button.dataset.composerType));
});

postComposerInput?.addEventListener("input", () => {
  updateComposerState();
  postComposerInput.style.height = "auto";
  postComposerInput.style.height = `${Math.min(Math.max(postComposerInput.scrollHeight, 156), 260)}px`;
});

composerAudience?.addEventListener("click", () => {
  composerAudiencePublic = !composerAudiencePublic;
  composerAudience.querySelector("span").textContent = composerAudiencePublic ? "Öffentlich" : "Freunde";
});

composerMediaButton?.addEventListener("click", () => {
  composerHasAttachment = true;
  if (composerAttachment) composerAttachment.hidden = false;
});

removeComposerAttachment?.addEventListener("click", () => {
  composerHasAttachment = false;
  if (composerAttachment) composerAttachment.hidden = true;
});

composerEmojiButton?.addEventListener("click", () => {
  if (!postComposerInput) return;
  const insertion = " 🔥";
  const start = postComposerInput.selectionStart;
  const end = postComposerInput.selectionEnd;
  postComposerInput.value =
    postComposerInput.value.slice(0, start) +
    insertion +
    postComposerInput.value.slice(end);
  postComposerInput.selectionStart = postComposerInput.selectionEnd = start + insertion.length;
  postComposerInput.dispatchEvent(new Event("input"));
  postComposerInput.focus();
});

composerLfgButton?.addEventListener("click", () => setComposerType("LFG"));

saveComposerDraft?.addEventListener("click", () => {
  showToast("Entwurf gespeichert");
  closePostComposer();
});

function createComposerExtraMarkup() {
  if (activeComposerType === "LFG") {
    return `
      <div class="lfg-strip">
        <span><b>Hunt: Showdown</b><small>EU · Console</small></span>
        <span><b>Heute</b><small>Start</small></span>
        <span><b>1 / 3</b><small>Team</small></span>
        <button data-toast="LFG geöffnet">Mitmachen <svg><use href="#i-arrow"></use></svg></button>
      </div>
    `;
  }

  if (activeComposerType === "Moment" || composerHasAttachment) {
    return `
      <div class="moment-preview">
        <div class="moment-copy">
          <span class="moment-label">HNT MOMENT</span>
          <strong>Neuer Moment von Valentina</strong>
          <small>Gerade eben · Stillwater Bayou</small>
        </div>
        <button data-toast="Moment abgespielt">▶</button>
      </div>
    `;
  }

  if (activeComposerType === "Frage") {
    return `
      <div class="poll">
        <button><span>Option 1</span><b>0%</b></button>
        <button><span>Option 2</span><b>0%</b></button>
        <small>Noch keine Stimmen</small>
      </div>
    `;
  }

  return "";
}

function bindNewPostInteractions(post) {
  const likeButton = post.querySelector(".like-button");
  const saveButton = post.querySelector(".save-button");
  const commentButton = post.querySelector(".comment-button");

  likeButton?.addEventListener("click", () => {
    likeButton.classList.toggle("liked");
    const counter = likeButton.querySelector("span");
    const value = Number.parseInt(counter?.textContent || "0", 10);
    if (counter) counter.textContent = String(value + (likeButton.classList.contains("liked") ? 1 : -1));
  });

  saveButton?.addEventListener("click", () => {
    saveButton.classList.toggle("saved");
    showToast(saveButton.classList.contains("saved") ? "Gespeichert" : "Nicht mehr gespeichert");
  });

  commentButton?.addEventListener("click", () => openCommentsModal(commentButton));

  post.querySelectorAll("[data-toast]").forEach((element) => {
    element.addEventListener("click", () => showToast(element.dataset.toast));
  });

  post.querySelectorAll(".poll button").forEach((button) => {
    button.addEventListener("click", () => {
      post.querySelectorAll(".poll button").forEach((item) => item.classList.remove("selected"));
      button.classList.add("selected");
      showToast("Stimme gespeichert");
    });
  });
}

publishComposerPost?.addEventListener("click", () => {
  const text = postComposerInput?.value.trim() || "";
  if (!text) {
    postComposerInput?.focus();
    return;
  }

  const badgeClass = {
    Beitrag: "discussion",
    LFG: "",
    Moment: "moment",
    Frage: "discussion"
  }[activeComposerType];

  const badgeText = {
    Beitrag: "Beitrag",
    LFG: "LFG",
    Moment: "Moment",
    Frage: "Frage"
  }[activeComposerType];

  const post = document.createElement("article");
  post.className = "social-post";
  post.innerHTML = `
    <header class="post-head">
      <img src="/assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg" alt="Valentina">
      <div class="post-author">
        <strong>Valentina</strong>
        <span>@valentina · gerade eben · ${composerAudiencePublic ? "Öffentlich" : "Freunde"}</span>
      </div>
      <span class="post-badge ${badgeClass}">${badgeText}</span>
      <button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
    </header>
    <div class="post-body">
      <p>${escapeComposerText(text)}</p>
      ${createComposerExtraMarkup()}
    </div>
    <footer class="post-actions">
      <button class="like-button"><svg><use href="#i-heart"></use></svg><span>0</span></button>
      <button class="comment-button" aria-label="Kommentare öffnen"><svg><use href="#i-comment"></use></svg><span>0</span></button>
      <button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
      <button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
    </footer>
  `;

  document.querySelector(".post-list")?.prepend(post);
  bindNewPostInteractions(post);

  const profilePosts = document.querySelector(".profile-stats article:nth-child(3) strong");
  if (profilePosts) {
    profilePosts.textContent = String((Number.parseInt(profilePosts.textContent, 10) || 0) + 1);
  }

  closePostComposer();
  resetPostComposer();
  showToast("Post veröffentlicht");

  const feedCard = document.querySelector(".social-feed-card");
  feedCard?.scrollIntoView({ behavior: "smooth", block: "start" });
});

resetPostComposer();


/* Mobile profile and stats fullscreen sheets */
const mobileDashboardQuery = window.matchMedia("(max-width: 899px)");
const mobileStatsTrigger = document.getElementById("mobileStatsTrigger");
const mobileProfileTrigger = document.getElementById("mobileProfileTrigger");
const mobileProfileModal = document.getElementById("mobileProfileModal");
const mobileStatsModal = document.getElementById("mobileStatsModal");
const mobileProfileContent = document.getElementById("mobileProfileContent");
const mobileStatsContent = document.getElementById("mobileStatsContent");

const mobileProfilePanel = document.getElementById("profilePanel");
const mobileSchedulePanel = document.querySelector(".fixed-schedule");
const mobileCompositionPanel = document.getElementById("compositionPanel");
const mobileSalaryPanel = document.querySelector(".salary-attendance-card");

function createMobilePanelMarker(node, label) {
  if (!node || !node.parentNode) return null;
  const marker = document.createComment(label);
  node.parentNode.insertBefore(marker, node);
  return marker;
}

const mobilePanelMarkers = {
  profile: createMobilePanelMarker(mobileProfilePanel, "mobile-profile-origin"),
  schedule: createMobilePanelMarker(mobileSchedulePanel, "mobile-schedule-origin"),
  composition: createMobilePanelMarker(mobileCompositionPanel, "mobile-composition-origin"),
  salary: createMobilePanelMarker(mobileSalaryPanel, "mobile-salary-origin")
};

function restoreMobilePanel(node, marker) {
  if (!node || !marker?.parentNode) return;
  marker.parentNode.insertBefore(node, marker.nextSibling);
}

function closeMobileDashboardModal(modal) {
  if (!modal) return;
  modal.classList.remove("is-open");
  modal.setAttribute("aria-hidden", "true");

  if (
    !mobileProfileModal?.classList.contains("is-open") &&
    !mobileStatsModal?.classList.contains("is-open")
  ) {
    document.body.classList.remove("mobile-dashboard-modal-open");
  }
}

function openMobileDashboardModal(modal) {
  if (!mobileDashboardQuery.matches || !modal) return;

  closeMobileDashboardModal(
    modal === mobileProfileModal ? mobileStatsModal : mobileProfileModal
  );

  modal.classList.add("is-open");
  modal.setAttribute("aria-hidden", "false");
  document.body.classList.add("mobile-dashboard-modal-open");

  const body = modal.querySelector(".mobile-dashboard-sheet-body");
  if (body) body.scrollTop = 0;
}

function relocateDashboardPanels() {
  if (mobileDashboardQuery.matches) {
    if (mobileProfilePanel && mobileProfileContent) {
      mobileProfileContent.appendChild(mobileProfilePanel);
    }

    if (mobileStatsContent) {
      if (mobileSchedulePanel) mobileStatsContent.appendChild(mobileSchedulePanel);
      if (mobileCompositionPanel) mobileStatsContent.appendChild(mobileCompositionPanel);
      if (mobileSalaryPanel) mobileStatsContent.appendChild(mobileSalaryPanel);
    }

    return;
  }

  closeMobileDashboardModal(mobileProfileModal);
  closeMobileDashboardModal(mobileStatsModal);

  restoreMobilePanel(mobileProfilePanel, mobilePanelMarkers.profile);
  restoreMobilePanel(mobileSchedulePanel, mobilePanelMarkers.schedule);
  restoreMobilePanel(mobileCompositionPanel, mobilePanelMarkers.composition);
  restoreMobilePanel(mobileSalaryPanel, mobilePanelMarkers.salary);
}

/* Capture is used so the existing desktop "Profile opened" toast remains
   untouched, while mobile opens the fullscreen profile sheet instead. */
mobileProfileTrigger?.addEventListener("click", (event) => {
  if (!mobileDashboardQuery.matches) return;
  event.preventDefault();
  event.stopImmediatePropagation();
  openMobileDashboardModal(mobileProfileModal);
}, true);

mobileStatsTrigger?.addEventListener("click", () => {
  openMobileDashboardModal(mobileStatsModal);
});

document.querySelectorAll("[data-mobile-modal-close]").forEach((button) => {
  button.addEventListener("click", () => {
    closeMobileDashboardModal(
      button.dataset.mobileModalClose === "profile"
        ? mobileProfileModal
        : mobileStatsModal
    );
  });
});

[mobileProfileModal, mobileStatsModal].forEach((modal) => {
  modal?.addEventListener("click", (event) => {
    if (event.target === modal) closeMobileDashboardModal(modal);
  });
});

document.addEventListener("keydown", (event) => {
  if (event.key !== "Escape") return;

  if (mobileProfileModal?.classList.contains("is-open")) {
    closeMobileDashboardModal(mobileProfileModal);
  } else if (mobileStatsModal?.classList.contains("is-open")) {
    closeMobileDashboardModal(mobileStatsModal);
  }
});

mobileDashboardQuery.addEventListener?.("change", relocateDashboardPanels);
window.addEventListener("resize", relocateDashboardPanels);
relocateDashboardPanels();

/* Ensure real activity content is present when the mobile stats sheet opens. */
function hydrateMobileStatsActivity() {
  if (!mobileDashboardQuery?.matches) return;

  const activityList = document.getElementById("compositionActivity");
  const activityState = document.getElementById("activityState");
  if (!activityList || activityList.querySelector(".activity-item")) return;

  const items = [
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg", title: "Katy hat ein LFG erstellt", meta: "EU · Console", time: "jetzt" },
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg", title: "Jonathan hat ein Moment geteilt", meta: "Stillwater Bayou", time: "2m" },
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/sarah.jpg", title: "Sarah erhielt einen Badge", meta: "Bayou Veteran", time: "6m" },
    { image: "/assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg", title: "Erica ist einem Cup beigetreten", meta: "Summer Hunt", time: "11m" }
  ];

  activityList.innerHTML = items.map((item) => `
    <article class="activity-item">
      <img src="${item.image}" alt="">
      <div><strong>${item.title}</strong><small>${item.meta}</small></div>
      <span>${item.time}</span>
    </article>
  `).join("");

  if (activityState) activityState.textContent = "Live";
}

mobileStatsTrigger?.addEventListener("click", () => {
  window.setTimeout(hydrateMobileStatsActivity, 30);
});


/* Header dropdown menus */
const headerActionMenus = [...document.querySelectorAll(".header-action-menu")];

function closeHeaderDropdowns(exceptMenu = null) {
  headerActionMenus.forEach((menu) => {
    if (menu === exceptMenu) return;
    menu.classList.remove("is-open");
    menu.querySelector(".header-dropdown-trigger")?.setAttribute("aria-expanded", "false");
  });
}

headerActionMenus.forEach((menu) => {
  const trigger = menu.querySelector(".header-dropdown-trigger");
  if (!trigger) return;

  trigger.addEventListener("click", (event) => {
    /* The existing mobile profile handler uses capture and stops this
       event, so mobile still opens the fullscreen profile sheet. */
    const willOpen = !menu.classList.contains("is-open");
    closeHeaderDropdowns(menu);
    menu.classList.toggle("is-open", willOpen);
    trigger.setAttribute("aria-expanded", String(willOpen));
    event.stopPropagation();
  });

  menu.querySelector(".header-dropdown")?.addEventListener("click", (event) => {
    event.stopPropagation();
  });
});

document.addEventListener("click", () => closeHeaderDropdowns());

document.addEventListener("keydown", (event) => {
  if (event.key !== "Escape") return;
  closeHeaderDropdowns();
});

function updateHeaderBadge(type, value) {
  const badge = document.querySelector(`[data-header-badge="${type}"]`);
  const countLabel = document.querySelector(`[data-dropdown-count="${type}"]`);
  const safeValue = Math.max(0, Number(value) || 0);

  if (badge) {
    badge.textContent = String(safeValue);
    badge.classList.toggle("is-empty", safeValue === 0);
  }

  if (countLabel) {
    const suffix = countLabel.textContent.replace(/^[\d.\s]+/, "").trim()
      || (type === "friends" ? "offen" : "ungelesen");
    countLabel.textContent = `${safeValue} ${suffix}`;
  }
}

function currentHeaderBadgeValue(type) {
  const badge = document.querySelector(`[data-header-badge="${type}"]`);
  return Number.parseInt(badge?.textContent || "0", 10) || 0;
}

const sharedHeader = document.querySelector("[data-hnt-shared-header]");

async function headerJson(url, options = {}) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";
  const response = await fetch(url, {
    credentials: "same-origin",
    ...options,
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...(csrfToken ? {"X-CSRF-TOKEN": csrfToken} : {}),
      ...(options.headers || {}),
    },
  });

  if (!response.ok) {
    throw new Error(`Header request failed (${response.status})`);
  }

  return response.json();
}

const headerPanels = {
  notifications: {
    url: sharedHeader?.dataset.headerNotificationsUrl,
    list: ".header-notification-list",
    count: (payload) => payload.unread_count,
  },
  messages: {
    url: sharedHeader?.dataset.headerMessagesUrl,
    list: ".header-message-list",
    count: (payload) => payload.unread_count,
  },
  friends: {
    url: sharedHeader?.dataset.headerFriendsUrl,
    list: ".header-request-list",
    count: (payload) => payload.count,
  },
};

async function refreshHeaderPanel(type) {
  const panel = headerPanels[type];
  if (!sharedHeader || !panel?.url) return;

  const payload = await headerJson(panel.url);
  if (payload.authenticated === false) return;

  const list = sharedHeader.querySelector(panel.list);
  if (list && typeof payload.html === "string") {
    list.innerHTML = payload.html;
  }
  updateHeaderBadge(type, panel.count(payload));
}

async function refreshHeaderBadges() {
  const url = sharedHeader?.dataset.headerBadgesUrl;
  if (!url) return;

  const payload = await headerJson(url);
  if (payload.authenticated === false) return;

  updateHeaderBadge("notifications", payload.notifications_unread);
  updateHeaderBadge("messages", payload.messages_unread);
  updateHeaderBadge("friends", payload.friend_request_count);
}

async function refreshSharedHeader() {
  if (!sharedHeader) return;

  await Promise.allSettled([
    refreshHeaderBadges(),
    refreshHeaderPanel("notifications"),
    refreshHeaderPanel("messages"),
    refreshHeaderPanel("friends"),
  ]);
}

if (sharedHeader?.dataset.headerBadgesUrl) {
  void refreshSharedHeader();
}

sharedHeader?.addEventListener("submit", async (event) => {
  const form = event.target.closest("form");
  if (!form) return;

  const isNotification = form.matches("[data-hnt-notification-read]");
  const isFriendRequest = form.matches("[data-hnt-friend-request-action]");
  if (!isNotification && !isFriendRequest) return;

  event.preventDefault();
  const submitButton = event.submitter || form.querySelector('button[type="submit"]');
  if (submitButton) submitButton.disabled = true;

  try {
    const payload = await headerJson(form.action, {
      method: (form.method || "POST").toUpperCase(),
      body: new FormData(form),
    });

    if (payload.message) showToast(payload.message);

    if (isNotification && payload.action_url) {
      window.location.assign(payload.action_url);
      return;
    }

    await Promise.allSettled([
      refreshHeaderBadges(),
      refreshHeaderPanel(isNotification ? "notifications" : "friends"),
    ]);
  } catch (error) {
    showToast("Aktion konnte nicht ausgeführt werden");
    if (submitButton) submitButton.disabled = false;
  }
});

sharedHeader?.querySelector(".header-mark-all")?.addEventListener("click", async (event) => {
  const button = event.currentTarget;
  const url = sharedHeader.dataset.headerNotificationsReadAllUrl;
  if (!url) return;

  button.disabled = true;
  try {
    const payload = await headerJson(url, {method: "POST"});
    updateHeaderBadge("notifications", payload.unread_count ?? 0);
    await refreshHeaderPanel("notifications");
    showToast(payload.message || "Alle Benachrichtigungen wurden als gelesen markiert");
  } catch (error) {
    showToast("Benachrichtigungen konnten nicht aktualisiert werden");
  } finally {
    button.disabled = false;
  }
});


/* HNT.ROCKS main navigation dropdowns */
const mainNavItems = [...document.querySelectorAll(".main-nav-item")];

function closeMainNavMenus(exceptItem = null) {
  mainNavItems.forEach((item) => {
    if (item === exceptItem) return;
    item.classList.remove("is-open");
    item.querySelector(".main-nav-trigger")?.setAttribute("aria-expanded", "false");
  });
}

mainNavItems.forEach((item) => {
  const trigger = item.querySelector(":scope > .main-nav-trigger");
  const dropdown = item.querySelector(":scope > .main-nav-dropdown");
  if (!trigger || !dropdown) return;

  trigger.addEventListener("click", (event) => {
    const willOpen = !item.classList.contains("is-open");

    closeMainNavMenus(item);
    if (typeof closeHeaderDropdowns === "function") {
      closeHeaderDropdowns();
    }

    item.classList.toggle("is-open", willOpen);
    trigger.setAttribute("aria-expanded", String(willOpen));
    event.stopPropagation();
  });

  dropdown.addEventListener("click", (event) => {
    event.stopPropagation();
  });

  dropdown.querySelectorAll("[data-toast]").forEach((button) => {
    button.addEventListener("click", () => closeMainNavMenus());
  });
});

document.querySelectorAll(".header-dropdown-trigger").forEach((trigger) => {
  trigger.addEventListener("click", () => closeMainNavMenus(), true);
});

document.addEventListener("click", () => closeMainNavMenus());

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") closeMainNavMenus();
});
