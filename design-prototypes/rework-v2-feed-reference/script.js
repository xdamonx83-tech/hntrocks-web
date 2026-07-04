(function () {
  var userChip = document.querySelector(".user-chip");

  if (!userChip) {
    return;
  }

  userChip.addEventListener("click", function () {
    userChip.classList.toggle("is-open");
  });
})();
