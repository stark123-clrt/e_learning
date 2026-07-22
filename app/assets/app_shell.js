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

// Afficher / masquer les champs mot de passe
(function () {
  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-toggle-password]');
    if (!btn) return;
    var input = document.getElementById(btn.getAttribute('data-toggle-password'));
    if (!input) return;
    var showing = input.getAttribute('type') === 'text';
    input.setAttribute('type', showing ? 'password' : 'text');
    btn.setAttribute('aria-label', showing ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
    btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
  });
})();
