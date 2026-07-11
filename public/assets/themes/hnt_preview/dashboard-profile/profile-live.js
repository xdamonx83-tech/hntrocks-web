(() => {
  const profileTabs = [...document.querySelectorAll('[data-profile-tab]')];
  const profilePanels = [...document.querySelectorAll('[data-profile-panel]')];
  const profileTabTitle = document.getElementById('profileTabTitle');
  const profileMainScroller = document.querySelector('.profile-page-main');

  function activateProfileTab(tabName, shouldFocus = false) {
    profileTabs.forEach((button) => {
      const active = button.dataset.profileTab === tabName;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', String(active));
      if (active && shouldFocus) button.focus();
    });

    profilePanels.forEach((panel) => {
      const active = panel.dataset.profilePanel === tabName;
      panel.classList.toggle('active', active);
      panel.hidden = !active;
    });

    const activeButton = profileTabs.find((button) => button.dataset.profileTab === tabName);
    if (profileTabTitle && activeButton) {
      profileTabTitle.textContent = activeButton.dataset.title || activeButton.textContent.trim();
    }

    if (profileMainScroller && window.matchMedia('(min-width: 900px)').matches) {
      const feedTop = document.querySelector('.profile-post-feed')?.offsetTop || 0;
      profileMainScroller.scrollTo({ top: Math.max(0, feedTop - 8), behavior: 'smooth' });
    }
  }

  profileTabs.forEach((button) => {
    button.addEventListener('click', () => activateProfileTab(button.dataset.profileTab));
    button.addEventListener('keydown', (event) => {
      if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
      event.preventDefault();
      const currentIndex = profileTabs.indexOf(button);
      const direction = event.key === 'ArrowRight' ? 1 : -1;
      const nextIndex = (currentIndex + direction + profileTabs.length) % profileTabs.length;
      activateProfileTab(profileTabs[nextIndex].dataset.profileTab, true);
    });
  });

  const share = async (url, title = document.title) => {
    try {
      if (navigator.share) {
        await navigator.share({ title, url });
        return;
      }

      if (navigator.clipboard) {
        await navigator.clipboard.writeText(url);
        if (typeof window.showToast === 'function') window.showToast('Link kopiert');
      }
    } catch (error) {
      if (error?.name !== 'AbortError' && typeof window.showToast === 'function') {
        window.showToast('Teilen war nicht möglich');
      }
    }
  };

  document.querySelectorAll('[data-profile-share]').forEach((button) => {
    button.addEventListener('click', () => share(window.location.href));
  });

  document.querySelectorAll('[data-profile-share-url]').forEach((button) => {
    button.addEventListener('click', () => share(button.dataset.profileShareUrl || window.location.href, 'HNT.ROCKS Beitrag'));
  });

  document.getElementById('openEmptyPostComposer')?.addEventListener('click', () => {
    document.getElementById('openPostComposer')?.click();
  });

  const setActiveFilter = (buttons, activeButton) => {
    buttons.forEach((button) => button.classList.toggle('active', button === activeButton));
  };

  const friendFilterButtons = [...document.querySelectorAll('[data-profile-friend-filter]')];
  const friendCards = [...document.querySelectorAll('[data-profile-friend-card]')];
  const friendFilterEmpty = document.querySelector('[data-profile-friends-filter-empty]');

  friendFilterButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const filter = button.dataset.profileFriendFilter || 'all';
      setActiveFilter(friendFilterButtons, button);
      let visible = 0;

      friendCards.forEach((card) => {
        const matches = filter === 'all'
          || (filter === 'online' && card.dataset.friendOnline === '1')
          || (filter === 'ready' && card.dataset.friendReady === '1');
        card.hidden = !matches;
        if (matches) visible += 1;
      });

      if (friendFilterEmpty) friendFilterEmpty.hidden = visible > 0;
    });
  });

  const momentSortButtons = [...document.querySelectorAll('[data-profile-moment-sort]')];
  const momentGallery = document.querySelector('[data-profile-moment-gallery]');

  momentSortButtons.forEach((button) => {
    button.addEventListener('click', () => {
      if (!momentGallery) return;
      const mode = button.dataset.profileMomentSort || 'newest';
      setActiveFilter(momentSortButtons, button);
      const cards = [...momentGallery.querySelectorAll('[data-profile-moment-card]')];

      cards.sort((left, right) => {
        const leftDate = Number(left.dataset.momentPublished || 0);
        const rightDate = Number(right.dataset.momentPublished || 0);
        const leftLikes = Number(left.dataset.momentLikes || 0);
        const rightLikes = Number(right.dataset.momentLikes || 0);

        if (mode === 'popular') return rightLikes - leftLikes || rightDate - leftDate;
        if (mode === 'oldest') return leftDate - rightDate;
        return rightDate - leftDate;
      });

      cards.forEach((card) => momentGallery.appendChild(card));
    });
  });

  const badgeFilterButtons = [...document.querySelectorAll('[data-profile-badge-filter]')];
  const badgeCards = [...document.querySelectorAll('[data-profile-badge-card]')];
  const badgeFilterEmpty = document.querySelector('[data-profile-badges-filter-empty]');

  badgeFilterButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const filter = button.dataset.profileBadgeFilter || 'all';
      setActiveFilter(badgeFilterButtons, button);
      let visible = 0;

      badgeCards.forEach((card) => {
        const rarity = card.dataset.badgeRarity || 'common';
        const matches = filter === 'all'
          || (filter === 'rare' && ['rare', 'epic', 'legendary'].includes(rarity))
          || (filter === 'recent' && card.dataset.badgeRecent === '1');
        card.hidden = !matches;
        if (matches) visible += 1;
      });

      if (badgeFilterEmpty) badgeFilterEmpty.hidden = visible > 0;
    });
  });

  activateProfileTab('posts');
})();
