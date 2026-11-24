/* ==========================================================================
   Divé krídla – ADMINISTRÁCIA (22. 11. 2026), bez knižníc.
   - bočný panel: na mobile vysúvací, na desktope zbaliteľný ( [ ), stav v localStorage
   - rýchle hľadanie Ctrl+K / „/“: sekcie administrácie, stránky webu, akcie (bez diakritiky)
   - rýchlejšie prechody: prednačítanie stránky pri prejdení myšou / dotyku, ukazovateľ načítania
   - formuláre: neuložené zmeny (panel dole + upozornenie pri odchode), počítadlá znakov, živé náhľady
   - tabuľky: okamžité filtrovanie, potvrdenie nebezpečných akcií
   Server všetko overuje znova (CSRF, oprávnenia) – tento skript len uľahčuje prácu.
   ========================================================================== */
(function () {
  'use strict';
  var d = document, root = d.documentElement;
  var $ = function (s, r) { return (r || d).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); };
  var norm = function (s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); };
  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { if (v === null) localStorage.removeItem(k); else localStorage.setItem(k, v); } catch (e) {} }
  };
  var typing = function (t) { return t && (t.closest('input, textarea, select, [contenteditable]')); };

  /* ======================= BOČNÝ PANEL ======================= */
  var openBtn = $('[data-adm-open]'), scrim = $('.adm-scrim');
  function sideOpen(on) {
    root.classList.toggle('adm-side-open', on);
    if (scrim) scrim.hidden = !on;
    if (openBtn) openBtn.setAttribute('aria-expanded', on ? 'true' : 'false');
    if (on) { var a = $('.adm-nav__link.is-active') || $('.adm-nav__link'); if (a) a.focus({ preventScroll: true }); }
  }
  if (openBtn) openBtn.addEventListener('click', function () { sideOpen(true); });
  $$('[data-adm-close]').forEach(function (b) { b.addEventListener('click', function () { sideOpen(false); }); });
  function collapse() {
    var on = !root.classList.contains('adm-collapsed');
    root.classList.toggle('adm-collapsed', on);
    store.set('dk-adm-collapsed', on ? '1' : null);
  }
  $$('[data-adm-collapse]').forEach(function (b) { b.addEventListener('click', collapse); });
  // aktívna položka viditeľná aj v dlhom menu
  var act = $('.adm-nav__link.is-active'); if (act && act.scrollIntoView) act.scrollIntoView({ block: 'nearest' });

  /* ======================= RÝCHLE HĽADANIE (Ctrl+K) ======================= */
  var cmd = $('#adm-cmd'), q = cmd && $('[data-adm-cmd-q]', cmd), list = cmd && $('.adm-cmd__list', cmd), empty = cmd && $('[data-adm-cmd-empty]', cmd);
  var items = cmd ? $$('li', list) : [], sel = -1, lastFocus = null;
  items.forEach(function (li) { var el = li.firstElementChild; li._k = norm(el.getAttribute('data-k') + ' ' + el.textContent); });
  function visible() { return items.filter(function (li) { return !li.hidden; }); }
  function mark(i) {
    var v = visible();
    items.forEach(function (li) { li.firstElementChild.classList.remove('is-sel'); li.removeAttribute('aria-selected'); });
    if (!v.length) { sel = -1; return; }
    sel = (i + v.length) % v.length;
    var el = v[sel].firstElementChild; el.classList.add('is-sel'); v[sel].setAttribute('aria-selected', 'true');
    el.scrollIntoView({ block: 'nearest' });
  }
  function filter() {
    var words = norm(q.value).split(/\s+/).filter(Boolean);
    items.forEach(function (li) { li.hidden = words.length > 0 && !words.every(function (w) { return li._k.indexOf(w) !== -1; }); });
    if (empty) empty.hidden = visible().length > 0;
    mark(0);
  }
  function cmdOpen() {
    if (!cmd || !cmd.hidden) return;
    lastFocus = d.activeElement; sideOpen(false);
    cmd.hidden = false; q.value = ''; filter(); q.focus();
    d.body.style.overflow = 'hidden';
  }
  function cmdClose() {
    if (!cmd || cmd.hidden) return;
    cmd.hidden = true; d.body.style.overflow = '';
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
  function run(el) {
    if (!el) return;
    var a = el.getAttribute('data-adm-action');
    if (a === 'theme') { cmdClose(); var t = $('#theme-toggle'); if (t) t.click(); return; }
    if (a === 'collapse') { cmdClose(); collapse(); return; }
    if (el.href) { prefetch(el.href); loading(); location.href = el.href; }
  }
  $$('[data-adm-cmd]').forEach(function (b) { b.addEventListener('click', cmdOpen); });
  if (cmd) {
    q.addEventListener('input', filter);
    cmd.addEventListener('click', function (e) {
      if (e.target === cmd) { cmdClose(); return; }
      var el = e.target.closest('a, button');
      if (el && list.contains(el)) { e.preventDefault(); run(el); }
    });
    cmd.addEventListener('mousemove', function (e) {
      var li = e.target.closest('li'); if (!li || li.hidden) return;
      var i = visible().indexOf(li); if (i !== sel) mark(i);
    });
    cmd.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); mark(sel + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); mark(sel - 1); }
      else if (e.key === 'Enter') { e.preventDefault(); var v = visible(); if (v[sel]) run(v[sel].firstElementChild); }
      else if (e.key === 'Escape') { e.preventDefault(); cmdClose(); }
      else if (e.key === 'Tab') { e.preventDefault(); q.focus(); }
    });
  }

  /* ======================= KLÁVESOVÉ SKRATKY ======================= */
  var gPressed = 0;
  d.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); if (cmd && cmd.hidden) cmdOpen(); else cmdClose(); return; }
    if (e.key === 'Escape' && root.classList.contains('adm-side-open')) { sideOpen(false); return; }
    if (e.ctrlKey || e.metaKey || e.altKey || typing(e.target) || (cmd && !cmd.hidden) || $('.pp-picker.is-modal, .bo-lb.is-open, .lightbox:not([hidden])')) return;
    if (e.key === '/') { e.preventDefault(); cmdOpen(); }
    else if (e.key === '[') { collapse(); }
    else if (e.key === 'g' || e.key === 'G') { gPressed = Date.now(); }
    else if ((e.key === 'n' || e.key === 'N') && Date.now() - gPressed < 900) { loading(); location.href = 'admin'; }
  });

  /* ======================= RÝCHLEJŠIE PRECHODY ======================= */
  var done = {};
  function sameOrigin(href) {
    try { var u = new URL(href, location.href); return u.origin === location.origin ? u : null; } catch (e) { return null; }
  }
  function prefetch(href) {
    var u = sameOrigin(href); if (!u) return;
    if (/\/(api|odhlasenie)/.test(u.pathname) || /\.(pdf|jpe?g|png|webp|zip|mp4)$/i.test(u.pathname)) return;
    var key = u.pathname + u.search;
    if (done[key] || key === location.pathname + location.search) return;
    done[key] = true;
    var l = d.createElement('link'); l.rel = 'prefetch'; l.href = key; l.as = 'document';
    d.head.appendChild(l);
  }
  var hoverT = null;
  function linkFrom(e) { var a = e.target.closest && e.target.closest('a[href]'); return a && !a.hasAttribute('download') && a.target !== '_blank' ? a : null; }
  d.addEventListener('pointerover', function (e) {
    var a = linkFrom(e); if (!a) return;
    clearTimeout(hoverT); hoverT = setTimeout(function () { prefetch(a.href); }, 65);
  }, { passive: true });
  d.addEventListener('pointerout', function () { clearTimeout(hoverT); }, { passive: true });
  d.addEventListener('touchstart', function (e) { var a = linkFrom(e); if (a) prefetch(a.href); }, { passive: true });
  d.addEventListener('focusin', function (e) { var a = linkFrom(e); if (a) prefetch(a.href); });
  // po načítaní v čase nečinnosti prednačítať sekcie bočného panela (stránky administrácie sú malé)
  (window.requestIdleCallback || function (f) { setTimeout(f, 1200); })(function () {
    if (navigator.connection && (navigator.connection.saveData || /2g/.test(navigator.connection.effectiveType || ''))) return;
    $$('.adm-nav__link[href]').slice(0, 12).forEach(function (a) { prefetch(a.href); });
  });

  function loading() { root.classList.add('adm-loading'); }
  d.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var a = linkFrom(e); if (!a) return;
    var u = sameOrigin(a.href); if (!u) return;
    if (u.pathname === location.pathname && u.search === location.search) return;   // kotva na tej istej stránke
    if (a.closest('#adm-side')) sideOpen(false);
    loading();
  });
  d.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var f = e.target, b = e.submitter || $('[type="submit"]', f);
    var c = f.getAttribute('data-confirm');   // tlačidlá s data-confirm potvrdzuje už bo-ui.js pri kliknutí
    if (c && !confirm(c)) { e.preventDefault(); return; }
    f._admSubmitting = true;
    if (b && b.classList.contains('adm-btn')) b.classList.add('is-busy');
    if (!f.hasAttribute('data-ajax') && !f.closest('[data-gupload]')) loading();
  });
  window.addEventListener('pageshow', function () { root.classList.remove('adm-loading'); $$('.adm-btn.is-busy').forEach(function (b) { b.classList.remove('is-busy'); }); });
  window.addEventListener('pagereveal', function (e) { if (e.viewTransition) root.classList.add('adm-vt'); });

  /* ======================= FORMULÁRE: NEULOŽENÉ ZMENY ======================= */
  function snap(f) {
    var out = [];
    $$('input, textarea, select', f).forEach(function (el) {
      if (!el.name || el.type === 'hidden' || el.type === 'file') return;
      out.push(el.name + '=' + ((el.type === 'checkbox' || el.type === 'radio') ? (el.checked ? el.value : '') : el.value));
    });
    return out.join('&');
  }
  var dirtyForms = $$('form[data-adm-dirty]');
  dirtyForms.forEach(function (f) {
    f._orig = snap(f);
    var msg = $('[data-adm-savemsg]', f), base = msg ? msg.textContent : '';
    var check = function () {
      var dirty = snap(f) !== f._orig;
      f.classList.toggle('is-dirty', dirty);
      if (msg) msg.textContent = dirty ? 'Máte neuložené zmeny' : base;
    };
    f.addEventListener('input', check); f.addEventListener('change', check);
    var r = $('[data-adm-reset]', f);
    if (r) r.addEventListener('click', function () { f.reset(); setTimeout(function () { check(); $$('[data-pv], [data-adm-count]', f).forEach(function (el) { el.dispatchEvent(new Event('input', { bubbles: true })); }); }, 0); });
  });
  window.addEventListener('beforeunload', function (e) {
    if (dirtyForms.some(function (f) { return f.classList.contains('is-dirty') && !f._admSubmitting; })) { e.preventDefault(); e.returnValue = ''; }
  });

  /* ======================= POČÍTADLÁ ZNAKOV ======================= */
  $$('[data-adm-count]').forEach(function (el) {
    var out = d.getElementById(el.getAttribute('data-adm-count')); if (!out) return;
    var max = +el.getAttribute('data-max') || 0;
    var upd = function () {
      var n = el.value.length || (el.value === '' && el.placeholder ? el.placeholder.length : 0);
      out.textContent = n + (max ? ' / ' + max : '');
      out.classList.toggle('is-over', max > 0 && n > max);
    };
    el.addEventListener('input', upd); upd();
  });

  /* ======================= ŽIVÉ NÁHĽADY ======================= */
  $$('[data-pv]').forEach(function (el) {
    var key = el.getAttribute('data-pv');
    var outs = $$('[data-pv-out="' + key + '"]');
    var upd = function () {
      var v = el.type === 'checkbox' ? el.checked : el.value.trim();
      outs.forEach(function (o) {
        if (el.type === 'checkbox') { if (v) o.removeAttribute('data-off'); else o.setAttribute('data-off', ''); return; }
        var text = v || el.placeholder || o.getAttribute('data-default') || '';
        if (o.hasAttribute('data-pv-href')) { o.href = v || '#'; o.hidden = !v; }
        else { o.textContent = text; }
        if (o.hasAttribute('data-pv-hide')) o.hidden = !text;
      });
    };
    el.addEventListener('input', upd); el.addEventListener('change', upd); upd();
  });

  /* ======================= FOTOGRAFIE STRÁNOK: FILTER STRÁNOK ======================= */
  var ppf = $('[data-pp-pagefilter]');
  if (ppf) {
    var pages = $$('.pp-page');
    pages.forEach(function (p) { var h = $('h2', p); p._q = norm(h ? h.textContent : ''); });
    ppf.addEventListener('input', function () {
      var w = norm(ppf.value);
      pages.forEach(function (p) { p.hidden = w !== '' && p._q.indexOf(w) === -1; });
    });
  }

  /* ======================= VÝREZ FOTOGRAFIE (admin-fotky) =======================
     Zaostrený bod x/y v % = object-position, priblíženie z = scale okolo toho bodu (rovnako ako na webe, bo-ui.css časť 22). */
  var crop = $('#crop');
  if (crop && crop.showModal) {
    var cf = $('[data-crop-form]', crop), main = $('[data-crop-main]', crop), side = $('[data-crop-side]', crop);
    var imgs = $$('img', crop), zr = $('[data-crop-zoom]', crop), zo = $('[data-crop-zoomout]', crop), po = $('[data-crop-pos]', crop);
    var st = { x: 50, y: 50, z: 1 };
    var clampN = function (v, a, b) { return Math.max(a, Math.min(b, v)); };
    var paint = function () {
      imgs.forEach(function (im) { im.style.setProperty('--x', st.x + '%'); im.style.setProperty('--y', st.y + '%'); im.style.setProperty('--z', st.z); });
      zr.value = st.z; zo.textContent = Math.round(st.z * 100) + ' %';
      po.textContent = 'Bod ' + Math.round(st.x) + ' % × ' + Math.round(st.y) + ' %';
      cf.x.value = st.x.toFixed(2); cf.y.value = st.y.toFixed(2); cf.z.value = st.z.toFixed(3);
    };
    var overflow = function () {   // o koľko px je zobrazená fotografia väčšia ako rámček (vodorovne, zvisle)
      var im = $('img', main), W = main.clientWidth, H = main.clientHeight, nw = im.naturalWidth || W, nh = im.naturalHeight || H;
      var s = Math.max(W / nw, H / nh) * st.z;
      return [nw * s - W, nh * s - H];
    };
    $$('[data-crop]').forEach(function (b) {
      b.addEventListener('click', function () {
        cf.slug.value = b.dataset.slug; cf.slot.value = b.dataset.slot;
        $('[data-crop-title]', crop).textContent = b.dataset.label;
        var r = (b.dataset.ratios || '4/3,4/3').split(',');
        var num = function (s) { var p = s.split('/'); return (+p[0]) / (+p[1] || 1); };
        main.style.aspectRatio = r[0]; side.style.aspectRatio = r[1] || r[0];
        main.style.maxWidth = 'calc(58vh * ' + num(r[0]).toFixed(4) + ')';        // pomer strán ostane presný aj na nízkej obrazovke
        side.style.maxWidth = 'calc(34vh * ' + num(r[1] || r[0]).toFixed(4) + ')';
        imgs.forEach(function (im) { im.src = b.dataset.src; });
        st = { x: +b.dataset.x, y: +b.dataset.y, z: +b.dataset.z || 1 };
        paint(); crop.showModal(); main.focus({ preventScroll: true });
      });
    });
    $$('[data-crop-close]', crop).forEach(function (b) { b.addEventListener('click', function () { crop.close(); }); });
    crop.addEventListener('click', function (e) { if (e.target === crop) crop.close(); });
    var drag = null;
    main.addEventListener('pointerdown', function (e) {
      e.preventDefault(); main.setPointerCapture(e.pointerId);
      drag = { x: e.clientX, y: e.clientY, sx: st.x, sy: st.y, o: overflow() }; main.classList.add('is-drag');
    });
    main.addEventListener('pointermove', function (e) {
      if (!drag) return;
      if (drag.o[0] > 0.5) st.x = clampN(drag.sx - (e.clientX - drag.x) / drag.o[0] * 100, 0, 100);
      if (drag.o[1] > 0.5) st.y = clampN(drag.sy - (e.clientY - drag.y) / drag.o[1] * 100, 0, 100);
      paint();
    });
    var end = function () { drag = null; main.classList.remove('is-drag'); };
    main.addEventListener('pointerup', end); main.addEventListener('pointercancel', end);
    main.addEventListener('wheel', function (e) { e.preventDefault(); st.z = clampN(st.z - e.deltaY * 0.0015, 1, 3); paint(); }, { passive: false });
    main.addEventListener('keydown', function (e) {
      var k = e.shiftKey ? 5 : 1, m = { ArrowLeft: [-k, 0], ArrowRight: [k, 0], ArrowUp: [0, -k], ArrowDown: [0, k] }[e.key];
      if (m) { e.preventDefault(); st.x = clampN(st.x + m[0], 0, 100); st.y = clampN(st.y + m[1], 0, 100); paint(); }
      else if (e.key === '+' || e.key === '=') { st.z = clampN(st.z + .05, 1, 3); paint(); }
      else if (e.key === '-') { st.z = clampN(st.z - .05, 1, 3); paint(); }
    });
    zr.addEventListener('input', function () { st.z = +zr.value; paint(); });
  }

  /* ======================= FAREBNÁ PALETA (admin-nastavenia?s=farby) ======================= */
  var palForm = $('[data-pal]');
  if (palForm) {
    var pv = $('[data-pal-pv]'), cList = $('[data-pal-contrast]'), mode = 'light';
    var hexOk = function (v) { return /^#[0-9a-f]{6}$/i.test(v); };
    var get = function (m, k) { var h = $('[data-pal-hex="' + m + '.' + k + '"]'); return h && hexOk(h.value) ? h.value.toLowerCase() : (h ? h.dataset.def : '#000000'); };
    var lum = function (hex) {
      var c = [1, 3, 5].map(function (i) { var v = parseInt(hex.substr(i, 2), 16) / 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); });
      return .2126 * c[0] + .7152 * c[1] + .0722 * c[2];
    };
    var ratio = function (a, b) { var x = lum(a), y = lum(b); return (Math.max(x, y) + .05) / (Math.min(x, y) + .05); };
    var KEYS = ['bg', 'surface', 'text', 'heading', 'muted', 'accent', 'btn_bg', 'btn_fg', 'panel', 'on_dark'];
    var PAIRS = [['Písmo / pozadie', 'text', 'bg'], ['Nadpisy / pozadie', 'heading', 'bg'], ['Tlmený text / pozadie', 'muted', 'bg'], ['Text tlačidiel', 'btn_fg', 'btn_bg'], ['Tmavé pásy', 'on_dark', 'panel']];
    var render = function () {
      KEYS.forEach(function (k) { pv.style.setProperty('--p-' + k.replace('_', '-'), get(mode, k)); });
      $('[data-pal-modelabel]').textContent = mode === 'dark' ? 'Tmavý režim' : 'Svetlý režim';
      cList.innerHTML = '';
      PAIRS.forEach(function (p) {
        var r = ratio(get(mode, p[1]), get(mode, p[2])), li = d.createElement('li'), ok = r >= 4.5, mid = r >= 3;
        li.className = ok ? 'is-ok' : (mid ? 'is-mid' : 'is-low');
        li.innerHTML = '<span class="pal-contrast__sw"><i></i><i></i></span><span class="pal-contrast__l"></span><strong></strong>';
        var sw = li.querySelectorAll('i'); sw[0].style.background = get(mode, p[2]); sw[1].style.background = get(mode, p[1]);
        li.querySelector('.pal-contrast__l').textContent = p[0];
        li.querySelector('strong').textContent = r.toFixed(1).replace('.', ',') + ' : 1 ' + (ok ? '✓' : (mid ? '· slabší' : '· nízky'));
        cList.appendChild(li);
      });
    };
    var setVal = function (m, k, v, from) {
      var h = $('[data-pal-hex="' + m + '.' + k + '"]'), c = $('[data-pal-color="' + m + '.' + k + '"]');
      if (from !== 'hex') h.value = v;
      if (hexOk(v)) { if (from !== 'color') c.value = v; c.nextElementSibling.style.background = v; h.removeAttribute('aria-invalid'); }
      else h.setAttribute('aria-invalid', 'true');
      h.closest('.pal-row').classList.toggle('is-changed', hexOk(v) && v.toLowerCase() !== h.dataset.def);
      render();
    };
    $$('[data-pal-color]', palForm).forEach(function (c) {
      var mk = c.getAttribute('data-pal-color').split('.');
      c.addEventListener('input', function () { setVal(mk[0], mk[1], c.value, 'color'); palForm.dispatchEvent(new Event('input')); });
    });
    $$('[data-pal-hex]', palForm).forEach(function (h) {
      var mk = h.getAttribute('data-pal-hex').split('.');
      h.addEventListener('input', function () { var v = h.value.trim(); if (/^[0-9a-f]{6}$/i.test(v)) { v = '#' + v; h.value = v; } setVal(mk[0], mk[1], v, 'hex'); });
      h.closest('.pal-row').classList.toggle('is-changed', h.value.toLowerCase() !== h.dataset.def);
    });
    $$('[data-pal-undo]', palForm).forEach(function (b) {
      var mk = b.getAttribute('data-pal-undo').split('.');
      b.addEventListener('click', function () { var h = $('[data-pal-hex="' + mk.join('.') + '"]'); setVal(mk[0], mk[1], h.dataset.def); palForm.dispatchEvent(new Event('input')); });
    });
    $$('[data-pal-preset]', palForm).forEach(function (b) {
      b.addEventListener('click', function () {
        var p = JSON.parse(b.getAttribute('data-pal-preset'));
        ['light', 'dark'].forEach(function (m) { KEYS.forEach(function (k) { if (p[m] && p[m][k]) setVal(m, k, p[m][k]); }); });
        $$('[data-pal-preset]', palForm).forEach(function (x) { x.classList.toggle('is-on', x === b); });
        palForm.dispatchEvent(new Event('input'));
      });
    });
    var tabs = $$('[data-pal-mode]', palForm);
    var showMode = function (m) {
      mode = m;
      tabs.forEach(function (t) { var on = t.getAttribute('data-pal-mode') === m; t.setAttribute('aria-selected', on); if (on) t.setAttribute('aria-current', 'true'); else t.removeAttribute('aria-current'); });
      $$('[data-pal-set]', palForm).forEach(function (s) { s.hidden = s.getAttribute('data-pal-set') !== m; });
      render();
    };
    tabs.forEach(function (t) { t.addEventListener('click', function (e) { e.preventDefault(); showMode(t.getAttribute('data-pal-mode')); }); });
    palForm.addEventListener('reset', function () { setTimeout(function () { $$('[data-pal-hex]', palForm).forEach(function (h) { var mk = h.getAttribute('data-pal-hex').split('.'); setVal(mk[0], mk[1], h.value); }); }, 0); });
    showMode(root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
  }

  /* ======================= TABUĽKY: FILTROVANIE ======================= */
  $$('[data-adm-filter]').forEach(function (inp) {
    var tbl = $(inp.getAttribute('data-adm-filter')); if (!tbl) return;
    var role = $('[data-adm-filter-role="' + inp.getAttribute('data-adm-filter') + '"]');
    var count = $('[data-adm-filter-count="' + inp.getAttribute('data-adm-filter') + '"]');
    var rows = $$('tbody tr', tbl);
    rows.forEach(function (r) { r._q = norm(r.getAttribute('data-q') || r.textContent); });
    var upd = function () {
      var w = norm(inp.value).split(/\s+/).filter(Boolean), rv = role ? role.value : '', n = 0;
      rows.forEach(function (r) {
        var ok = w.every(function (x) { return r._q.indexOf(x) !== -1; }) && (!rv || r.getAttribute('data-role') === rv);
        r.hidden = !ok; if (ok) n++;
      });
      if (count) count.textContent = n === rows.length ? rows.length + '' : n + ' z ' + rows.length;
    };
    inp.addEventListener('input', upd); if (role) role.addEventListener('change', upd); upd();
  });
})();
