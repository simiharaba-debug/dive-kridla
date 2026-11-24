/* Skripty webu (bez knižníc): téma, menu, toasty, formuláre, galéria, lightbox, správa médií. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var root = document.documentElement;

  /* ===== téma (svetlá / tmavá) ===== */
  var themeBtn = $('#theme-toggle');
  function applyTheme(t, save) {
    root.classList.add('theme-anim');
    root.setAttribute('data-theme', t);
    var m = $('meta[name="theme-color"]'); if (m) { if (!m.dataset.light) m.dataset.light = m.content; m.setAttribute('content', t === 'dark' ? (m.dataset.dark || '#3b3329') : m.dataset.light); }
    if (themeBtn) themeBtn.setAttribute('aria-pressed', t === 'dark');
    if (save) { try { localStorage.setItem('dk-theme', t); } catch (e) {} }
    setTimeout(function () { root.classList.remove('theme-anim'); }, 450);
  }
  if (themeBtn) {
    themeBtn.setAttribute('aria-pressed', root.getAttribute('data-theme') === 'dark');
    themeBtn.addEventListener('click', function () {
      applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true);
    });
  }
  (function () {
    var m = $('meta[name="theme-color"]'); if (m) { m.dataset.light = m.content; if (root.getAttribute('data-theme') === 'dark') m.setAttribute('content', m.dataset.dark || '#3b3329'); }
    if (window.matchMedia) {
      matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
        var saved = null; try { saved = localStorage.getItem('dk-theme'); } catch (x) {}
        var def = root.getAttribute('data-theme-default');   // predvolený režim z administrácie
        if (!saved && def !== 'light' && def !== 'dark') applyTheme(e.matches ? 'dark' : 'light', false);
      });
    }
  })();

  /* ===== toasty ===== */
  var toastBox = $('#toasts');
  function toast(type, msg, ms) {
    if (!toastBox) return;
    var t = document.createElement('div');
    t.className = 'toast toast--' + type;
    t.setAttribute('role', type === 'error' ? 'alert' : 'status');
    var p = document.createElement('p'); p.textContent = msg;
    var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Zavrieť oznámenie'); b.textContent = '×';
    t.appendChild(p); t.appendChild(b); toastBox.appendChild(t);
    var close = function () { if (t.classList.contains('is-out')) return; t.classList.add('is-out'); setTimeout(function () { t.remove(); }, 260); };
    b.addEventListener('click', close);
    setTimeout(close, ms || (type === 'error' ? 7000 : 4500));
  }
  window.dkToast = toast;
  if (toastBox && toastBox.dataset.flash) {
    try { JSON.parse(toastBox.dataset.flash).forEach(function (f) { toast(f.type, f.msg); }); } catch (e) {}
  }

  /* ===== oznámenie nad hlavičkou (zapína administrátor) – skrytie si zapamätá prehliadač ===== */
  var banner = $('.site-banner');
  if (banner) {
    var bk = 'dk-banner-' + banner.getAttribute('data-banner');
    try { if (localStorage.getItem(bk)) banner.hidden = true; } catch (e) {}
    var bx = $('.site-banner__x', banner);
    if (bx) bx.addEventListener('click', function () { banner.hidden = true; try { localStorage.setItem(bk, '1'); } catch (e) {} });
  }

  /* ===== menu a rozbaľovacie ponuky ===== */
  var toggle = $('.nav-toggle'), nav = $('#menu');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open);
      toggle.setAttribute('aria-label', open ? 'Zavrieť menu' : 'Otvoriť menu');
    });
  }
  var menus = $$('.nav__more');
  function closeMenus(except) {
    menus.forEach(function (m) { if (m !== except) { m.classList.remove('is-open'); var b = $('.nav__more-btn', m); if (b) b.setAttribute('aria-expanded', 'false'); } });
  }
  menus.forEach(function (m) {
    var b = $('.nav__more-btn', m);
    b.addEventListener('click', function (e) {
      e.stopPropagation(); closeMenus(m);
      var open = m.classList.toggle('is-open'); b.setAttribute('aria-expanded', open);
    });
  });
  document.addEventListener('click', function (e) { if (!e.target.closest('.nav__more')) closeMenus(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenus(); });

  /* ===== animácia pri zobrazení ===== */
  var items = $$('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -60px 0px' });
    items.forEach(function (el) { io.observe(el); });
  } else { items.forEach(function (el) { el.classList.add('is-in'); }); }

  /* ===== úvodná galéria: filter podľa kategórie a „zobraziť ďalšie“ ===== */
  var gallery = $('#gallery');
  if (gallery) {
    var PAGE = 12, limit = PAGE, filter = 'all';
    var cells = Array.prototype.slice.call(gallery.children);
    var moreBtn = $('#gallery-more');
    var chips = $$('[data-filter]');
    var render = function () {
      var shown = 0, total = 0;
      cells.forEach(function (li) {
        var match = filter === 'all' || li.getAttribute('data-cat') === filter;
        if (match) total++;
        var visible = match && shown < limit;
        if (visible) shown++;
        li.hidden = !visible;
      });
      moreBtn.hidden = total <= limit;
      moreBtn.textContent = 'Zobraziť ďalšie (' + (total - Math.min(limit, total)) + ')';
    };
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        filter = c.getAttribute('data-filter'); limit = PAGE;
        chips.forEach(function (x) { var on = x === c; x.classList.toggle('is-on', on); x.setAttribute('aria-pressed', on); });
        render();
      });
    });
    moreBtn.addEventListener('click', function () { limit += PAGE; render(); });
    render();
  }

  /* ===== lightbox (fotografie s navigáciou; zoznam tvoria iba viditeľné položky) ===== */
  var lb = $('#lightbox');
  if (lb) {
    var lbImg = $('img', lb), lbCap = $('figcaption', lb);
    var list = [], idx = 0, lastFocus = null;
    var show = function () {
      var a = list[idx];
      lbImg.src = a.getAttribute('href');
      lbImg.alt = (a.querySelector('img') || {}).alt || '';
      lbCap.textContent = a.getAttribute('data-caption') || '';
      var single = list.length < 2;
      $('.lightbox__prev', lb).hidden = single; $('.lightbox__next', lb).hidden = single;
    };
    var close = function () { lb.hidden = true; lbImg.removeAttribute('src'); document.body.style.overflow = ''; if (lastFocus) lastFocus.focus(); };
    var step = function (d) { idx = (idx + d + list.length) % list.length; show(); };
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[data-lightbox]');
      if (!a) return;
      e.preventDefault();
      var group = a.getAttribute('data-lightbox');
      list = $$('a[data-lightbox="' + group + '"]').filter(function (x) { return !x.closest('[hidden]'); });
      idx = Math.max(0, list.indexOf(a)); lastFocus = a;
      lb.hidden = false; document.body.style.overflow = 'hidden';
      show(); $('.lightbox__close', lb).focus();
    });
    $('.lightbox__close', lb).addEventListener('click', close);
    $('.lightbox__prev', lb).addEventListener('click', function () { step(-1); });
    $('.lightbox__next', lb).addEventListener('click', function () { step(1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft' && list.length > 1) step(-1);
      else if (e.key === 'ArrowRight' && list.length > 1) step(1);
    });
    var tx = null;   // swipe na dotykových zariadeniach
    lb.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) {
      if (tx === null || list.length < 2) return;
      var dx = e.changedTouches[0].clientX - tx; tx = null;
      if (Math.abs(dx) > 50) step(dx > 0 ? -1 : 1);
    });
  }

  /* ===== formuláre: okamžitá validácia, heslo, sila hesla, načítavanie ===== */
  function fieldBox(el) { return el.closest('.field') || el.closest('label') || el.parentNode; }
  function msgEl(el) {
    var box = fieldBox(el), m = $('.field-msg', box);
    if (!m) { m = document.createElement('p'); m.className = 'field-msg'; box.appendChild(m); }
    return m;
  }
  function errorFor(el) {
    var v = el.validity, val = el.value;
    if (el.type === 'checkbox') return '';
    if (v.valueMissing) return 'Toto pole je povinné.';
    if (el.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) return 'Zadajte platný e-mail (napr. meno@domena.sk).';
    if (v.tooShort) return 'Zadajte aspoň ' + el.minLength + ' znakov.';
    if (v.patternMismatch) return el.name === 'username' ? 'Povolené sú písmená, čísla, medzera a znaky _ . - (3 – 30 znakov).' : 'Nesprávny formát.';
    if (el.dataset.policy && val && (!/\p{L}/u.test(val) || !/\d/.test(val))) return 'Heslo musí obsahovať aspoň jedno písmeno a jedno číslo.';
    if (el.dataset.match) { var o = document.getElementById(el.dataset.match); if (o && val !== o.value) return 'Heslá sa nezhodujú.'; }
    return '';
  }
  function check(el, final) {
    var err = errorFor(el), box = fieldBox(el), m = msgEl(el);
    if (!el.dataset.hint) el.dataset.hint = (m.textContent || '').trim();
    if (el.dataset.serverErr === '1' && !final && el.dataset.touched !== '1') return;
    el.dataset.serverErr = '0';
    box.classList.toggle('has-error', !!err);
    box.classList.toggle('is-valid', !err && !!el.value);
    if (err) { el.setAttribute('aria-invalid', 'true'); m.textContent = err; m.classList.add('is-error'); }
    else { el.removeAttribute('aria-invalid'); m.textContent = el.dataset.hint || ''; m.classList.remove('is-error'); }
    return !err;
  }
  function strengthOf(p) {
    var s = 0;
    if (p.length >= 10) s++;
    if (p.length >= 14) s++;
    if (/\p{L}/u.test(p) && /\d/.test(p)) s++;
    if (/[^\p{L}\d]/u.test(p) && /[\p{Lu}]/u.test(p)) s++;
    return p ? Math.max(1, Math.min(4, s)) : 0;
  }
  $$('form[data-validate]').forEach(function (form) {
    var controls = $$('input:not([type=hidden]):not([type=checkbox]), textarea, select', form).filter(function (c) { return !c.closest('.hp'); });
    controls.forEach(function (c) {
      var box = fieldBox(c); if (box.classList.contains('has-error')) c.dataset.serverErr = '1';
      c.addEventListener('blur', function () { c.dataset.touched = '1'; check(c); });
      c.addEventListener('input', function () {
        c.dataset.touched = '1'; c.dataset.serverErr = '0';
        if (box.classList.contains('has-error') || c.dataset.live === '1') check(c);
        if (c.dataset.policy) {
          var bar = $('[data-strength-for="' + c.id + '"]'); if (bar) bar.dataset.level = strengthOf(c.value), bar.firstElementChild.style.width = (strengthOf(c.value) * 25) + '%';
        }
        if (c.id === 'f-password') { var c2 = $('#f-password2'); if (c2 && c2.value) check(c2); }
      });
      c.addEventListener('blur', function () { c.dataset.live = '1'; });
    });
    form.addEventListener('submit', function (e) {
      var bad = controls.filter(function (c) { c.dataset.serverErr = '0'; return !check(c, true); });
      if (bad.length) { e.preventDefault(); bad[0].focus(); toast('error', 'Skontrolujte prosím vyznačené polia.'); return; }
      var btn = $('button[type=submit]', form);
      if (form.hasAttribute('data-ajax')) {
        e.preventDefault();
        if (btn.classList.contains('is-loading')) return;
        btn.classList.add('is-loading');
        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }, credentials: 'same-origin' })
          .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Server vrátil neočakávanú odpoveď. Skúste to znova.' }; }); })
          .then(function (j) {
            toast(j.ok ? 'success' : 'error', j.message || (j.ok ? 'Odoslané.' : 'Nepodarilo sa odoslať.'), j.ok ? undefined : 8000);
            var st = $('.form-status', form);
            if (!st) { st = document.createElement('p'); st.className = 'form-status'; st.setAttribute('role', 'status'); form.appendChild(st); }
            st.textContent = j.message || ''; st.classList.toggle('is-error', !j.ok);
            if (j.errors) {
              var first = null;
              Object.keys(j.errors).forEach(function (k) {
                var c = form.querySelector('[name="' + k + '"]'); if (!c) return;
                var box = fieldBox(c), m = msgEl(c);
                box.classList.add('has-error'); box.classList.remove('is-valid'); c.setAttribute('aria-invalid', 'true');
                m.textContent = j.errors[k]; m.classList.add('is-error'); c.dataset.serverErr = '1'; c.dataset.touched = '0';
                if (!first) first = c;
              });
              if (first) first.focus();
            }
            if (j.ok) { form.reset(); controls.forEach(function (c) { fieldBox(c).classList.remove('is-valid'); }); }
          })
          .catch(function () { toast('error', 'Spojenie so serverom zlyhalo. Skúste to znova.'); })
          .then(function () { btn.classList.remove('is-loading'); });
      } else if (btn) { btn.classList.add('is-loading'); }
    });
  });
  window.addEventListener('pageshow', function () { $$('.btn.is-loading').forEach(function (b) { if (!b.closest('[data-ajax]')) b.classList.remove('is-loading'); }); });
  $$('.pw__toggle').forEach(function (b) {
    b.addEventListener('click', function () {
      var inp = $('input', b.parentNode), show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      b.setAttribute('aria-pressed', show); b.setAttribute('aria-label', show ? 'Skryť heslo' : 'Zobraziť heslo');
    });
  });

  /* ===== fotogaléria: filter fotografie / videá ===== */
  var grid = $('#mgrid');
  var csrf = document.body.getAttribute('data-csrf') || '';
  var curFilter = 'all';
  var isPrivate = !!(grid && grid.dataset.private === '1');
  var API = {
    upload: (grid && grid.dataset.apiUpload) || 'api/media-upload.php',
    update: (grid && grid.dataset.apiUpdate) || 'api/media-update.php',
    del: (grid && grid.dataset.apiDelete) || 'api/media-delete.php'
  };
  var LB_GROUP = isPrivate ? 'private' : 'gal';
  function fmtDate(iso) { var d = new Date(iso); return isNaN(d) ? '' : d.getDate() + '. ' + (d.getMonth() + 1) + '. ' + d.getFullYear() + ' ' + d.getHours() + ':' + ('0' + d.getMinutes()).slice(-2); }
  function sortGrid(dir) {
    var items = $$('.mitem', grid);
    items.sort(function (a, b) { var x = a.dataset.created || '', y = b.dataset.created || ''; if (x === y) return (+b.dataset.id - +a.dataset.id) * (dir === 'old' ? -1 : 1); return (x < y ? 1 : -1) * (dir === 'old' ? -1 : 1); });
    items.forEach(function (li) { grid.appendChild(li); });
  }
  function updateCounts() {
    var all = $$('.mitem', grid);
    var n = { all: all.length, image: all.filter(function (x) { return x.dataset.kind === 'image'; }).length };
    n.video = n.all - n.image;
    ['all', 'image', 'video'].forEach(function (k) { var c = $('#cnt-' + k); if (c) c.textContent = n[k]; });
  }
  function applyFilter() {
    var shown = 0;
    $$('.mitem', grid).forEach(function (li) {
      var ok = curFilter === 'all' || li.dataset.kind === curFilter;
      li.hidden = !ok; if (ok) shown++;
      if (!ok) { var v = $('video', li); if (v) v.pause(); }
    });
    var em = $('#mgrid-empty'); if (em) { em.hidden = shown > 0; if (!shown) em.textContent = $$('.mitem', grid).length ? 'V tejto kategórii zatiaľ nie je žiadny súbor.' : (isPrivate ? 'Vaša súkromná galéria je zatiaľ prázdna. Nahrajte prvé fotografie alebo videá vyššie.' : 'V tejto kategórii zatiaľ nie je žiadny súbor.'); }
  }
  if (grid) {
    $$('[data-mfilter]').forEach(function (c) {
      c.addEventListener('click', function () {
        curFilter = c.dataset.mfilter;
        $$('[data-mfilter]').forEach(function (x) { var on = x === c; x.classList.toggle('is-on', on); x.setAttribute('aria-pressed', on); });
        applyFilter();
      });
    });
    var sortSel = $('#msort');
    if (sortSel) sortSel.addEventListener('change', function () { sortGrid(sortSel.value); });
  }

  /* ===== správa médií (iba administrátor; server všetko znovu overuje) ===== */
  var dz = $('#dropzone');
  function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }
  function iconBtn(act, label, danger, path) {
    var b = el('button', 'icon-btn' + (danger ? ' icon-btn--danger' : '')); b.type = 'button'; b.dataset.act = act; b.setAttribute('aria-label', label);
    b.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + path + '</svg>';
    return b;
  }
  function buildItem(it) {
    var li = el('li', 'mitem mitem--' + it.kind + ' is-new');
    li.dataset.kind = it.kind; li.dataset.id = it.id; li.dataset.title = it.title; li.dataset.desc = it.description;
    if (it.created_at) li.dataset.created = it.created_at;
    var cap = it.title + (it.description ? ' – ' + it.description : '');
    var media;
    if (it.kind === 'image') {
      media = el('a', 'mitem__media'); media.href = it.path; media.dataset.lightbox = LB_GROUP; media.dataset.caption = cap;
      var img = el('img'); img.src = it.thumb; img.alt = it.title || 'Fotografia'; img.decoding = 'async'; media.appendChild(img);
    } else {
      media = el('div', 'mitem__media');
      var v = el('video'); v.controls = true; v.preload = 'none'; v.setAttribute('playsinline', ''); v.poster = it.thumb;
      var s = el('source'); s.src = it.path; s.type = it.mime || (/\.webm$/.test(it.path) ? 'video/webm' : 'video/mp4'); v.appendChild(s); media.appendChild(v);
    }
    li.appendChild(media);
    var c = el('div', 'mitem__cap'); c.appendChild(el('strong', null, it.title || (it.kind === 'video' ? 'Video' : 'Fotografia')));
    if (it.description) c.appendChild(el('span', null, it.description));
    if (it.created_at) { var tm = el('time', 'mitem__date', 'Nahraté ' + fmtDate(it.created_at)); tm.setAttribute('datetime', it.created_at); c.appendChild(tm); }
    li.appendChild(c);
    var act = el('div', 'mitem__actions');
    act.appendChild(iconBtn('edit', 'Upraviť údaje', false, '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>'));
    act.appendChild(iconBtn('delete', 'Zmazať', true, '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/>'));
    li.appendChild(act);
    return li;
  }
  function refreshItem(li, it) {
    li.dataset.title = it.title; li.dataset.desc = it.description;
    var cap = $('.mitem__cap', li), tmOld = $('.mitem__date', cap); cap.textContent = '';
    cap.appendChild(el('strong', null, it.title || (it.kind === 'video' ? 'Video' : 'Fotografia')));
    if (it.description) cap.appendChild(el('span', null, it.description));
    if (tmOld) cap.appendChild(tmOld);
    var a = $('a[data-lightbox]', li);
    if (a) { a.dataset.caption = it.title + (it.description ? ' – ' + it.description : ''); var im = $('img', a); if (im) im.alt = it.title || 'Fotografia'; }
  }
  function api(url, data) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'Accept': 'application/json' }, body: JSON.stringify(data) })
      .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Neočakávaná odpoveď servera.' }; }); });
  }

  if (dz && grid) {
    var input = $('#file-input'), queue = $('#queue'), actions = $('#queue-actions');
    var maxImg = +dz.dataset.maxImage, maxVid = +dz.dataset.maxVideo;
    var entries = [], busy = false;
    var fmt = function (b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' kB'; };
    function kindOf(f) {
      if (/^image\/(jpeg|png|webp)$/.test(f.type) || /\.(jpe?g|png|webp)$/i.test(f.name)) return 'image';
      if (/^video\/(mp4|webm)$/.test(f.type) || /\.(mp4|webm)$/i.test(f.name)) return 'video';
      return null;
    }
    function syncActions() {
      var pending = entries.filter(function (e) { return e.state === 'ready'; }).length;
      actions.hidden = entries.length === 0;
      $('#upload-all').disabled = busy || pending === 0;
      $('#clear-queue').disabled = busy;
    }
    function addFiles(files) {
      Array.prototype.forEach.call(files, function (f) {
        var kind = kindOf(f), err = '';
        if (!kind) err = 'Nepodporovaný formát (povolené: JPG, PNG, WebP, MP4, WebM).';
        else if (kind === 'image' && f.size > maxImg) err = 'Fotografia je väčšia ako ' + fmt(maxImg) + '.';
        else if (kind === 'video' && f.size > maxVid) err = 'Video je väčšie ako ' + fmt(maxVid) + '.';
        var row = el('li', 'qitem' + (err ? ' is-error' : ''));
        var prev = el('div', 'qitem__prev');
        if (kind && !err) {
          var url = URL.createObjectURL(f);
          if (kind === 'image') { var im = el('img'); im.src = url; im.alt = 'Náhľad ' + f.name; prev.appendChild(im); }
          else { var vv = el('video'); vv.src = url + '#t=0.5'; vv.muted = true; vv.preload = 'metadata'; vv.setAttribute('playsinline', ''); prev.appendChild(vv); }
          prev.appendChild(el('span', 'qitem__badge', kind === 'image' ? 'Foto' : 'Video'));
        }
        var fields = el('div', 'qitem__fields');
        fields.appendChild(el('div', 'qitem__file', f.name + ' · ' + fmt(f.size)));
        var title = el('input'); title.placeholder = 'Názov (povinný)'; title.maxLength = 120; title.value = f.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ');
        title.setAttribute('aria-label', 'Názov súboru ' + f.name);
        var desc = el('textarea'); desc.placeholder = 'Popis (voliteľný)'; desc.rows = 1; desc.maxLength = 1000; desc.setAttribute('aria-label', 'Popis súboru ' + f.name);
        var prog = el('div', 'progress'); prog.appendChild(el('span'));
        var status = el('div', 'qitem__status', err || 'Pripravené na nahratie');
        if (!err) { fields.appendChild(title); fields.appendChild(desc); }
        fields.appendChild(prog); fields.appendChild(status);
        var rm = el('button', 'qitem__remove', '×'); rm.type = 'button'; rm.setAttribute('aria-label', 'Odstrániť zo zoznamu');
        row.appendChild(prev); row.appendChild(fields); row.appendChild(rm);
        queue.appendChild(row);
        var entry = { file: f, kind: kind, row: row, title: title, desc: desc, bar: prog.firstChild, status: status, rm: rm, state: err ? 'error' : 'ready' };
        rm.addEventListener('click', function () { entries.splice(entries.indexOf(entry), 1); row.remove(); syncActions(); });
        entries.push(entry);
      });
      syncActions();
    }
    function uploadOne(en) {
      return new Promise(function (resolve) {
        if (!en.title.value.trim()) { en.state = 'error'; en.row.classList.add('is-error'); en.status.textContent = 'Zadajte názov.'; en.title.focus(); return resolve(false); }
        en.state = 'uploading'; en.rm.disabled = true; en.title.disabled = en.desc.disabled = true;
        en.row.classList.remove('is-error'); en.status.textContent = 'Nahrávam… 0 %';
        var fd = new FormData(); fd.append('file', en.file); fd.append('title', en.title.value.trim()); fd.append('description', en.desc.value.trim());
        var xhr = new XMLHttpRequest();
        xhr.open('POST', API.upload); xhr.setRequestHeader('X-CSRF-Token', csrf); xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.onprogress = function (e) {
          if (!e.lengthComputable) return;
          var p = Math.round(e.loaded / e.total * 100); en.bar.style.width = p + '%';
          en.status.textContent = p < 100 ? 'Nahrávam… ' + p + ' %' : 'Spracúvam na serveri…';
        };
        var fail = function (msg) {
          en.state = 'error'; en.row.classList.add('is-error'); en.status.textContent = msg; en.rm.disabled = false; en.title.disabled = en.desc.disabled = false; en.bar.style.width = '100%';
          resolve(false);
        };
        xhr.onerror = function () { fail('Spojenie so serverom zlyhalo.'); };
        xhr.onload = function () {
          var j = null; try { j = JSON.parse(xhr.responseText); } catch (e) {}
          if (xhr.status === 201 && j && j.ok) {
            en.state = 'done'; en.row.classList.add('is-done'); en.bar.style.width = '100%'; en.status.textContent = isPrivate ? '✓ Nahrané do súkromnej galérie' : '✓ Nahrané a zverejnené';
            var li = buildItem(j.item); var so = $('#msort');
            if (so && so.value === 'old') grid.appendChild(li); else grid.insertBefore(li, grid.firstChild); updateCounts(); applyFilter();
            setTimeout(function () { var i = entries.indexOf(en); if (i > -1) entries.splice(i, 1); en.row.remove(); syncActions(); }, 2500);
            resolve(true);
          } else if (xhr.status === 413 || (!j && xhr.status === 200)) fail('Súbor je väčší ako povoľuje server (post_max_size).');
          else fail((j && j.error) || 'Nahrávanie zlyhalo (' + xhr.status + ').');
        };
        xhr.send(fd);
      });
    }
    $('#upload-all').addEventListener('click', function () {
      if (busy) return; busy = true; var btn = this; btn.classList.add('is-loading'); syncActions();
      var todo = entries.filter(function (e) { return e.state === 'ready' || e.state === 'error' && e.kind; }), ok = 0, bad = 0;
      todo.reduce(function (p, en) { return p.then(function () { return uploadOne(en); }).then(function (r) { r ? ok++ : bad++; }); }, Promise.resolve()).then(function () {
        busy = false; btn.classList.remove('is-loading'); syncActions();
        if (ok) toast('success', isPrivate ? (ok === 1 ? 'Súbor bol uložený do súkromnej galérie.' : ok + ' súborov bolo uložených do súkromnej galérie.') : (ok === 1 ? 'Súbor bol nahraný a zverejnený.' : ok + ' súborov bolo nahraných a zverejnených.'));
        if (bad) toast('error', bad === 1 ? 'Jeden súbor sa nepodarilo nahrať.' : bad + ' súborov sa nepodarilo nahrať.');
      });
    });
    $('#clear-queue').addEventListener('click', function () { entries.slice().forEach(function (e) { if (e.state !== 'uploading') { e.row.remove(); entries.splice(entries.indexOf(e), 1); } }); syncActions(); });
    dz.addEventListener('click', function () { input.click(); });
    dz.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
    input.addEventListener('change', function () { addFiles(input.files); input.value = ''; });
    ['dragenter', 'dragover'].forEach(function (ev) { dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.remove('is-over'); }); });
    dz.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) addFiles(e.dataTransfer.files); });
    window.addEventListener('dragover', function (e) { if (!e.target.closest('#dropzone')) e.preventDefault(); });
    window.addEventListener('drop', function (e) { if (!e.target.closest('#dropzone')) e.preventDefault(); });

    /* úprava a mazanie nahratých položiek */
    var editDlg = $('#edit-dialog'), delDlg = $('#delete-dialog'), current = null;
    $$('[data-close]').forEach(function (b) { b.addEventListener('click', function () { b.closest('dialog').close(); }); });
    $$('dialog.dialog').forEach(function (d) { d.addEventListener('click', function (e) { if (e.target === d) d.close(); }); });
    grid.addEventListener('click', function (e) {
      var b = e.target.closest('[data-act]'); if (!b) return;
      current = b.closest('.mitem');
      if (b.dataset.act === 'edit') {
        $('#edit-title').value = current.dataset.title; $('#edit-desc').value = current.dataset.desc; $('.field-msg', editDlg).textContent = '';
        editDlg.showModal(); $('#edit-title').focus();
      } else { $('#del-name').textContent = current.dataset.title || '(bez názvu)'; delDlg.showModal(); }
    });
    $('#edit-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var t = $('#edit-title').value.trim(), btn = $('button[type=submit]', editDlg);
      if (!t) { $('.field-msg', editDlg).textContent = 'Názov nesmie byť prázdny.'; $('.field-msg', editDlg).classList.add('is-error'); return; }
      btn.classList.add('is-loading');
      api(API.update, { id: +current.dataset.id, title: t, description: $('#edit-desc').value.trim() }).then(function (j) {
        btn.classList.remove('is-loading');
        if (j.ok) { refreshItem(current, j.item); editDlg.close(); toast('success', 'Údaje boli uložené.'); }
        else toast('error', j.error || 'Uloženie zlyhalo.');
      }).catch(function () { btn.classList.remove('is-loading'); toast('error', 'Spojenie so serverom zlyhalo.'); });
    });
    $('#delete-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = $('button[type=submit]', delDlg), li = current; btn.classList.add('is-loading');
      api(API.del, { id: +li.dataset.id }).then(function (j) {
        btn.classList.remove('is-loading');
        if (j.ok) {
          delDlg.close(); li.classList.add('is-removing');
          setTimeout(function () { li.remove(); updateCounts(); applyFilter(); }, 280);
          toast('success', 'Súbor bol zmazaný.');
        } else toast('error', j.error || 'Zmazanie zlyhalo.');
      }).catch(function () { btn.classList.remove('is-loading'); toast('error', 'Spojenie so serverom zlyhalo.'); });
    });
  }
})();
