/* admin.js - order status changes (approve / reject / update) */
(function () {
  'use strict';

  function drawSalesChart(canvas) {
    var rows;
    try { rows = JSON.parse(canvas.dataset.chart || '[]'); }
    catch (err) { console.error('Sales chart data could not be read.', err); return; }
    if (!Array.isArray(rows) || !rows.length) return;

    var context = canvas.getContext('2d');
    if (!context) {
      console.error('Canvas 2D is unavailable; the sales chart cannot be drawn.');
      return;
    }

    function render() {
      var width = canvas.clientWidth;
      if (!width) return;
      var height = 250;
      var ratio = Math.max(1, window.devicePixelRatio || 1);
      canvas.width = Math.round(width * ratio);
      canvas.height = Math.round(height * ratio);
      context.setTransform(ratio, 0, 0, ratio, 0, 0);

      var style = window.getComputedStyle(document.documentElement);
      var border = style.getPropertyValue('--border').trim() || '#E7E5DD';
      var muted = style.getPropertyValue('--muted').trim() || '#737B75';
      var accent = style.getPropertyValue('--accent').trim() || '#D6A75D';
      var values = rows.map(function (row) { return Number(row.value) || 0; });
      var max = Math.max.apply(null, [1].concat(values));
      var left = 58, right = 12, top = 14, bottom = 34;
      var plotWidth = Math.max(0, width - left - right);
      var plotHeight = height - top - bottom;
      context.clearRect(0, 0, width, height);
      context.font = '12px Inter, system-ui, sans-serif';
      context.textBaseline = 'middle';
      context.fillStyle = muted;
      context.strokeStyle = border;
      context.lineWidth = 1;

      for (var tick = 0; tick <= 4; tick++) {
        var y = top + plotHeight * tick / 4;
        var amount = Math.round(max * (4 - tick) / 4);
        context.beginPath();
        context.moveTo(left, y);
        context.lineTo(width - right, y);
        context.stroke();
        context.textAlign = 'right';
        context.fillText('$' + amount.toLocaleString(), left - 10, y);
      }

      var points = values.map(function (value, index) {
        var x = rows.length > 1 ? left + plotWidth * index / (rows.length - 1) : left + plotWidth / 2;
        return { x: x, y: top + plotHeight * (1 - value / max) };
      });
      var gradient = context.createLinearGradient(0, top, 0, top + plotHeight);
      gradient.addColorStop(0, 'rgba(214,167,93,.28)');
      gradient.addColorStop(1, 'rgba(214,167,93,0)');
      context.beginPath();
      context.moveTo(points[0].x, top + plotHeight);
      points.forEach(function (point) { context.lineTo(point.x, point.y); });
      context.lineTo(points[points.length - 1].x, top + plotHeight);
      context.closePath();
      context.fillStyle = gradient;
      context.fill();

      context.beginPath();
      points.forEach(function (point, index) {
        if (index === 0) context.moveTo(point.x, point.y);
        else context.lineTo(point.x, point.y);
      });
      context.strokeStyle = accent;
      context.lineWidth = 2.5;
      context.lineJoin = 'round';
      context.stroke();

      points.forEach(function (point, index) {
        context.beginPath();
        context.arc(point.x, point.y, 3.5, 0, Math.PI * 2);
        context.fillStyle = accent;
        context.fill();
        context.textAlign = 'center';
        context.fillStyle = muted;
        context.fillText(rows[index].label, point.x, height - 13);
      });
    }

    render();
  }

  function initSalesCharts() {
    document.querySelectorAll('.js-sales-chart').forEach(drawSalesChart);
  }

  initSalesCharts();
  document.addEventListener('bs:content-updated', initSalesCharts);
  var resizeTimer;
  window.addEventListener('resize', function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(initSalesCharts, 120);
  });

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
