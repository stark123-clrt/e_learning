(function () {
  var toggle = document.querySelector('[data-menu-toggle]');
  var sidebar = document.querySelector('[data-sidebar]');
  var backdrop = document.querySelector('[data-sidebar-backdrop]');

  if (!toggle || !sidebar || !backdrop) return;

  function openMenu() {
    sidebar.classList.add('is-open');
    backdrop.style.display = 'block';
    toggle.setAttribute('aria-expanded', 'true');
  }

  function closeMenu() {
    sidebar.classList.remove('is-open');
    backdrop.style.display = 'none';
    toggle.setAttribute('aria-expanded', 'false');
  }

  toggle.addEventListener('click', function () {
    if (sidebar.classList.contains('is-open')) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  backdrop.addEventListener('click', closeMenu);
})();
