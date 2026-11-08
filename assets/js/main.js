/* main.js - shared helpers: API calls, toasts, live section refresh, small UI behaviours */
(function () {
  'use strict';
  var meta = function (n) { var m = document.querySelector('meta[name="' + n + '"]'); return m ? m.content : ''; };
  var BS = window.BS = window.BS || {};
  BS.base = meta('base-url');
  BS.csrf = meta('csrf-token');
  BS.url = function (p) { return BS.base + '/' + String(p).replace(/^\//, ''); };

  /** fetch() wrapper: JSON in/out, CSRF header, readable errors. */
  BS.api = async function (path, opts) {
    opts = opts || {};
    var method = opts.method || 'GET';
    var u = BS.url('api/' + path);
    if (opts.params) u += (u.indexOf('?') >= 0 ? '&' : '?') + new URLSearchParams(opts.params).toString();
    var init = { method: method, credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (method !== 'GET') {
      init.headers['Content-Type'] = 'application/json';
      init.headers['X-CSRF-Token'] = BS.csrf;
      init.body = JSON.stringify(opts.body || {});
    }
    var res;
    try { res = await fetch(u, init); }
    catch (e) { throw { message: 'Network error. Please check your connection.', status: 0 }; }
    var data = null;
    try { data = await res.json(); } catch (e) { /* not JSON */ }
    if (!res.ok || !data || data.ok === false) {
      throw { message: (data && data.message) || ('Request failed (' + res.status + ')'), status: res.status, data: data };
    }
    return data;
  };

  BS.toast = function (msg, type) {
    var box = document.getElementById('toasts');
    if (!box) return;
    var t = document.createElement('div');
    t.className = 'toast toast-' + (type || 'info');
    t.textContent = msg;               // textContent: never interpreted as HTML
    box.appendChild(t);
    setTimeout(function () { t.classList.add('toast-hide'); setTimeout(function () { t.remove(); }, 400); }, 4500);
  };

  BS.busy = function (btn, on) {
    if (!btn) return;
    btn.classList.toggle('is-loading', !!on);
    btn.disabled = !!on;
  };

  BS.setCartCount = function (n) {
    document.querySelectorAll('.js-cart-count').forEach(function (el) { el.textContent = n; el.hidden = !(n > 0); });
  };

  /**
   * Re-fetch the current page and swap the content of every [data-live] section
   * whose list of names includes one of `names` (e.g. "books", "orders", "notifs", "cart").
   */
  BS.liveRefresh = async function (names) {
    var regions = Array.prototype.filter.call(document.querySelectorAll('[data-live]'), function (el) {
      var list = el.dataset.live.split(' ');
      return names.some(function (n) { return list.indexOf(n) >= 0; });
    });
    if (!regions.length) return;
    try {
      var res = await fetch(location.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } });
      if (!res.ok) return;
      var doc = new DOMParser().parseFromString(await res.text(), 'text/html');
      var updated = false;
      regions.forEach(function (el) {
        var a = document.activeElement;
        if (a && el.contains(a) && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName)) return;   // don't disturb typing
        var fresh = doc.querySelector('[data-live-id="' + el.dataset.liveId + '"]');
        if (fresh && fresh.innerHTML !== el.innerHTML) { el.innerHTML = fresh.innerHTML; updated = true; }
      });
      if (updated) document.dispatchEvent(new CustomEvent('bs:content-updated'));
    } catch (e) { /* ignore - next poll will retry */ }
  };

  document.addEventListener('click', async function (e) {
    var t = e.target;
    // mobile menus
    if (t.closest('.nav-toggle')) {
      var hdr = document.querySelector('.site-header');
      var btn = hdr && hdr.querySelector('.nav-toggle');
      if (hdr) hdr.classList.toggle('nav-open');
      if (btn) btn.setAttribute('aria-expanded', hdr && hdr.classList.contains('nav-open') ? 'true' : 'false');
      return;
    }
    if (t.closest('.sidebar-toggle')) { document.body.classList.toggle('sidebar-open'); return; }
    // close mobile nav on backdrop tap
    if (document.querySelector('.site-header.nav-open') && !t.closest('.main-nav') && !t.closest('.nav-toggle')) {
      var hdr2 = document.querySelector('.site-header');
      var btn2 = hdr2 && hdr2.querySelector('.nav-toggle');
      if (hdr2) hdr2.classList.remove('nav-open');
      if (btn2) btn2.setAttribute('aria-expanded', 'false');
    }
    // notifications: mark read
    var mr = t.closest('.js-mark-read, .js-mark-all');
    if (mr) {
      BS.busy(mr, true);
      try {
        await BS.api('notifications.php', { method: 'POST', body: { action: mr.classList.contains('js-mark-all') ? 'mark_all' : 'mark_read', id: parseInt(mr.dataset.id || '0', 10) } });
        await BS.liveRefresh(['notifs']);
        if (BS.pollNow) BS.pollNow();
      } catch (err) { BS.toast(err.message, 'error'); }
      BS.busy(mr, false);
    }
  });

  // <form data-confirm="..."> asks before submitting
  document.addEventListener('submit', function (e) {
    var msg = e.target.dataset ? e.target.dataset.confirm : '';
    if (msg && !window.confirm(msg)) e.preventDefault();
  });
})();
