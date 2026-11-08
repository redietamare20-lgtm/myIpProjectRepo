/* realtime.js - "real-time-like" updates using polling (setInterval-style) + Fetch API.
   Every few seconds we ask api/realtime.php for tiny change tokens. Only when a token
   changes do we reload the matching page section, so the database is hit lightly. */
(function () {
  'use strict';
  var base = Math.max(3000, parseInt(document.body.dataset.poll || '4000', 10));
  var tokens = { books: null, orders: null, notifs: null };
  var lastNotif = -1;                 // -1 = first call: just learn the current state, no toasts
  var seen = {};                      // notification ids already shown (prevents duplicates)
  var running = false, failures = 0, timer = null;

  function setBadges(n) {
    document.querySelectorAll('.js-notif-badge').forEach(function (el) { el.textContent = n; el.hidden = !(n > 0); });
  }

  async function poll() {
    if (running || document.hidden) return;
    running = true;
    try {
      var r = await BS.api('realtime.php', { params: { n: lastNotif } });
      failures = 0;

      if (tokens.books !== null && r.books !== tokens.books) BS.liveRefresh(['books']);
      tokens.books = r.books;

      if (r.orders !== null && r.orders !== undefined) {
        if (tokens.orders !== null && r.orders !== tokens.orders) BS.liveRefresh(['orders']);
        tokens.orders = r.orders;
      }
      if (r.notifs) {
        var nt = r.notifs.max_id + '-' + r.notifs.unread;
        if (tokens.notifs !== null && nt !== tokens.notifs) BS.liveRefresh(['notifs']);
        tokens.notifs = nt;
        setBadges(r.notifs.unread);
        r.notifs.new.forEach(function (n) {
          if (!seen[n.id]) { seen[n.id] = true; BS.toast(n.message, 'info'); }
        });
        lastNotif = r.notifs.max_id;
      }
      if (typeof r.cart_count === 'number') BS.setCartCount(r.cart_count);
    } catch (err) {
      failures++;
      if (failures === 3) BS.toast('Live updates paused - connection problem. Retrying...', 'error');
    }
    running = false;
  }

  function loop() {
    poll().then(function () {
      // slow down automatically when the server is not answering
      timer = setTimeout(loop, base * Math.min(1 + failures, 5));
    });
  }

  BS.pollNow = poll;
  document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
  loop();
})();
