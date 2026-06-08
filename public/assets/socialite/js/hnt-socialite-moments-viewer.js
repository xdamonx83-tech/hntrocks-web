(() => {
  const ready = (fn) => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  };

  ready(() => {
    const drawer = document.querySelector('[data-socialite-moment-comments]');
    const backdrop = document.querySelector('[data-socialite-moment-comments-backdrop]');
    const moreSheet = document.querySelector('[data-socialite-moment-more]');
    const moreBackdrop = document.querySelector('[data-socialite-moment-more-backdrop]');
    const editForm = document.querySelector('[data-socialite-moment-edit-form]');
    const descriptionNode = document.querySelector('[data-socialite-moment-description]');
    const video = document.querySelector('[data-socialite-moment-video]');
    const progressInput = document.querySelector('[data-socialite-moment-progress]');
    const pauseState = document.querySelector('[data-socialite-moment-pause-state]');
    const muteButtons = Array.from(document.querySelectorAll('[data-socialite-moment-mute]'));
    const storageKey = 'hnt_moments_muted';
    const readMutePreference = () => {
      try {
        const stored = window.localStorage?.getItem(storageKey);
        if (stored === '0') return false;
        if (stored === '1') return true;
      } catch (error) {}
      return true;
    };
    const writeMutePreference = (muted) => {
      try {
        window.localStorage?.setItem(storageKey, muted ? '1' : '0');
      } catch (error) {}
    };
    let shouldStayMuted = readMutePreference();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const openDrawer = () => {
      if (!drawer) return;
      closeMoreSheet();
      drawer.classList.add('is-open');
      backdrop?.classList.add('is-open');
      drawer.setAttribute('aria-hidden', 'false');
    };

    const closeDrawer = () => {
      if (!drawer) return;
      drawer.classList.remove('is-open');
      backdrop?.classList.remove('is-open');
      drawer.setAttribute('aria-hidden', 'true');
    };

    function openMoreSheet() {
      if (!moreSheet) return;
      closeDrawer();
      moreSheet.classList.add('is-open');
      moreBackdrop?.classList.add('is-open');
      moreSheet.setAttribute('aria-hidden', 'false');
    }

    function closeMoreSheet() {
      if (!moreSheet) return;
      moreSheet.classList.remove('is-open');
      moreBackdrop?.classList.remove('is-open');
      moreSheet.setAttribute('aria-hidden', 'true');
    }

    const updateMuteButton = () => {
      if (!video || muteButtons.length === 0) return;
      const muted = video.muted || video.volume === 0;
      muteButtons.forEach((button) => {
        const mutedLabel = button.dataset.mutedLabel || 'Unmute';
        const unmutedLabel = button.dataset.unmutedLabel || 'Mute';
        button.setAttribute('aria-label', muted ? mutedLabel : unmutedLabel);
        button.setAttribute('title', muted ? mutedLabel : unmutedLabel);
        button.classList.toggle('is-muted', muted);
        button.classList.toggle('is-unmuted', !muted);
        const mutedIcon = button.querySelector('[data-socialite-moment-mute-on]');
        const unmutedIcon = button.querySelector('[data-socialite-moment-mute-off]');
        if (mutedIcon) {
          mutedIcon.hidden = !muted;
          mutedIcon.style.display = muted ? 'block' : 'none';
        }
        if (unmutedIcon) {
          unmutedIcon.hidden = muted;
          unmutedIcon.style.display = muted ? 'none' : 'block';
        }
      });
    };

    const applyMutePreferenceToVideo = () => {
      if (!video) return;
      video.muted = shouldStayMuted;
      if (shouldStayMuted) {
        video.setAttribute('muted', 'muted');
      } else {
        video.removeAttribute('muted');
        video.volume = 1;
      }
      updateMuteButton();
    };

    const setProgressValue = (percent) => {
      if (!progressInput) return;
      const safePercent = Number.isFinite(percent) ? Math.max(0, Math.min(100, percent)) : 0;
      progressInput.value = String(safePercent);
      progressInput.style.setProperty('--hnt-progress', `${safePercent}%`);
    };

    const syncProgress = () => {
      if (!video || !progressInput || !Number.isFinite(video.duration) || video.duration <= 0) {
        setProgressValue(0);
        return;
      }

      setProgressValue((video.currentTime / video.duration) * 100);
    };

    const updatePauseState = () => {
      if (!video || !pauseState) return;
      const showPaused = video.paused && !video.ended;
      pauseState.hidden = !showPaused;
      pauseState.classList.toggle('is-visible', showPaused);
    };

    const startVideo = () => {
      if (!video) return;
      shouldStayMuted = readMutePreference();
      applyMutePreferenceToVideo();
      video.setAttribute('playsinline', 'playsinline');
      video.setAttribute('webkit-playsinline', 'webkit-playsinline');
      const playPromise = video.play();
      if (playPromise && typeof playPromise.catch === 'function') {
        playPromise.catch(() => {
          video.muted = true;
          video.setAttribute('muted', 'muted');
          updateMuteButton();
          updatePauseState();
        });
      }
      updatePauseState();
    };

    startVideo();
    window.addEventListener('load', startVideo, { once: true });
    setTimeout(startVideo, 250);

    document.addEventListener('click', async (event) => {
      const muteToggle = event.target.closest('[data-socialite-moment-mute]');
      if (muteToggle) {
        event.preventDefault();
        event.stopPropagation();
        if (!video) return;
        shouldStayMuted = !(video.muted || video.volume === 0);
        writeMutePreference(shouldStayMuted);
        applyMutePreferenceToVideo();
        if (video.paused) startVideo();
        return;
      }

      const openButton = event.target.closest('[data-socialite-moment-comments-open]');
      if (openButton) {
        event.preventDefault();
        event.stopPropagation();
        openDrawer();
        return;
      }

      const closeButton = event.target.closest('[data-socialite-moment-comments-close]');
      if (closeButton) {
        event.preventDefault();
        closeDrawer();
        return;
      }

      const moreOpen = event.target.closest('[data-socialite-moment-more-open]');
      if (moreOpen) {
        event.preventDefault();
        event.stopPropagation();
        openMoreSheet();
        return;
      }

      const moreClose = event.target.closest('[data-socialite-moment-more-close]');
      if (moreClose) {
        event.preventDefault();
        closeMoreSheet();
        return;
      }

      const editToggle = event.target.closest('[data-socialite-moment-edit-toggle]');
      if (editToggle) {
        event.preventDefault();
        editForm?.classList.toggle('is-open');
        const textarea = editForm?.querySelector('textarea');
        if (editForm?.classList.contains('is-open')) {
          setTimeout(() => textarea?.focus(), 80);
        }
        return;
      }

      const backButton = event.target.closest('[data-socialite-moment-back]');
      if (backButton) {
        event.preventDefault();
        const targetUrl = backButton.getAttribute('href') || '/feed';
        window.location.assign(targetUrl);
        return;
      }

      const shareButton = event.target.closest('[data-socialite-moment-share]');
      if (shareButton) {
        event.preventDefault();
        const shareData = { title: document.title, url: window.location.href };
        try {
          if (navigator.share) {
            await navigator.share(shareData);
          } else if (navigator.clipboard) {
            await navigator.clipboard.writeText(window.location.href);
          }
        } catch (error) {}
      }
    });

    document.querySelectorAll('[data-socialite-moment-friend-form]').forEach((form) => {
      form.addEventListener('submit', async (event) => {
        if (!window.fetch) return;
        event.preventDefault();
        event.stopPropagation();

        const button = form.querySelector('button[type="submit"]');
        const connectedLabel = form.dataset.connectedLabel || 'Friends';
        const requestedLabel = form.dataset.requestedLabel || connectedLabel;
        if (button) button.disabled = true;

        try {
          const response = await fetch(form.action, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
              Accept: 'application/json',
            },
            body: new FormData(form),
          });

          if (!response.ok) throw new Error('Friend request failed');
          const payload = await response.json().catch(() => ({}));
          const label = payload.friendship_status === 'accepted' ? connectedLabel : requestedLabel;
          const badge = document.createElement('span');
          badge.className = 'hnt-moment-action-friend-badge is-connected';
          badge.setAttribute('aria-label', label);
          badge.setAttribute('title', label);
          badge.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" d="M5 12.5 9.3 17 19 7"/></svg>';
          form.replaceWith(badge);
        } catch (error) {
          form.submit();
        }
      });
    });

    editForm?.addEventListener('submit', async (event) => {
      if (!window.fetch) return;
      event.preventDefault();
      const button = editForm.querySelector('button[type="submit"]');
      const formData = new FormData(editForm);
      const description = String(formData.get('description') || '').trim();
      const originalText = button?.textContent || 'Save';

      if (button) {
        button.disabled = true;
        button.textContent = button?.dataset.savingLabel || 'Saving...';
      }

      try {
        const response = await fetch(editForm.action, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
          },
          body: formData,
        });

        if (!response.ok) throw new Error('Update failed');
        await response.json().catch(() => ({}));

        if (descriptionNode) {
          if (description.length > 0) {
            descriptionNode.hidden = false;
            descriptionNode.textContent = description.length > 150 ? `${description.slice(0, 147)}...` : description;
          } else {
            descriptionNode.hidden = true;
            descriptionNode.textContent = '';
          }
        }

        editForm.classList.remove('is-open');
        closeMoreSheet();
      } catch (error) {
        editForm.submit();
      } finally {
        if (button) {
          button.disabled = false;
          button.textContent = originalText;
        }
      }
    });

    video?.addEventListener('click', () => {
      if (video.paused) {
        const playPromise = video.play();
        if (playPromise && typeof playPromise.catch === 'function') {
          playPromise.catch(() => {});
        }
      } else {
        video.pause();
      }
    });

    progressInput?.addEventListener('input', () => {
      if (!video || !Number.isFinite(video.duration) || video.duration <= 0) return;
      const percent = Number(progressInput.value || 0);
      video.currentTime = (Math.max(0, Math.min(100, percent)) / 100) * video.duration;
      setProgressValue(percent);
    });

    ['loadedmetadata', 'durationchange', 'timeupdate', 'seeked'].forEach((eventName) => {
      video?.addEventListener(eventName, syncProgress);
    });

    ['play', 'playing', 'pause', 'ended'].forEach((eventName) => {
      video?.addEventListener(eventName, updatePauseState);
    });

    video?.addEventListener('volumechange', () => {
      updateMuteButton();
    });

    syncProgress();
    updatePauseState();

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeDrawer();
        closeMoreSheet();
      }
    });

    document.addEventListener('visibilitychange', () => {
      if (!video) return;
      if (document.hidden) {
        if (!video.paused) video.pause();
      } else {
        startVideo();
      }
    });
  });
})();
