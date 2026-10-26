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

  /* ---------- Loading state and double-submit guard for POST forms ---------- */
  (function submitGuard() {
    function reset(f, btn) {
      f.removeAttribute('data-submitting');
      if (btn) btn.classList.remove('is-loading');
    }
    document.addEventListener('submit', function (e) {
      var f = e.target;
      if (!f || f.tagName !== 'FORM' || e.defaultPrevented) return;        // cancelled by the confirm dialog
      if (f.id === 'form-login') return;                                   // login.php has its own handling
      if ((f.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
      if (f.getAttribute('data-submitting') === '1') { e.preventDefault(); return; }
      f.setAttribute('data-submitting', '1');
      var btn = e.submitter || f.querySelector('button[type="submit"], button:not([type])');
      // Wait one tick so a button's name/value is still sent with the form.
      setTimeout(function () { if (btn) btn.classList.add('is-loading'); }, 0);
      // Downloads (export) keep the page open, so give the button back after a while.
      setTimeout(function () { reset(f, btn); }, 6000);
    });
    window.addEventListener('pageshow', function () {
      document.querySelectorAll('form[data-submitting]').forEach(function (f) {
        f.removeAttribute('data-submitting');
      });
      document.querySelectorAll('.btn.is-loading').forEach(function (b) { b.classList.remove('is-loading'); });
    });
  })();

  /* ---------- Flash messages: dismiss button, success fades out ---------- */
  (function flash() {
    document.querySelectorAll('.message').forEach(function (m) {
      var isError = m.classList.contains('message-error');
      m.setAttribute('role', isError ? 'alert' : 'status');
      var close = document.createElement('button');
      close.type = 'button';
      close.className = 'msg-close';
      close.setAttribute('aria-label', 'Dismiss message');
      close.textContent = '\u00d7';
      function hide() {
        if (!m.parentNode) return;
        m.classList.add('is-leaving');
        setTimeout(function () { if (m.parentNode) m.parentNode.removeChild(m); }, 320);
      }
      close.addEventListener('click', hide);
      m.appendChild(close);
      if (!isError) setTimeout(hide, 6000);
    });
  })();

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

  /* ---------- Add admin: show/hide button and strength hint (advice only; the server decides) ---------- */
  (function passwordHelper() {
    var form = document.getElementById('form-admin');
    if (!form) return;
    var input = form.querySelector('input[name="password"]');
    if (!input || !input.parentNode) return;

    var parent = input.parentNode;
    var wrap = document.createElement('div');
    wrap.className = 'pw-wrap';
    parent.insertBefore(wrap, input);
    wrap.appendChild(input);

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'pw-toggle';
    toggle.textContent = 'Show';
    toggle.setAttribute('aria-label', 'Show password');
    toggle.setAttribute('aria-pressed', 'false');
    wrap.appendChild(toggle);

    var meter = document.createElement('div');
    meter.className = 'strength';
    meter.setAttribute('data-level', '0');
    var bar = document.createElement('div');
    bar.className = 'strength-bar';
    bar.appendChild(document.createElement('i'));
    var text = document.createElement('div');
    text.className = 'strength-text';
    text.setAttribute('aria-live', 'polite');
    meter.appendChild(bar);
    meter.appendChild(text);
    parent.appendChild(meter);

    // Put the username rule right under the Username box (the password rule is the strength hint).
    var hint = form.querySelector('p.hint');
    var userInput = form.querySelector('input[name="username"]');
    if (hint && userInput && userInput.parentNode && userInput.parentNode.tagName === 'LABEL') {
      hint.textContent = '3 to 50 letters, digits, _ . -';
      hint.className = 'strength-text';
      userInput.parentNode.appendChild(hint);
    }

    function level(p) {
      if (!p) return 0;
      if (p.length < 8) return 1;
      var classes = [/[a-z]/, /[A-Z]/, /\d/, /[^A-Za-z0-9]/].filter(function (r) { return r.test(p); }).length;
      var s = classes + (p.length >= 12 ? 1 : 0);
      if (s <= 1) return 1;
      if (s === 2) return 2;
      if (s === 3) return 3;
      return 4;
    }
    var WORDS = [
      'Use at least 8 characters.',
      'Weak: add capitals, numbers or symbols.',
      'Fair: add length or a symbol.',
      'Good password.',
      'Strong password.'
    ];
    var TOO_SHORT = 'Too short: use at least 8 characters.';

    function update() {
      var v = input.value;
      var l = level(v);
      meter.setAttribute('data-level', String(l));
      text.textContent = (v && v.length < 8) ? TOO_SHORT : WORDS[l];
    }

    toggle.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      toggle.textContent = show ? 'Hide' : 'Show';
      toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
      input.focus();
    });
    input.addEventListener('input', update);
    update();
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

    function setTemp(text) {
      var el = document.getElementById('temp-value');
      if (!el || el.textContent === text) return;
      el.textContent = text;
      el.classList.remove('tick');
      void el.offsetWidth;            // restart the small pulse
      el.classList.add('tick');
    }

    function setIndicators(led, state) {
      var l = document.getElementById('led-state');
      var s = document.getElementById('system-state');
      if (l && led) l.setAttribute('data-led', led);
      if (s && state) s.setAttribute('data-state', state);
    }

    // First paint: read the words the server already rendered.
    (function initIndicators() {
      var l = document.getElementById('led-state');
      var s = document.getElementById('system-state');
      var ledMap = { off: 'OFF', on: 'ON', blinking: 'BLINK' };
      if (l) l.setAttribute('data-led', ledMap[l.textContent.trim().toLowerCase()] || 'OFF');
      if (s) {
        var t = s.textContent.trim().toLowerCase();
        s.setAttribute('data-state', t.indexOf('running') === 0 ? 'running' : (t.indexOf('off') === 0 ? 'off' : 'idle'));
      }
    })();

    function render(d) {
      setTemp(typeof d.temp === 'number' ? d.temp.toFixed(1) : '--');
      setText('temp-label', d.label || '--');
      setText('mode-name', MODE[d.mode] || '--');
      setText('room-name', d.room || '--');
      setText('system-state', STATE[d.state] || '--');
      setText('led-state', LED[d.led] || '--');
      setText('updated-at', d.updated_at || '--');
      setIndicators(d.led, d.state);

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