/* admin.js - order status changes (approve / reject / update) */
(function () {
  'use strict';
  document.addEventListener('click', async function (e) {
    var b = e.target.closest('.js-order-status');
    if (!b) return;
    var status = b.dataset.status;
    if (!status && b.dataset.select) { var s = document.querySelector(b.dataset.select); status = s ? s.value : ''; }
    if (b.dataset.confirm && !window.confirm(b.dataset.confirm)) return;
    BS.busy(b, true);
    try {
      var r = await BS.api('orders.php', { method: 'POST', body: { action: 'status', order_id: parseInt(b.dataset.id, 10), status: status } });
      BS.toast(r.message, 'success');
      await BS.liveRefresh(['orders']);
      if (BS.pollNow) BS.pollNow();
    } catch (err) {
      BS.toast(err.message, 'error');
    }
    BS.busy(b, false);
  });
})();
