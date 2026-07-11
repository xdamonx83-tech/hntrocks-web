/* Anchored expansion for the real HNT agenda card. */
(() => {
  const panel = document.querySelector('.hnt-agenda-panel');
  const trigger = panel?.querySelector('.section-head .circle-button');
  const itemsHost = panel?.querySelector('.hnt-agenda-items');
  const axisHost = panel?.querySelector('.hnt-agenda-axis');

  if (!panel || !trigger || !itemsHost || !axisHost) return;
  if (panel.dataset.agendaExpandReady === '1') return;
  panel.dataset.agendaExpandReady = '1';

  const compactDesktopRows = {
    message: 54,
    lfg: 79,
    cup: 69,
    contract: 71,
    challenge: 73,
    moment: 70,
    default: 69,
  };
  const compactMobileRows = {
    message: 76,
    lfg: 104,
    cup: 90,
    contract: 92,
    challenge: 96,
    moment: 92,
    default: 90,
  };
  const expandedDesktopRows = {
    message: 64,
    lfg: 92,
    cup: 82,
    contract: 84,
    challenge: 86,
    moment: 84,
    default: 82,
  };
  const expandedMobileRows = {
    message: 80,
    lfg: 108,
    cup: 96,
    contract: 98,
    challenge: 102,
    moment: 98,
    default: 96,
  };

  let expanded = false;
  let animating = false;
  let originParent = null;
  let originNextSibling = null;
  let originRect = null;
  let originalStyle = null;
  let originalTriggerLabel = trigger.getAttribute('aria-label');
  let backdrop = null;
  let finishTimer = 0;

  const addStyle = () => {
    if (document.getElementById('real-dashboard-agenda-expand-style')) return;

    const style = document.createElement('style');
    style.id = 'real-dashboard-agenda-expand-style';
    style.textContent = `
      body.hnt-agenda-expanded {
        overflow: hidden !important;
      }

      .hnt-agenda-expand-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1190;
        background: rgba(35, 34, 29, .10);
        opacity: 0;
        transition: opacity .34s ease;
      }

      .hnt-agenda-expand-backdrop.is-visible {
        opacity: 1;
      }

      .hnt-agenda-panel.hnt-agenda-floating {
        position: fixed !important;
        right: auto !important;
        bottom: auto !important;
        z-index: 1200 !important;
        box-sizing: border-box !important;
        max-width: none !important;
        max-height: none !important;
        margin: 0 !important;
        transform: translateZ(0);
        transform-origin: left bottom;
        will-change: top, left, width, height, border-radius, box-shadow;
        transition:
          top .44s cubic-bezier(.2,.8,.2,1),
          left .44s cubic-bezier(.2,.8,.2,1),
          width .44s cubic-bezier(.2,.8,.2,1),
          height .44s cubic-bezier(.2,.8,.2,1),
          padding .44s cubic-bezier(.2,.8,.2,1),
          border-radius .44s cubic-bezier(.2,.8,.2,1),
          box-shadow .44s cubic-bezier(.2,.8,.2,1),
          background .28s ease;
      }

      .hnt-agenda-panel.hnt-agenda-floating.is-expanded {
        overflow-x: hidden !important;
        overflow-y: auto !important;
        padding: 22px 22px 20px !important;
        border-radius: 31px !important;
        background: rgba(255, 253, 247, .985) !important;
        box-shadow:
          0 24px 72px rgba(31, 30, 25, .20),
          0 0 0 1px rgba(255, 255, 255, .72) inset !important;
        scrollbar-width: thin;
      }

      .hnt-agenda-panel.hnt-agenda-floating .section-head .circle-button svg {
        transition: transform .38s cubic-bezier(.2,.8,.2,1);
      }

      .hnt-agenda-panel.hnt-agenda-floating.is-expanded .section-head .circle-button svg {
        transform: rotate(180deg) scale(1.03);
      }

      .hnt-agenda-panel.is-expanded .hnt-section-kicker {
        margin-bottom: 5px !important;
        font-size: 8px !important;
      }

      .hnt-agenda-panel.is-expanded .section-head h2 {
        font-size: clamp(22px, 2vw, 31px) !important;
        line-height: 1 !important;
        letter-spacing: -.8px !important;
      }

      .hnt-agenda-panel.is-expanded .section-head .circle-button {
        width: 44px !important;
        height: 44px !important;
        flex: 0 0 44px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-row {
        gap: 7px !important;
        margin-top: 18px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-row button {
        min-height: 48px !important;
        border-radius: 17px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-row b {
        font-size: 10px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-row small {
        font-size: 8px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-timeline {
        height: auto !important;
        min-height: 0 !important;
        grid-template-columns: 58px minmax(0, 1fr) !important;
        gap: 11px !important;
        margin-top: 18px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-axis,
      .hnt-agenda-panel.is-expanded .hnt-agenda-items {
        gap: 8px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-axis span {
        width: 52px !important;
        height: 29px !important;
        border-radius: 15px !important;
        font-size: 8px !important;
      }

      .hnt-agenda-panel.is-expanded .time-dot {
        width: 18px !important;
        height: 18px !important;
        font-size: 10px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-card {
        gap: 10px !important;
        padding: 12px 14px !important;
        border-radius: 18px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-copy {
        gap: 3px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-copy > span {
        font-size: 7px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-copy h3 {
        font-size: 12px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-copy p {
        font-size: 9px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-card > button,
      .hnt-agenda-panel.is-expanded .hnt-agenda-card-footer > button {
        min-width: 76px !important;
        height: 34px !important;
        padding-inline: 12px !important;
        border-radius: 18px !important;
        font-size: 9px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-card.highlight > button {
        width: 38px !important;
        min-width: 38px !important;
        padding: 0 !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-mini-avatars img {
        width: 27px !important;
        height: 27px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-progress {
        right: 14px !important;
        bottom: 10px !important;
        left: 14px !important;
        height: 6px !important;
      }

      @media (max-width: 720px) {
        .hnt-agenda-panel.hnt-agenda-floating.is-expanded {
          padding: 20px 17px 20px !important;
          border-radius: 27px !important;
        }

        .hnt-agenda-panel.is-expanded .section-head h2 {
          font-size: clamp(24px, 7vw, 31px) !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-timeline {
          grid-template-columns: 56px minmax(0, 1fr) !important;
          gap: 9px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-mini-avatars {
          display: none !important;
        }
      }

      @media (prefers-reduced-motion: reduce) {
        .hnt-agenda-expand-backdrop,
        .hnt-agenda-panel.hnt-agenda-floating,
        .hnt-agenda-panel.hnt-agenda-floating .section-head .circle-button svg {
          transition-duration: .01ms !important;
        }
      }
    `;
    document.head.appendChild(style);
  };

  const cards = () => [...itemsHost.querySelectorAll(':scope > .hnt-agenda-card')];

  const rowMap = (large) => {
    if (large) {
      return window.matchMedia('(max-width: 720px)').matches ? expandedMobileRows : expandedDesktopRows;
    }

    return window.matchMedia('(max-width: 899px)').matches ? compactMobileRows : compactDesktopRows;
  };

  const syncRows = (large = expanded) => {
    const currentCards = cards();
    if (!currentCards.length) return;

    const rows = rowMap(large);
    const template = currentCards
      .map((card) => `${rows[card.dataset.agendaType] || rows.default}px`)
      .join(' ');

    itemsHost.style.gridTemplateRows = template;
    axisHost.style.gridTemplateRows = template;
  };

  const targetRect = () => {
    const narrow = window.matchMedia('(max-width: 720px)').matches;
    const margin = narrow ? 8 : 16;
    const left = Math.max(margin, originRect.left);
    const bottomGap = Math.max(margin, window.innerHeight - originRect.bottom);
    const availableWidth = Math.max(originRect.width, window.innerWidth - left - margin);
    const availableHeight = Math.max(originRect.height, window.innerHeight - bottomGap - margin);

    const preferredWidth = narrow
      ? window.innerWidth - left - margin
      : Math.max(originRect.width + 260, originRect.width * 1.72);
    const width = Math.min(preferredWidth, 840, availableWidth);

    const contentHeight = panel.scrollHeight + 12;
    const preferredHeight = Math.max(
      originRect.height,
      Math.min(contentHeight, originRect.height + 180),
    );
    const height = Math.min(preferredHeight, availableHeight);
    const bottom = window.innerHeight - bottomGap;

    return {
      top: Math.max(margin, bottom - height),
      left,
      width,
      height,
    };
  };

  const setRect = (rect) => {
    panel.style.top = `${Math.round(rect.top)}px`;
    panel.style.left = `${Math.round(rect.left)}px`;
    panel.style.width = `${Math.round(rect.width)}px`;
    panel.style.height = `${Math.round(rect.height)}px`;
  };

  const finishClose = () => {
    window.clearTimeout(finishTimer);

    panel.classList.remove('hnt-agenda-floating', 'is-expanded');

    if (originalStyle === null) panel.removeAttribute('style');
    else panel.setAttribute('style', originalStyle);

    const parent = originParent?.isConnected ? originParent : document.body;
    if (originNextSibling?.parentNode === parent) parent.insertBefore(panel, originNextSibling);
    else parent.appendChild(panel);

    backdrop?.remove();
    backdrop = null;
    document.body.classList.remove('hnt-agenda-expanded');

    expanded = false;
    animating = false;
    trigger.setAttribute('aria-expanded', 'false');
    if (originalTriggerLabel === null) trigger.removeAttribute('aria-label');
    else trigger.setAttribute('aria-label', originalTriggerLabel);

    syncRows(false);
    trigger.focus({ preventScroll: true });
  };

  const close = () => {
    if (!expanded || animating) return;
    animating = true;

    panel.classList.remove('is-expanded');
    syncRows(false);
    setRect(originRect);
    backdrop?.classList.remove('is-visible');

    finishTimer = window.setTimeout(finishClose, 500);
  };

  const open = () => {
    if (expanded || animating) return;
    animating = true;
    expanded = true;

    originParent = panel.parentNode;
    originNextSibling = panel.nextSibling;
    originRect = panel.getBoundingClientRect();
    originalStyle = panel.getAttribute('style');

    backdrop = document.createElement('div');
    backdrop.className = 'hnt-agenda-expand-backdrop';
    backdrop.setAttribute('aria-hidden', 'true');
    backdrop.addEventListener('click', close);

    document.body.appendChild(backdrop);
    document.body.appendChild(panel);
    document.body.classList.add('hnt-agenda-expanded');

    panel.classList.add('hnt-agenda-floating');
    trigger.setAttribute('aria-expanded', 'true');
    trigger.setAttribute('aria-label', 'Aufgabenansicht verkleinern');

    panel.style.position = 'fixed';
    panel.style.right = 'auto';
    panel.style.bottom = 'auto';
    setRect(originRect);

    void panel.offsetWidth;

    window.requestAnimationFrame(() => {
      panel.classList.add('is-expanded');
      syncRows(true);
      setRect(targetRect());
      backdrop?.classList.add('is-visible');
      finishTimer = window.setTimeout(() => {
        animating = false;
      }, 480);
    });
  };

  const toggle = (event) => {
    event.preventDefault();
    event.stopImmediatePropagation();
    if (expanded) close();
    else open();
  };

  addStyle();
  trigger.setAttribute('aria-expanded', 'false');
  trigger.setAttribute('aria-label', 'Aufgabenansicht vergrößern');
  trigger.addEventListener('click', toggle, true);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && expanded) {
      event.preventDefault();
      close();
    }
  });

  const observer = new MutationObserver(() => {
    if (!expanded) return;
    window.requestAnimationFrame(() => {
      syncRows(true);
      setRect(targetRect());
    });
  });
  observer.observe(itemsHost, { childList: true });

  window.addEventListener('resize', () => {
    if (!expanded || animating) return;
    setRect(targetRect());
    syncRows(true);
  }, { passive: true });
})();
