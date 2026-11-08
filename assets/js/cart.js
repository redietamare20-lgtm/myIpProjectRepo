/* cart.js - add to cart, change quantity, remove, checkout, cancel order (customers) */
(function () {
  'use strict';

  document.addEventListener('click', async function (e) {
    var add = e.target.closest('.js-add-cart');
    if (add && !add.disabled) {
      e.preventDefault();
      var qty = 1;
      if (add.dataset.qty) { var q = document.querySelector(add.dataset.qty); if (q) qty = parseInt(q.value, 10) || 1; }
      BS.busy(add, true);
      try {
        var r = await BS.api('cart.php', { method: 'POST', body: { action: 'add', book_id: parseInt(add.dataset.id, 10), quantity: qty } });
        BS.toast(r.message, 'success');
        BS.setCartCount(r.cart_count);
      } catch (err) {
        if (err.status === 401) {
          var here = location.pathname.replace(BS.base + '/', '') + location.search;
          location.href = BS.url('login.php?next=' + encodeURIComponent(here));
          return;
        }
        BS.toast(err.message, 'error');
      }
      BS.busy(add, false);
      return;
    }

    var rm = e.target.closest('.js-cart-remove');
    if (rm) {
      BS.busy(rm, true);
      try {
        var r2 = await BS.api('cart.php', { method: 'POST', body: { action: 'remove', book_id: parseInt(rm.dataset.id, 10) } });
        BS.setCartCount(r2.cart_count);
      } catch (err) { BS.toast(err.message, 'error'); }
      await BS.liveRefresh(['cart']);
      return;
    }

    var cancel = e.target.closest('.js-cancel-order');
    if (cancel) {
      if (!window.confirm('Cancel this order?')) return;
      BS.busy(cancel, true);
      try {
        var r3 = await BS.api('orders.php', { method: 'POST', body: { action: 'cancel', order_id: parseInt(cancel.dataset.id, 10) } });
        BS.toast(r3.message, 'success');
        await BS.liveRefresh(['orders']);
      } catch (err) { BS.toast(err.message, 'error'); BS.busy(cancel, false); }
    }
  });

  document.addEventListener('change', async function (e) {
    var inp = e.target.closest('.js-cart-qty');
    if (!inp) return;
    try {
      var r = await BS.api('cart.php', { method: 'POST', body: { action: 'set', book_id: parseInt(inp.dataset.id, 10), quantity: parseInt(inp.value, 10) || 1 } });
      BS.setCartCount(r.cart_count);
    } catch (err) { BS.toast(err.message, 'error'); }
    await BS.liveRefresh(['cart']);
  });

  var form = document.getElementById('checkout-form');
  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var box = document.getElementById('checkout-error');
      var btn = form.querySelector('button[type="submit"]');
      box.hidden = true;
      BS.busy(btn, true);
      try {
        var r = await BS.api('orders.php', { method: 'POST', body: {
          action: 'place', address: form.address.value, phone: form.phone.value, notes: form.notes.value } });
        location.href = BS.url('customer/order-details.php?id=' + r.order_id);
      } catch (err) {
        box.textContent = err.message;
        box.hidden = false;
        BS.busy(btn, false);
      }
    });
  }
})();
