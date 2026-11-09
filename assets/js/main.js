/* main.js - shared helpers: API calls, toasts, live section refresh, small UI behaviours */
(function () {
  'use strict';
  var meta = function (n) { var m = document.querySelector('meta[name="' + n + '"]'); return m ? m.content : ''; };
  var BS = window.BS = window.BS || {};
  BS.base = meta('base-url');
  BS.csrf = meta('csrf-token');
  BS.url = function (p) { return BS.base + '/' + String(p).replace(/^\//, ''); };

  var googleCoverQueue = [];
  var googleCoverActive = 0;
  var googleBooksPaused = false;
  var googleCoverCachePrefix = 'bookstore-google-cover-v1:';
  var googleCoverConcurrency = 3;
  var googleBooksApiKey = meta('google-books-api-key');

  function applyGoogleCover(placeholder, src) {
    var title = placeholder.dataset.title || 'Book cover';
    var img = document.createElement('img');
    img.className = placeholder.classList.contains('book-cover') ? 'book-cover' : '';
    img.alt = title;
    img.loading = 'lazy';
    img.decoding = 'async';
    img.src = src;
    img.addEventListener('error', function () {
      var initial = document.createElement('span');
      initial.textContent = title.charAt(0).toLocaleUpperCase();
      placeholder.classList.remove('google-cover-loaded');
      placeholder.classList.add('cover-placeholder');
      placeholder.replaceChildren(initial);
    }, { once: true });
    placeholder.classList.remove('cover-placeholder');
    placeholder.classList.add('google-cover-loaded');
    placeholder.replaceChildren(img);
  }

  async function lookupGoogleCover(placeholder) {
    var title = (placeholder.dataset.title || '').trim();
    var author = (placeholder.dataset.author || '').trim();
    var isbn = (placeholder.dataset.isbn || '').replace(/[^0-9Xx]/g, '');
    if (!title) return;

    var cacheKey = googleCoverCachePrefix + (googleBooksApiKey ? 'keyed:' : 'anonymous:') + (isbn || (title + '|' + author).toLocaleLowerCase());
    try {
      var cached = localStorage.getItem(cacheKey);
      if (cached) {
        cached = JSON.parse(cached);
        if (cached.expires > Date.now()) {
          if (cached.src) applyGoogleCover(placeholder, cached.src);
          return;
        }
        localStorage.removeItem(cacheKey);
      }
    } catch (error) {
      console.warn('Could not read the Google Books cover cache.', error);
    }

    var params = {
      q: isbn ? 'isbn:' + isbn : 'intitle:' + title + (author ? ' inauthor:' + author : ''),
      maxResults: '1',
      fields: 'items(volumeInfo(imageLinks))'
    };
    if (googleBooksApiKey) params.key = googleBooksApiKey;
    var query = new URLSearchParams(params);
    var response = await fetch('https://www.googleapis.com/books/v1/volumes?' + query.toString(), {
      headers: { Accept: 'application/json' },
      credentials: 'omit'
    });
    if (!response.ok) {
      var requestError = new Error('Google Books returned HTTP ' + response.status);
      requestError.status = response.status;
      throw requestError;
    }
    var data = await response.json();
    var image = data.items && data.items[0] && data.items[0].volumeInfo && data.items[0].volumeInfo.imageLinks;
    var src = image && (image.thumbnail || image.smallThumbnail);
    if (src) {
      try {
        var imageUrl = new URL(src);
          if (!/^https?:$/.test(imageUrl.protocol) || !/^(books\.google\.com|books\.googleusercontent\.com)$/.test(imageUrl.hostname)) src = '';
          else {
            imageUrl.protocol = 'https:';
            src = imageUrl.href;
          }
      } catch (error) {
        src = '';
      }
    }

    try {
      localStorage.setItem(cacheKey, JSON.stringify({
        src: src || '',
        expires: Date.now() + (src ? 30 : 7) * 24 * 60 * 60 * 1000
      }));
    } catch (error) {
      console.warn('Could not save the Google Books cover cache.', error);
    }
    if (src) applyGoogleCover(placeholder, src);
  }

  function processGoogleCoverQueue() {
    while (!googleBooksPaused && googleCoverActive < googleCoverConcurrency && googleCoverQueue.length) {
      var placeholder = googleCoverQueue.shift();
      googleCoverActive++;
      lookupGoogleCover(placeholder).catch(function (error) {
        console.warn('Google Books cover lookup failed; keeping the local placeholder.', error);
        if (error.status === 403 || error.status === 429) googleBooksPaused = true;
      }).then(function () {
        googleCoverActive--;
        processGoogleCoverQueue();
      });
    }
  }

  function loadGoogleBookCovers() {
    if (googleBooksPaused) return;
    document.querySelectorAll('[data-google-cover]:not([data-google-cover-queued])').forEach(function (placeholder) {
      placeholder.dataset.googleCoverQueued = 'true';
      googleCoverQueue.push(placeholder);
    });
    processGoogleCoverQueue();
  }

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

  loadGoogleBookCovers();
  document.addEventListener('bs:content-updated', loadGoogleBookCovers);

  // <form data-confirm="..."> asks before submitting
  document.addEventListener('submit', function (e) {
    var msg = e.target.dataset ? e.target.dataset.confirm : '';
    if (msg && !window.confirm(msg)) e.preventDefault();
  });
})();
