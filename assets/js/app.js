(function () {
  'use strict';

  var body = document.body;
  var toggle = document.querySelector('.menu-toggle');
  var overlay = document.querySelector('.sidebar-overlay');

  /* Menu sidebar di layar kecil */
  function setNav(open) {
    body.classList.toggle('nav-open', open);
    if (toggle) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    }
  }
  if (toggle) {
    toggle.addEventListener('click', function () {
      setNav(!body.classList.contains('nav-open'));
    });
  }
  if (overlay) {
    overlay.addEventListener('click', function () { setNav(false); });
  }
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') { setNav(false); }
  });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 900) { setNav(false); }
  });

  /* Tampilkan/sembunyikan password */
  var passwordInput = document.getElementById('password');
  var passwordToggle = document.querySelector('.password-toggle');
  if (passwordInput && passwordToggle) {
    var eyeIcon = passwordToggle.querySelector('svg');
    var EYE_OPEN = '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>';
    var EYE_CLOSED = '<path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a15.8 15.8 0 0 1-3.1 3.9"/><path d="M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 4-.8"/>';
    passwordToggle.addEventListener('click', function () {
      var shown = passwordInput.type === 'password';
      passwordInput.type = shown ? 'text' : 'password';
      var label = shown ? 'Sembunyikan password' : 'Lihat password';
      passwordToggle.setAttribute('aria-label', label);
      passwordToggle.setAttribute('title', label);
      if (eyeIcon) { eyeIcon.innerHTML = shown ? EYE_CLOSED : EYE_OPEN; }
    });
  }

  /* Pesan sukses hilang otomatis */
  document.querySelectorAll('.alert.alert-success').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 450);
    }, 4000);
  });

  /* Cegah klik ganda saat form dikirim */
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (ev.defaultPrevented) { return; } // mis. konfirmasi hapus dibatalkan
      form.querySelectorAll('button[type=submit], button:not([type])').forEach(function (btn) {
        setTimeout(function () { btn.disabled = true; }, 0);
      });
    });
  });
})();
