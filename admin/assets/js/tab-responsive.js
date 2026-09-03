/*!
 * Tab Responsive Dropdown
 * Converts .nav.nav-pills.tab-responsive to a centered dropdown on mobile (<768px).
 * Desktop layout is unchanged.
 */
(function () {
  'use strict';

  function initTabDropdowns() {
    var pills = document.querySelectorAll('.nav.nav-pills.tab-responsive');
    pills.forEach(function (ul) {
      if (ul.dataset.tabDd) return;
      ul.dataset.tabDd = '1';

      var links = ul.querySelectorAll('a.nav-link');
      if (!links.length) return;

      var activeLink = ul.querySelector('a.nav-link.active') || links[0];

      /* ---- wrapper ---- */
      var wrap = document.createElement('div');
      wrap.className = 'tab-dd-wrap';

      /* ---- toggle button ---- */
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'tab-dd-btn';
      btn.setAttribute('aria-haspopup', 'true');
      btn.setAttribute('aria-expanded', 'false');
      btn.innerHTML =
        '<span class="tab-dd-label">' + activeLink.innerHTML + '</span>' +
        '<span class="tab-dd-caret"><i class="fas fa-chevron-down"></i></span>';

      /* ---- dropdown menu ---- */
      var menu = document.createElement('div');
      menu.className = 'tab-dd-menu';
      menu.setAttribute('role', 'menu');

      links.forEach(function (link) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'tab-dd-item' + (link.classList.contains('active') ? ' active' : '');
        item.setAttribute('role', 'menuitem');
        item.innerHTML = link.innerHTML;

        item.addEventListener('click', function () {
          /* update button label */
          btn.querySelector('.tab-dd-label').innerHTML = item.innerHTML;
          /* update active class in menu */
          menu.querySelectorAll('.tab-dd-item').forEach(function (i) { i.classList.remove('active'); });
          item.classList.add('active');
          /* close dropdown */
          closeWrap(wrap, btn);
          /* trigger original navigation / onclick / bootstrap-tab */
          link.click();
        });

        menu.appendChild(item);
      });

      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = wrap.classList.contains('open');
        /* close any other open dropdowns first */
        document.querySelectorAll('.tab-dd-wrap.open').forEach(function (w) {
          closeWrap(w, w.querySelector('.tab-dd-btn'));
        });
        if (!isOpen) {
          wrap.classList.add('open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });

      wrap.appendChild(btn);
      wrap.appendChild(menu);
      ul.parentNode.insertBefore(wrap, ul);
    });
  }

  function closeWrap(wrap, btn) {
    wrap.classList.remove('open');
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  /* Close on outside click */
  document.addEventListener('click', function () {
    document.querySelectorAll('.tab-dd-wrap.open').forEach(function (w) {
      closeWrap(w, w.querySelector('.tab-dd-btn'));
    });
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTabDropdowns);
  } else {
    initTabDropdowns();
  }
})();
