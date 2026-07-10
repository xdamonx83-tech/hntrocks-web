/* Real feed preview integration
   The visual demo remains intact. Real Laravel posts are prepended and can be
   removed again without touching the original template markup. */
(() => {
  const realFeedList = document.querySelector(".post-list");
  const realFeedTabs = [...document.querySelectorAll(".feed-tabs > button:not(.compose-button)")];

  if (!realFeedList || !window.fetch) return;

  const realFeedEndpoint = `${window.location.pathname}?data=1`;
  let realFeedRequestId = 0;

  const escapeRealFeedText = (value = "") => String(value).replace(/[&<>"']/g, (character) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;"
  })[character]);

  const formatRealFeedCount = (value) => {
    const number = Number.parseInt(value, 10) || 0;
    return new Intl.NumberFormat(document.documentElement.lang || "de").format(number);
  };

  const updateRealFeedProfile = (profile, badges) => {
    if (!profile) return;

    const avatarSelectors = [
      ".header-profile-avatar img",
      ".profile-avatar-wrap img",
      ".composer-avatar img",
      ".comments-composer > img"
    ];

    avatarSelectors.forEach((selector) => {
      const image = document.querySelector(selector);
      if (!image) return;
      image.src = profile.avatar;
      image.alt = profile.name;
    });

    const nameSelectors = [
      ".header-profile-summary > div:last-child strong",
      ".profile-meta strong",
      ".composer-identity > div:last-child strong"
    ];

    nameSelectors.forEach((selector) => {
      const element = document.querySelector(selector);
      if (element) element.textContent = profile.name;
    });

    const handleSelectors = [
      ".profile-meta span",
      ".composer-identity > div:last-child span"
    ];

    handleSelectors.forEach((selector) => {
      const element = document.querySelector(selector);
      if (element) element.textContent = selector.includes("profile-meta")
        ? `${profile.handle} · Online`
        : profile.handle;
    });

    const headerSummary = document.querySelector(".header-profile-summary > div:last-child span");
    if (headerSummary) headerSummary.textContent = `${profile.handle} · Level ${profile.level}`;

    const greeting = document.querySelector(".feed-scroll h1");
    if (greeting) greeting.textContent = `Hello ${profile.name}`;

    const profileStats = document.querySelectorAll(".profile-stats article strong");
    if (profileStats[0]) profileStats[0].textContent = formatRealFeedCount(profile.rocks);
    if (profileStats[1]) profileStats[1].textContent = formatRealFeedCount(profile.friends);
    if (profileStats[2]) profileStats[2].textContent = formatRealFeedCount(profile.posts);

    const headerStats = document.querySelectorAll(".header-profile-stats span strong");
    if (headerStats[0]) headerStats[0].textContent = formatRealFeedCount(profile.rocks);
    if (headerStats[1]) headerStats[1].textContent = formatRealFeedCount(profile.friends);
    if (headerStats[2]) headerStats[2].textContent = formatRealFeedCount(profile.posts);

    const levelLabel = document.querySelector(".profile-level .level-row span");
    if (levelLabel) levelLabel.textContent = `Level ${profile.level}`;

    if (typeof updateHeaderBadge === "function" && badges) {
      updateHeaderBadge("messages", badges.messages || 0);
      updateHeaderBadge("notifications", badges.notifications || 0);
      updateHeaderBadge("friends", badges.friends || 0);
    }
  };

  const renderRealFeedMedia = (media = [], permalink = "#") => {
    if (!Array.isArray(media) || media.length === 0) return "";

    const visible = media.slice(0, 4);
    const items = visible.map((item, index) => {
      const extra = index === visible.length - 1 && media.length > visible.length
        ? `<span class="real-post-media-more">+${media.length - visible.length}</span>`
        : "";

      if (item.type === "image") {
        return `
          <a class="real-post-media-item" href="${escapeRealFeedText(permalink)}" aria-label="Beitrag öffnen">
            <img src="${escapeRealFeedText(item.url)}" alt="${escapeRealFeedText(item.alt || "")}" loading="lazy">
            ${extra}
          </a>
        `;
      }

      if (item.type === "video") {
        return `
          <div class="real-post-media-item real-post-video-item">
            <video controls muted playsinline preload="metadata">
              <source src="${escapeRealFeedText(item.url)}" type="${escapeRealFeedText(item.mime || "video/mp4")}">
            </video>
            ${extra}
          </div>
        `;
      }

      return `
        <a class="real-post-media-item real-post-file-item" href="${escapeRealFeedText(item.url)}" target="_blank" rel="noopener">
          <span>Datei öffnen</span>
          ${extra}
        </a>
      `;
    }).join("");

    return `<div class="real-post-media-grid real-post-media-count-${Math.min(media.length, 4)}">${items}</div>`;
  };

  const renderRealFeedPoll = (poll) => {
    if (!poll || !Array.isArray(poll.options) || poll.options.length === 0) return "";

    const options = poll.options.map((option) => `
      <button type="button" class="${option.selected ? "selected" : ""}" data-real-poll-option>
        <span>${escapeRealFeedText(option.body)}</span>
        <b>${formatRealFeedCount(option.percent)}%</b>
      </button>
    `).join("");

    return `
      <div class="poll real-feed-poll">
        <strong class="real-feed-poll-question">${escapeRealFeedText(poll.question || "Community-Umfrage")}</strong>
        ${options}
        <small>${formatRealFeedCount(poll.total_votes)} Stimmen</small>
      </div>
    `;
  };

  const createRealFeedPost = (post) => {
    const article = document.createElement("article");
    article.className = "social-post real-feed-post";
    article.dataset.realFeedPost = String(post.id);
    article.dataset.realPermalink = post.permalink || "#";

    const body = post.body
      ? `<p>${escapeRealFeedText(post.body).replace(/\n/g, "<br>")}</p>`
      : "";

    const media = renderRealFeedMedia(post.media, post.permalink);
    const poll = renderRealFeedPoll(post.poll);
    const metaSuffix = post.team?.name || post.visibility || "Öffentlich";
    const pinned = post.is_pinned ? `<span class="real-feed-pinned">Angeheftet</span>` : "";

    article.innerHTML = `
      <header class="post-head">
        <a class="real-feed-author-avatar" href="${escapeRealFeedText(post.author.profile_url || "#")}">
          <img alt="${escapeRealFeedText(post.author.name)}" src="${escapeRealFeedText(post.author.avatar)}">
        </a>
        <div class="post-author">
          <a href="${escapeRealFeedText(post.author.profile_url || "#")}"><strong>${escapeRealFeedText(post.author.name)}</strong></a>
          <span>${escapeRealFeedText(post.author.handle)} · ${escapeRealFeedText(post.created_at)} · ${escapeRealFeedText(metaSuffix)}</span>
        </div>
        ${pinned}
        <span class="post-badge ${escapeRealFeedText(post.badge_class || "discussion")}">${escapeRealFeedText(post.badge || "Beitrag")}</span>
        <button class="post-more" type="button" data-real-post-more aria-label="Beitragsoptionen"><svg><use href="#i-more"></use></svg></button>
      </header>
      <div class="post-body">
        ${body}
        ${media}
        ${poll}
      </div>
      <footer class="post-actions">
        <button class="like-button ${post.viewer?.reacted ? "liked" : ""}" type="button" data-real-preview-like>
          <svg><use href="#i-heart"></use></svg><span>${formatRealFeedCount(post.counts?.reactions)}</span>
        </button>
        <button aria-label="Kommentare öffnen" class="comment-button" type="button" data-real-preview-comments>
          <svg><use href="#i-comment"></use></svg><span>${formatRealFeedCount(post.counts?.comments)}</span>
        </button>
        <button type="button" data-real-preview-share>
          <svg><use href="#i-share"></use></svg><span>Teilen</span>
        </button>
        <button class="save-button ${post.viewer?.bookmarked ? "saved" : ""}" type="button" data-real-preview-save aria-label="Speichern">
          <svg><use href="#i-bookmark"></use></svg>
        </button>
      </footer>
    `;

    const likeButton = article.querySelector("[data-real-preview-like]");
    likeButton?.addEventListener("click", () => {
      likeButton.classList.toggle("liked");
      const counter = likeButton.querySelector("span");
      const value = Number.parseInt((counter?.textContent || "0").replace(/\D/g, ""), 10) || 0;
      if (counter) counter.textContent = formatRealFeedCount(value + (likeButton.classList.contains("liked") ? 1 : -1));
      showToast("Vorschau: echter Like wird im nächsten Schritt angebunden");
    });

    const saveButton = article.querySelector("[data-real-preview-save]");
    saveButton?.addEventListener("click", () => {
      saveButton.classList.toggle("saved");
      showToast("Vorschau: echtes Speichern wird im nächsten Schritt angebunden");
    });

    article.querySelectorAll("[data-real-poll-option]").forEach((button) => {
      button.addEventListener("click", () => {
        article.querySelectorAll("[data-real-poll-option]").forEach((item) => item.classList.remove("selected"));
        button.classList.add("selected");
        showToast("Vorschau: echte Abstimmung wird im nächsten Schritt angebunden");
      });
    });

    article.querySelector("[data-real-preview-share]")?.addEventListener("click", async () => {
      const url = post.permalink || window.location.href;

      try {
        if (navigator.share) {
          await navigator.share({ title: `${post.author.name} auf HNT.rocks`, url });
        } else if (navigator.clipboard) {
          await navigator.clipboard.writeText(url);
          showToast("Link kopiert");
        }
      } catch (error) {
        if (error?.name !== "AbortError") showToast("Teilen war nicht möglich");
      }
    });

    article.querySelector("[data-real-post-more]")?.addEventListener("click", () => {
      showToast("Beitragsmenü wird im nächsten Schritt echt angebunden");
    });

    article.querySelector("[data-real-preview-comments]")?.addEventListener("click", (event) => {
      const button = event.currentTarget;
      if (!commentsModal) return;

      commentsModal.dataset.realPreview = "1";
      activeCommentButton = button;
      activeCommentData = (post.comments || []).map((comment) => ({
        name: comment.name,
        handle: comment.handle,
        time: comment.time,
        avatar: comment.avatar,
        text: comment.text,
        likes: comment.likes,
        reply: false
      }));
      commentsSortNewest = false;

      if (commentsPostAvatar) commentsPostAvatar.src = post.author.avatar;
      if (commentsPostAuthor) commentsPostAuthor.textContent = post.author.name;
      if (commentsPostMeta) commentsPostMeta.textContent = `${post.author.handle} · ${post.created_at}`;
      if (commentsPostExcerpt) commentsPostExcerpt.textContent = post.excerpt || post.body || "Beitrag";
      if (commentsPostBadge) commentsPostBadge.textContent = post.badge || "Beitrag";

      updateModalCommentCount(post.counts?.comments || activeCommentData.length);
      renderModalComments();

      commentsModal.classList.add("is-open");
      commentsModal.setAttribute("aria-hidden", "false");
      document.body.classList.add("comments-open");
      window.setTimeout(() => commentsInput?.focus(), 120);
    });

    return article;
  };

  const renderRealFeedPosts = (payload) => {
    document.querySelectorAll("[data-real-feed-post]").forEach((node) => node.remove());
    document.body.classList.toggle("real-feed-following-mode", payload.mode === "following");

    if (!Array.isArray(payload.posts) || payload.posts.length === 0) {
      showToast(payload.mode === "following"
        ? "Keine Beiträge von Freunden gefunden"
        : "Noch keine echten Feed-Beiträge vorhanden");
      return;
    }

    const fragment = document.createDocumentFragment();
    payload.posts.forEach((post) => fragment.appendChild(createRealFeedPost(post)));
    realFeedList.prepend(fragment);
  };

  const loadRealFeed = async (mode = "for-you") => {
    const requestId = ++realFeedRequestId;
    realFeedList.classList.add("is-loading-real-feed");

    try {
      const response = await fetch(`${realFeedEndpoint}&mode=${encodeURIComponent(mode)}`, {
        credentials: "same-origin",
        headers: { "Accept": "application/json" }
      });

      if (!response.ok) throw new Error(`Feed request failed with ${response.status}`);

      const payload = await response.json();
      if (requestId !== realFeedRequestId) return;

      updateRealFeedProfile(payload.profile, payload.badges);
      renderRealFeedPosts(payload);
    } catch (error) {
      console.error("HNT real feed preview failed", error);
      showToast("Echte Feed-Daten konnten nicht geladen werden");
    } finally {
      if (requestId === realFeedRequestId) realFeedList.classList.remove("is-loading-real-feed");
    }
  };

  realFeedTabs.forEach((button, index) => {
    button.addEventListener("click", () => {
      loadRealFeed(index === 1 ? "following" : "for-you");
    });
  });

  const openStaticPreviewComments = (button) => {
    if (!commentsModal) return;

    const post = button.closest(".social-post");
    if (!post) return;

    const staticPosts = [...document.querySelectorAll(".social-post:not([data-real-feed-post])")];
    const postIndex = Math.max(0, staticPosts.indexOf(post));

    commentsModal.dataset.realPreview = "0";
    activeCommentButton = button;
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
  };

  document.querySelectorAll(".social-post:not([data-real-feed-post]) .comment-button").forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();
      openStaticPreviewComments(button);
    }, true);
  });

  commentsComposer?.addEventListener("submit", (event) => {
    if (commentsModal?.dataset.realPreview !== "1") return;

    event.preventDefault();
    event.stopImmediatePropagation();
    showToast("Echte Kommentare werden im nächsten Schritt gespeichert");
  }, true);

  loadRealFeed("for-you");
})();
