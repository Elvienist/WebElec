/* Room Monitor: browser behavior (Pages) */
(function () {
  'use strict';

  /* ---------- Grain particles: fine white specks that react to the pointer ---------- */
  (function grain() {
    var canvas = document.getElementById('bg-canvas');
    if (!canvas || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var w = 0, h = 0, pts = [], raf = null, t = 0;
    var mouse = { x: -9999, y: -9999 };

    function build() {
      var n = Math.min(520, Math.round((w * h) / 3200));
      pts = [];
      for (var i = 0; i < n; i++) {
        pts.push({
          x: Math.random() * w,
          y: Math.random() * h,
          vx: (Math.random() - 0.5) * 0.18,
          vy: -0.04 - Math.random() * 0.22,
          ix: 0, iy: 0,
          s: 0.7 + Math.random() * 1.2,
          a: 0.25 + Math.random() * 0.65,
          ph: Math.random() * 6.283,
          sp: 0.6 + Math.random() * 1.8
        });
      }
    }

    function resize() {
      w = window.innerWidth;
      h = window.innerHeight;
      canvas.width = w * dpr;
      canvas.height = h * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      build();
      draw();
    }

    function draw() {
      ctx.clearRect(0, 0, w, h);
      if (mouse.x > -999) {
        var g = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, 160);
        g.addColorStop(0, 'rgba(255,255,255,0.07)');
        g.addColorStop(1, 'rgba(255,255,255,0)');
        ctx.fillStyle = g;
        ctx.fillRect(mouse.x - 160, mouse.y - 160, 320, 320);
      }
      ctx.fillStyle = '#fff';
      for (var i = 0; i < pts.length; i++) {
        var p = pts[i];
        ctx.globalAlpha = p.a * (0.55 + 0.45 * Math.sin(t * p.sp + p.ph));
        ctx.fillRect(p.x, p.y, p.s, p.s);
      }
      ctx.globalAlpha = 1;
    }

    function step() {
      t += 0.016;
      var R = 150, R2 = R * R;
      for (var i = 0; i < pts.length; i++) {
        var p = pts[i];
        var dx = p.x - mouse.x, dy = p.y - mouse.y;
        var d2 = dx * dx + dy * dy;
        if (d2 < R2 && d2 > 1) {
          var d = Math.sqrt(d2);
          var f = Math.pow(1 - d / R, 2) * 2.2;
          p.ix += (dx / d) * f * 0.5 - (dy / d) * f * 0.12;
          p.iy += (dy / d) * f * 0.5 + (dx / d) * f * 0.12;
        }
        p.x += p.vx + p.ix;
        p.y += p.vy + p.iy;
        p.ix *= 0.92;
        p.iy *= 0.92;
        if (p.x < -2) p.x = w + 2; else if (p.x > w + 2) p.x = -2;
        if (p.y < -2) p.y = h + 2; else if (p.y > h + 2) p.y = -2;
      }
      draw();
      raf = requestAnimationFrame(step);
    }

    function burst(x, y) {
      var R = 240;
      for (var i = 0; i < pts.length; i++) {
        var p = pts[i];
        var dx = p.x - x, dy = p.y - y;
        var d = Math.sqrt(dx * dx + dy * dy) || 1;
        if (d < R) {
          var f = (1 - d / R) * 7;
          p.ix += (dx / d) * f;
          p.iy += (dy / d) * f;
        }
      }
    }

    function start() { if (!reduce.matches && raf === null && !document.hidden) raf = requestAnimationFrame(step); }
    function stop() { if (raf !== null) { cancelAnimationFrame(raf); raf = null; } }

    window.addEventListener('resize', resize);
    window.addEventListener('pointermove', function (e) { mouse.x = e.clientX; mouse.y = e.clientY; });
    window.addEventListener('pointerleave', function () { mouse.x = -9999; mouse.y = -9999; });
    window.addEventListener('pointerdown', function (e) { if (!reduce.matches) burst(e.clientX, e.clientY); });
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
    if (reduce.addEventListener) {
      reduce.addEventListener('change', function () { reduce.matches ? (stop(), draw()) : start(); });
    }

    resize();
    start();
  })();

  /* ---------- Card spotlight follows the pointer ---------- */
  (function spotlight() {
    var cards = document.querySelectorAll('section.card');
    if (!cards.length) return;
    var last = null, queued = false;
    function apply() {
      queued = false;
      if (!last) return;
      cards.forEach(function (c) {
        var r = c.getBoundingClientRect();
        c.style.setProperty('--mx', (last.clientX - r.left) + 'px');
        c.style.setProperty('--my', (last.clientY - r.top) + 'px');
      });
    }
    window.addEventListener('pointermove', function (e) {
      last = e;
      if (!queued) { queued = true; requestAnimationFrame(apply); }
    });
  })();

  /* ---------- Confirm dialogs: <form data-confirm="Question?"> ---------- */
  document.addEventListener('submit', function (e) {
    var q = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (q && !window.confirm(q)) e.preventDefault();
  });

  /* ---------- Custom limit fields show only for custom mode ---------- */
  (function customFields() {
    var form = document.getElementById('form-mode');
    var fields = document.getElementById('custom-fields');
    if (!form || !fields) return;
    var select = form.querySelector('select[name="mode"]');
    if (!select) return;
    function sync() { fields.classList.toggle('hidden', select.value !== 'custom'); }
    select.addEventListener('change', sync);
    sync();
  })();

  /* ---------- Live status polling (Dashboard only) ---------- */
  (function live() {
    if (!document.getElementById('live-card')) return;

    var POLL_MS = 2000;
    var STATE = { running: 'Running', off: 'Off', idle: 'Idle (enter a room name)' };
    var LED = { OFF: 'Off', ON: 'On', BLINK: 'Blinking' };
    var MODE = { general: 'General', fire: 'Fire', custom: 'Custom' };
    var TONES = ['good', 'warn', 'danger', 'low', 'muted'];

    var note = document.getElementById('signal-note');
    var banner = document.getElementById('sim-banner');
    var noteDefault = note ? note.textContent : 'No signal from the sensor bridge';

    function setText(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }

    function render(d) {
      setText('temp-value', typeof d.temp === 'number' ? d.temp.toFixed(1) : '--');
      setText('temp-label', d.label || '--');
      setText('mode-name', MODE[d.mode] || '--');
      setText('room-name', d.room || '--');
      setText('system-state', STATE[d.state] || '--');
      setText('led-state', LED[d.led] || '--');
      setText('updated-at', d.updated_at || '--');

      var label = document.getElementById('temp-label');
      if (label) {
        TONES.forEach(function (x) { label.classList.remove('tone-' + x); });
        if (d.tone) label.classList.add('tone-' + d.tone);
      }
      if (note) {
        note.textContent = noteDefault;
        note.classList.toggle('hidden', d.signal !== false);
      }
      if (banner) banner.classList.toggle('hidden', !d.sim_on);
    }

    function connectionLost() {
      if (note) { note.textContent = 'Lost connection to the server'; note.classList.remove('hidden'); }
    }

    function poll() {
      fetch('status.php', { cache: 'no-store', credentials: 'same-origin' })
        .then(function (r) {
          if (r.status === 401) { window.location.href = 'login.php'; return null; }
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        })
        .then(function (d) {
          if (d === null) return;
          if (!d || d.ok !== true) throw new Error('Bad response');
          render(d);
        })
        .catch(connectionLost)
        .then(function () { setTimeout(poll, POLL_MS); });
    }

    setTimeout(poll, POLL_MS);
  })();
})();