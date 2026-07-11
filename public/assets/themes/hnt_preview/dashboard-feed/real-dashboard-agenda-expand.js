/* Animated expansion for the real HNT agenda card. */
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
    message: 88,
    lfg: 118,
    cup: 104,
    contract: 108,
    challenge: 112,
    moment: 108,
    default: 104,
  };
  const expandedMobileRows = {
    message: 82,
    lfg: 108,
    cup: 98,
    contract: 102,
    challenge: 106,
    moment: 102,
    default: 98,
  };

  let expanded = false;
  let animating = false;
  let originParent = null;
  let originNextSibling = null;
  let originRect = null;
  let originalStyle = null;
  let originalRole = null;
  let originalAriaModal = null;
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
        background: rgba(35, 34, 29, .42);
        backdrop-filter: blur(12px) saturate(.82);
        -webkit-backdrop-filter: blur(12px) saturate(.82);
        opacity: 0;
        transition: opacity .38s ease;
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
        transform-origin: top left;
        will-change: top, left, width, height, border-radius, box-shadow;
        transition:
          top .46s cubic-bezier(.2,.8,.2,1),
          left .46s cubic-bezier(.2,.8,.2,1),
          width .46s cubic-bezier(.2,.8,.2,1),
          height .46s cubic-bezier(.2,.8,.2,1),
          padding .46s cubic-bezier(.2,.8,.2,1),
          border-radius .46s cubic-bezier(.2,.8,.2,1),
          box-shadow .46s cubic-bezier(.2,.8,.2,1),
          background .3s ease;
      }

      .hnt-agenda-panel.hnt-agenda-floating.is-expanded {
        overflow-x: hidden !important;
        overflow-y: auto !important;
        padding: clamp(26px, 3.2vw, 44px) !important;
        border-radius: 38px !important;
        background: rgba(255, 253, 247, .985) !important;
        box-shadow:
          0 34px 100px rgba(31, 30, 25, .27),
          0 0 0 1px rgba(255, 255, 255, .72) inset !important;
        scrollbar-width: thin;
      }

      .hnt-agenda-panel.hnt-agenda-floating .section-head .circle-button svg {
        transition: transform .42s cubic-bezier(.2,.8,.2,1);
      }

      .hnt-agenda-panel.hnt-agenda-floating.is-expanded .section-head .circle-button svg {
        transform: rotate(180deg) scale(1.06);
      }

      .hnt-agenda-panel.is-expanded .hnt-section-kicker {
        margin-bottom: 8px !important;
        font-size: 10px !important;
      }

      .hnt-agenda-panel.is-expanded .section-head h2 {
        font-size: clamp(31px, 3.2vw, 48px) !important;
        line-height: 1 !important;
        letter-spacing: -1.4px !important;
      }

      .hnt-agenda-panel.is-expanded .section-head .circle-button {
        width: 58px !important;
        height: 58px !important;
        flex: 0 0 58px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-row {
        gap: 10px !important;
        margin-top: 26px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-row button {
        min-height: 64px !important;
        border-radius: 22px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-row b {
        font-size: 13px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-row small {
        font-size: 10px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-timeline {
        height: auto !important;
        min-height: 0 !important;
        grid-template-columns: 82px minmax(0, 1fr) !important;
        gap: 18px !important;
        margin-top: 26px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-axis,
      .hnt-agenda-panel.is-expanded .hnt-agenda-items {
        gap: 12px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-axis span {
        width: 70px !important;
        height: 38px !important;
        border-radius: 20px !important;
        font-size: 10px !important;
      }

      .hnt-agenda-panel.is-expanded .time-dot {
        width: 22px !important;
        height: 22px !important;
        font-size: 12px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-card {
        gap: 18px !important;
        padding: 18px 22px !important;
        border-radius: 24px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-copy {
        gap: 4px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-copy > span {
        font-size: 9px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-copy h3 {
        font-size: 17px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-copy p {
        font-size: 12px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-card > button,
      .hnt-agenda-panel.is-expanded .hnt-agenda-card-footer > button {
        min-width: 110px !important;
        height: 46px !important;
        padding-inline: 18px !important;
        border-radius: 24px !important;
        font-size: 12px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-card.highlight > button {
        width: 50px !important;
        min-width: 50px !important;
        padding: 0 !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-mini-avatars img {
        width: 34px !important;
        height: 34px !important;
      }

      .hnt-agenda-panel.is-expanded .hnt-agenda-progress {
        right: 22px !important;
        bottom: 14px !important;
        left: 22px !important;
        height: 8px !important;
      }

      @media (max-width: 720px) {
        .hnt-agenda-panel.hnt-agenda-floating.is-expanded {
          padding: 22px 18px 26px !important;
          border-radius: 28px !important;
        }

        .hnt-agenda-panel.is-expanded .section-head h2 {
          font-size: clamp(28px, 8vw, 38px) !important;
        }

        .hnt-agenda-panel.is-expanded .section-head .circle-button {
          width: 50px !important;
          height: 50px !important;
          flex-basis: 50px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-row {
          gap: 6px !important;
          margin-top: 22px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-row button {
          min-height: 58px !important;
          border-radius: 19px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-timeline {
          grid-template-columns: 64px minmax(0, 1fr) !important;
          gap: 10px !important;
          margin-top: 22px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-axis,
        .hnt-agenda-panel.is-expanded .hnt-agenda-items {
          gap: 9px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-axis span {
          width: 58px !important;
          height: 34px !important;
          font-size: 9px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-card {
          gap: 10px !important;
          padding: 15px 16px !important;
          border-radius: 21px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-copy > span {
          font-size: 8px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-copy h3 {
          font-size: 15px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-copy p {
          font-size: 10px !important;
        }

        .hnt-agenda-panel.is-expanded .hnt-agenda-card > button,
        .hnt-agenda-panel.is-expanded .hnt-agenda-card-footer > button {
          min-width: 82px !important;
          height: 42px !important;
          padding-inline: 14px !important;
          font-size: 10px !important;
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
    const margin = narrow ? 8 : 24;
    const maxWidth = narrow ? window.innerWidth - (margin * 2) : Math.min(1120, window.innerWidth - (margin * 2));
    const maxHeight = window.innerHeight - (margin * 2);

    return {
      top: margin,
      left: Math.max(margin, (window.innerWidth - maxWidth) / 2),
      width: maxWidth,
      height: maxHeight,
    };
  };

  const setRect = (rect) => {
    panel.style.top = `${Math.round(rect.top)}px`;
    panel.style.left = `${Math.round(rect.left)}px`;
    panel.style.width = `${Math.round(rect.width)}px`;
    panel.style.height = `${Math.round(rect.height)}px`;
  };

  const restoreAttribute = (name, value) => {
    if (value === null) panel.removeAttribute(name);
    else panel.setAttribute(name, value);
  };

  const finishClose = () => {
    window.clearTimeout(finishTimer);

    panel.classList.remove('hnt-agenda-floating', 'is-expanded');
    restoreAttribute('role', originalRole);
    restoreAttribute('aria-modal', originalAriaModal);

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

    const activeTab = panel.querySelector('.hnt-agenda-row button.active');
    activeTab?.click();
    trigger.focus({ preventScroll: true });
  };

  const close = () => {
    if (!expanded || animating) return;
    animating = true;

    panel.classList.remove('is-expanded');
    syncRows(false);
    setRect(originRect);
    backdrop?.classList.remove('is-visible');

    finishTimer = window.setTimeout(finishClose, 520);
  };

  const open = () => {
    if (expanded || animating) return;
    animating = true;
    expanded = true;

    originParent = panel.parentNode;
    originNextSibling = panel.nextSibling;
    originRect = panel.getBoundingClientRect();
    originalStyle = panel.getAttribute('style');
    originalRole = panel.getAttribute('role');
    originalAriaModal = panel.getAttribute('aria-modal');

    backdrop = document.createElement('div');
    backdrop.className = 'hnt-agenda-expand-backdrop';
    backdrop.setAttribute('aria-hidden', 'true');
    backdrop.addEventListener('click', close);

    document.body.appendChild(backdrop);
    document.body.appendChild(panel);
    document.body.classList.add('hnt-agenda-expanded');

    panel.classList.add('hnt-agenda-floating');
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-label', 'Heute und als Nächstes');
    trigger.setAttribute('aria-expanded', 'true');
    trigger.setAttribute('aria-label', 'Große Aufgabenansicht schließen');

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
      }, 500);
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
  trigger.setAttribute('aria-label', 'Große Aufgabenansicht öffnen');
  trigger.addEventListener('click', toggle, true);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && expanded) {
      event.preventDefault();
      close();
    }
  });

  const observer = new MutationObserver(() => {
    if (expanded) window.requestAnimationFrame(() => syncRows(true));
  });
  observer.observe(itemsHost, { childList: true });

  window.addEventListener('resize', () => {
    if (!expanded || animating) return;
    setRect(targetRect());
    syncRows(true);
  }, { passive: true });
})();