/* ==========================================================================
   Divé krídla – dizajn 2026 (vrstva „fx“), bez knižníc.
   Načítava sa iba pri vzhľade „Nový dizajn“ (admin → Vzhľad webu).
   - opona pri prechode medzi stránkami      - vlastný kurzor s popisom (myš)
   - magnetické tlačidlá, výplň zo smeru     - nadpisy po slovách, odhaľovanie pri posúvaní
   - parallax úvodnej fotografie            - 3D náklon kariet s odleskom
   - skrývanie hlavičky, ukazovateľ čítania
   - kruhové prepnutie tmavého režimu        - počítadlá čísiel
   Rešpektuje „obmedziť pohyb“ (prefers-reduced-motion) a dotykové zariadenia.
   ========================================================================== */
(function () {
  'use strict';
  var d = document, root = d.documentElement, body = d.body;
  var $$ = function (s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); };
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;
  var lerp = function (a, b, t) { return a + (b - a) * t; };
  var clamp = function (v, a, b) { return Math.max(a, Math.min(b, v)); };
  root.classList.add('fx-js');
  /* efekt vypnutý v administrácii (Vzhľad a efekty) → trieda fx-off-… na <html> */
  var off = function (k) { return root.classList.contains('fx-off-' + k); };

  /* ---------- pomocník: spoločná slučka requestAnimationFrame ---------- */
  var tasks = [];
  function onFrame(fn) { tasks.push(fn); }
  var scrollY = window.pageYOffset, lastY = scrollY, velocity = 0;
  (function loop() {
    scrollY = window.pageYOffset;
    velocity = lerp(velocity, scrollY - lastY, 0.18);
    lastY = scrollY;
    for (var i = 0; i < tasks.length; i++) tasks[i]();
    requestAnimationFrame(loop);
  })();

  /* ---------- pozorovateľ: pridá .fx-in, keď je prvok na obrazovke ---------- */
  var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (en) {
    en.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('fx-in'); io.unobserve(e.target); if (e.target._fxIn) e.target._fxIn(); } });
  }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 }) : null;
  function watch(el) { if (io && !reduce) io.observe(el); else { el.classList.add('fx-in'); if (el._fxIn) el._fxIn(); } }

  /* ======================= 1. PRECHODY MEDZI STRÁNKAMI ======================= */
  (function () {
    if (reduce || off('curtain')) return;
    d.addEventListener('click', function (e) {
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var a = e.target.closest && e.target.closest('a[href]');
      if (!a || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-lightbox') || a.closest('[data-bo-gallery]')) return;
      var href = a.getAttribute('href');
      if (!href || href.charAt(0) === '#' || /^(mailto:|tel:|javascript:)/i.test(href)) return;
      var url;
      try { url = new URL(a.href, location.href); } catch (x) { return; }
      if (url.origin !== location.origin) return;
      if (url.pathname === location.pathname && url.search === location.search && url.hash) return;   // kotva na tej istej stránke
      if (/\.(pdf|jpe?g|png|webp|zip|mp4)$/i.test(url.pathname) || /\/api\//.test(url.pathname)) return;
      e.preventDefault();
      root.classList.add('fx-leaving');
      setTimeout(function () { location.href = url.href; }, 130);
    });
    window.addEventListener('pageshow', function (e) { if (e.persisted) { root.classList.remove('fx-leaving'); root.classList.add('fx-noenter'); } });
  })();

  /* ======================= 2. TEXTÚRA + UKAZOVATEĽ ČÍTANIA ======================= */
  if (!reduce && !off('grain')) { var grain = d.createElement('div'); grain.className = 'fx-grain'; grain.setAttribute('aria-hidden', 'true'); body.appendChild(grain); }
  if (!off('progress')) {
    var bar = d.createElement('div'); bar.className = 'fx-progress'; bar.setAttribute('aria-hidden', 'true'); body.appendChild(bar);
    onFrame(function () {
      var max = root.scrollHeight - innerHeight;
      bar.style.transform = 'scaleX(' + (max > 0 ? clamp(scrollY / max, 0, 1) : 0) + ')';
    });
  }

  /* ======================= 3. HLAVIČKA ======================= */
  (function () {
    var h = d.querySelector('.site-header'); if (!h) return;
    var noHide = off('hideheader');
    var hidden = false, scrolled = false, acc = 0, prev = scrollY;
    onFrame(function () {
      var dy = scrollY - prev; prev = scrollY;
      var busy = d.querySelector('.nav.is-open, .nav__more.is-open, .bo-noscroll') || d.activeElement && h.contains(d.activeElement);
      if ((scrollY > 24) !== scrolled) { scrolled = !scrolled; h.classList.toggle('fx-scrolled', scrolled); }
      acc = (dy > 0) === (acc > 0) ? acc + dy : dy;
      var want = hidden;
      if (scrollY < 140 || busy || noHide) want = false;
      else if (acc > 40) want = true;
      else if (acc < -30) want = false;
      if (want !== hidden) { hidden = want; h.classList.toggle('fx-hidden', hidden); }
    });
  })();

  /* ======================= 4. NADPISY PO SLOVÁCH ======================= */
  function splitWords(el) {
    if (el.classList.contains('fx-split') || el.children.length) return false;   // len čistý text (bez vnorených značiek)
    var text = el.textContent.replace(/\s+/g, ' ').trim(); if (!text) return false;
    el.setAttribute('aria-label', text);
    el.innerHTML = text.split(' ').map(function (w, i) {
      var s = d.createElement('span'); s.textContent = w;
      return '<span class="fx-w" aria-hidden="true"><span class="fx-wi" style="--i:' + i + '">' + s.innerHTML + '</span></span>';
    }).join(' ');
    el.classList.add('fx-split');
    return true;
  }
  $$('.hero h1, .page-hero h1').forEach(function (h) {
    if (splitWords(h)) requestAnimationFrame(function () { requestAnimationFrame(function () { h.classList.add('fx-in'); }); });
    else h.classList.add('fx-split', 'fx-in');
  });
  $$('.section-head h2, .band--forest .cta h2, .service > .service__main > h2').forEach(function (h) { if (splitWords(h)) watch(h); });

  /* ======================= 5. ODHAĽOVANIE PRI POSÚVANÍ ======================= */
  // postupné oneskorenie pre súrodencov (karty, galérie)
  $$('.reveal, [data-reveal]').forEach(function (el) {
    var p = el.parentElement; if (!p) return;
    var sib = Array.prototype.filter.call(p.children, function (c) { return c.classList.contains('reveal') || c.hasAttribute('data-reveal'); });
    if (sib.length > 1) el.style.setProperty('--i', sib.indexOf(el) % 6);
  });
  // fotografie v obsahu – odhalenie cez masku (nie úvodné, tie animuje CSS)
  $$('main .bo-figure, main .photo-grid > a, main .photo-grid > figure, main .gallery > a').forEach(function (el, i) {
    if (el.closest('.hero, .page-hero')) return;
    var r = el.getBoundingClientRect(); if (r.top < innerHeight * 0.9) return;   // už viditeľné – nemeniť
    el.setAttribute('data-fx', 'clip'); el.style.setProperty('--i', i % 4); watch(el);
  });
  $$('.bo-gallery').forEach(function (g) {
    if (g.getBoundingClientRect().top < innerHeight * 0.9) return;
    $$('.bo-gallery__item', g).forEach(function (it, i) { it.setAttribute('data-fx', 'up'); it.style.setProperty('--i', i % 8); watch(it); });
  });
  $$('.bo-videos > *, .lib-grid > li').forEach(function (it, i) {
    if (it.getBoundingClientRect().top < innerHeight * 0.9) return;
    it.setAttribute('data-fx', 'up'); it.style.setProperty('--i', i % 6); watch(it);
  });

  /* ======================= 6. POČÍTADLÁ ======================= */
  $$('.stats strong').forEach(function (el) {
    var m = el.textContent.trim().match(/^(\d+)(.*)$/); if (!m || reduce) return;
    var target = parseInt(m[1], 10), rest = m[2]; if (target < 2) return;
    el.textContent = '0' + rest;
    el._fxIn = function () {
      var t0 = performance.now(), dur = 1400;
      (function step(t) {
        var k = Math.min(1, (t - t0) / dur); k = 1 - Math.pow(1 - k, 4);
        el.textContent = Math.round(target * k) + rest;
        if (k < 1) requestAnimationFrame(step);
      })(t0);
    };
    watch(el);
  });

  /* ======================= 7. ÚVOD: PARALLAX FOTOGRAFIE, NÁPIS V PÄTIČKE ======================= */
  (function () {
    var hero = d.querySelector('.hero'), photo = hero && hero.querySelector('.hero__photo'), img = photo && photo.querySelector('img');
    if (photo && img) {
      var wrap = d.createElement('span'); wrap.className = 'fx-par';
      img.parentNode.insertBefore(wrap, img); wrap.appendChild(img);
      if (!reduce) {
        var tx = 0, ty = 0, cx = 0, cy = 0;
        onFrame(function () {
          var r = hero.getBoundingClientRect();
          if (r.bottom < 0) return;
          var p = clamp(-r.top / r.height, -1, 1);
          cx = lerp(cx, tx, 0.07); cy = lerp(cy, ty, 0.07);
          var H = wrap.offsetHeight || 1, oy = clamp(p, 0, 1) * H * 0.06 + cy * -5;   // zväčšenie 1.18 dáva rezervu 9 % na každej strane
          img.style.transform = 'translate3d(' + (cx * -10).toFixed(1) + 'px,' + oy.toFixed(1) + 'px,0) scale(1.18)';
        });
        if (fine) hero.addEventListener('pointermove', function (e) {
          var r = hero.getBoundingClientRect();
          tx = (e.clientX - r.left) / r.width * 2 - 1; ty = (e.clientY - r.top) / r.height * 2 - 1;
          hero.style.setProperty('--hx', (e.clientX - r.left) + 'px'); hero.style.setProperty('--hy', (e.clientY - r.top) + 'px');
          hero.classList.add('fx-glow');
        });
        hero.addEventListener('pointerleave', function () { tx = 0; ty = 0; hero.classList.remove('fx-glow'); });
      }
    }
    // úvodný pás podstránok
    var ph = d.querySelector('.page-hero'), pimg = ph && ph.querySelector('.frame img');
    if (ph && !reduce) {
      if (fine) ph.addEventListener('pointermove', function (e) {
        var r = ph.getBoundingClientRect();
        ph.style.setProperty('--hx', (e.clientX - r.left) + 'px'); ph.style.setProperty('--hy', (e.clientY - r.top) + 'px');
      });
      if (pimg) onFrame(function () {
        var r = ph.getBoundingClientRect(); if (r.bottom < 0) return;
        pimg.style.transform = 'translate3d(0,' + (clamp(-r.top / r.height, 0, 1) * pimg.offsetHeight * 0.05).toFixed(1) + 'px,0) scale(1.12)';
      });
    }
    // veľký nápis v pätičke – písmená
    $$('.fx-wordmark').forEach(function (w) {
      var t = w.textContent; w.textContent = '';
      Array.prototype.forEach.call(t, function (ch, i) {
        var s = d.createElement('span'); s.className = 'fx-ch'; s.style.setProperty('--i', i);
        s.textContent = ch === ' ' ? ' ' : ch; w.appendChild(s);
      });
      watch(w);
      // nápis presne na šírku pätičky
      var lastW = 0, inner = function () { var pe = w.parentElement, cs = getComputedStyle(pe); return pe.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight); }, fit = function (force) { var pw = inner(); if (force !== true && pw === lastW && w.style.fontSize) return; lastW = pw; w.style.fontSize = ''; var cw = pw, tw = w.getBoundingClientRect().width; if (tw > 0) w.style.fontSize = (parseFloat(getComputedStyle(w).fontSize) * cw / tw * 0.995).toFixed(1) + 'px'; };
      fit(); if (d.fonts && d.fonts.load) d.fonts.load('600 100px Fraunces').then(function () { fit(true); }, function () {});
      window.addEventListener('load', function () { fit(true); }); window.addEventListener('resize', fit);
      if ('ResizeObserver' in window) new ResizeObserver(fit).observe(w.parentElement);
    });
  })();

  /* ======================= 8. MENU: „PRETÁČANÝ“ TEXT ======================= */
  $$('.nav__link--main').forEach(function (a) {
    if (a.children.length) return;
    var t = a.textContent.trim();
    a.innerHTML = ''; var r = d.createElement('span'); r.className = 'fx-roll'; r.setAttribute('data-t', t);
    var s = d.createElement('span'); s.textContent = t; r.appendChild(s); a.appendChild(r);
  });

  if (reduce) return;   // ďalej už len efekty pre myš

  /* ======================= 9. KRUHOVÉ PREPNUTIE TMAVÉHO REŽIMU ======================= */
  (function () {
    var btn = d.getElementById('theme-toggle'); if (!btn || !d.startViewTransition) return;
    var passing = false;
    btn.addEventListener('click', function (e) {
      if (passing) return;
      e.stopImmediatePropagation(); e.preventDefault();
      var r = btn.getBoundingClientRect(), x = r.left + r.width / 2, y = r.top + r.height / 2;
      var R = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
      var vt = d.startViewTransition(function () { passing = true; btn.click(); passing = false; root.classList.remove('theme-anim'); });
      vt.ready.then(function () {
        root.animate({ clipPath: ['circle(0px at ' + x + 'px ' + y + 'px)', 'circle(' + R + 'px at ' + x + 'px ' + y + 'px)'] },
          { duration: 750, easing: 'cubic-bezier(.77,0,.18,1)', pseudoElement: '::view-transition-new(root)' });
      }).catch(function () {});
    }, true);
  })();

  if (!fine) return;

  /* ======================= 10. KURZOR ======================= */
  (function () {
    if (off('cursor')) return;
    root.classList.add('fx-cursor-on');
    var dot = d.createElement('div'), ring = d.createElement('div'), label = d.createElement('span');
    dot.className = 'fx-cur'; ring.className = 'fx-cur-ring'; label.className = 'fx-cur-ring__label';
    dot.setAttribute('aria-hidden', 'true'); ring.setAttribute('aria-hidden', 'true');
    ring.appendChild(label); body.appendChild(ring); body.appendChild(dot);
    var mx = innerWidth / 2, my = innerHeight / 2, rx = mx, ry = my, shown = false;
    var LABELS = [
      ['.bo-video, [data-bo-video], .bo-videos button', 'Prehrať'],
      ['.bo-gallery__item, a[data-lightbox], .photo-grid a, .gallery a, .mitem img', 'Zväčšiť'],
      ['.path-card, .bo-card, .gcat, .topic-card, .lib-cat', 'Otvoriť']
    ];
    function setState(t) {
      var cls = root.classList;
      cls.remove('fx-cur-link', 'fx-cur-label', 'fx-cur-text');
      if (!t || !t.closest) return;
      if (t.closest('input:not([type=submit]):not([type=checkbox]):not([type=radio]):not([type=file]), textarea, select, [contenteditable]')) { cls.add('fx-cur-text'); return; }
      if (t.closest('.bo-lb, .lightbox, .bo-vm, [role="dialog"]')) { if (t.closest('button, a')) cls.add('fx-cur-link'); return; }
      var custom = t.closest('[data-cursor]');
      if (custom) { label.textContent = custom.getAttribute('data-cursor'); cls.add('fx-cur-label'); return; }
      for (var i = 0; i < LABELS.length; i++) if (t.closest(LABELS[i][0])) { label.textContent = LABELS[i][1]; cls.add('fx-cur-label'); return; }
      if (t.closest('a, button, [role="button"], label, summary, .chip')) cls.add('fx-cur-link');
    }
    d.addEventListener('pointermove', function (e) {
      if (e.pointerType && e.pointerType !== 'mouse') return;
      mx = e.clientX; my = e.clientY;
      if (!shown) { shown = true; rx = mx; ry = my; root.classList.add('fx-cur-on'); }
    }, { passive: true });
    d.addEventListener('pointerover', function (e) { setState(e.target); }, { passive: true });
    d.addEventListener('pointerdown', function () { root.classList.add('fx-cur-down'); });
    d.addEventListener('pointerup', function () { root.classList.remove('fx-cur-down'); });
    d.documentElement.addEventListener('pointerleave', function () { shown = false; root.classList.remove('fx-cur-on'); });
    onFrame(function () {
      rx = lerp(rx, mx, 0.16); ry = lerp(ry, my, 0.16);
      dot.style.transform = 'translate3d(' + mx + 'px,' + my + 'px,0)';
      ring.style.transform = 'translate3d(' + rx.toFixed(1) + 'px,' + ry.toFixed(1) + 'px,0)';
    });
  })();

  /* ======================= 11. MAGNETICKÉ TLAČIDLÁ ======================= */
  $$('.btn, .nav__cta, .nav__login, .theme-toggle, .fx-scrollcue, .nav-toggle').forEach(function (b) {
    if (off('magnet') || b.closest('form .table, .mitem, .design-switch')) return;
    var strength = b.classList.contains('theme-toggle') ? 0.45 : 0.28;
    b.addEventListener('pointermove', function (e) {
      var r = b.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
      b.style.setProperty('--bx', x + 'px'); b.style.setProperty('--by', y + 'px');
      b.style.transition = 'transform .25s cubic-bezier(.16,1,.3,1), background-color .3s, color .35s, border-color .3s';
      b.style.transform = 'translate(' + ((x - r.width / 2) * strength).toFixed(1) + 'px,' + ((y - r.height / 2) * strength).toFixed(1) + 'px)';
    });
    b.addEventListener('pointerenter', function (e) {
      var r = b.getBoundingClientRect();
      b.style.setProperty('--bx', (e.clientX - r.left) + 'px'); b.style.setProperty('--by', (e.clientY - r.top) + 'px');
    });
    b.addEventListener('pointerleave', function (e) {
      var r = b.getBoundingClientRect();
      b.style.setProperty('--bx', (e.clientX - r.left) + 'px'); b.style.setProperty('--by', (e.clientY - r.top) + 'px');
      b.style.transition = 'transform .9s cubic-bezier(.16,1,.3,1), background-color .3s, color .35s, border-color .3s';
      b.style.transform = '';
    });
  });

  /* ======================= 12. 3D NÁKLON KARIET ======================= */
  $$('.path-card, .bo-card, .gcat, .lib-cat, .topic-card').forEach(function (c) {
    if (off('tilt')) return;
    // myš sleduje nehybný rodič (li), nie naklonená karta – na hrane karty preto nič nepreskakuje
    var host = c.parentElement && c.parentElement.tagName === 'LI' ? c.parentElement : c, max = 5, raf = 0, inside = false;
    host.addEventListener('pointermove', function (e) {
      inside = true;
      if (raf) return;
      raf = requestAnimationFrame(function () {
        raf = 0; if (!inside) return;
        var r = host.getBoundingClientRect(), px = clamp((e.clientX - r.left) / r.width, 0, 1), py = clamp((e.clientY - r.top) / r.height, 0, 1);
        c.classList.add('fx-tilting');
        c.style.setProperty('--ry', ((px - 0.5) * max).toFixed(2) + 'deg');
        c.style.setProperty('--rx', ((0.5 - py) * max).toFixed(2) + 'deg');
        c.style.setProperty('--mx', (px * 100).toFixed(1) + '%');
        c.style.setProperty('--my', (py * 100).toFixed(1) + '%');
      });
    });
    host.addEventListener('pointerleave', function () {
      inside = false;
      c.classList.remove('fx-tilting'); c.style.setProperty('--rx', '0deg'); c.style.setProperty('--ry', '0deg');
    });
  });
})();
