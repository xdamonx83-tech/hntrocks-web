(() => {
  const profileTabs = [...document.querySelectorAll("[data-profile-tab]")];
  const profilePanels = [...document.querySelectorAll("[data-profile-panel]")];
  const profileTabTitle = document.getElementById("profileTabTitle");
  const profileMainScroller = document.querySelector(".profile-page-main");

  function activateProfileTab(tabName, shouldFocus = false) {
    profileTabs.forEach((button) => {
      const active = button.dataset.profileTab === tabName;
      button.classList.toggle("active", active);
      button.setAttribute("aria-selected", String(active));
      if (active && shouldFocus) button.focus();
    });

    profilePanels.forEach((panel) => {
      const active = panel.dataset.profilePanel === tabName;
      panel.classList.toggle("active", active);
      panel.hidden = !active;
    });

    const activeButton = profileTabs.find((button) => button.dataset.profileTab === tabName);
    if (profileTabTitle && activeButton) {
      profileTabTitle.textContent = activeButton.dataset.title || activeButton.textContent.trim();
    }

    if (profileMainScroller && window.matchMedia("(min-width: 900px)").matches) {
      const feedTop = document.querySelector(".profile-post-feed")?.offsetTop || 0;
      profileMainScroller.scrollTo({ top: Math.max(0, feedTop - 8), behavior: "smooth" });
    }
  }

  profileTabs.forEach((button) => {
    button.addEventListener("click", () => activateProfileTab(button.dataset.profileTab));
    button.addEventListener("keydown", (event) => {
      if (!["ArrowLeft", "ArrowRight"].includes(event.key)) return;
      event.preventDefault();
      const currentIndex = profileTabs.indexOf(button);
      const direction = event.key === "ArrowRight" ? 1 : -1;
      const nextIndex = (currentIndex + direction + profileTabs.length) % profileTabs.length;
      activateProfileTab(profileTabs[nextIndex].dataset.profileTab, true);
    });
  });

  activateProfileTab("posts");
})();
