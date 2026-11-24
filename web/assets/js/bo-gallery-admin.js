/* ==========================================================================
   Správa galérie (iba administrátor) – načíta sa len na stránkach galérie pre admina.
   - záložky „Nahrať fotografie / Pridať video / Nová kategória“
   - výber „Nová kategória…“ zobrazí polia pre názov
   - pretiahnutie fotografií, náhľady, kliknutím sa zvolí náhľad kategórie
   - nahrávanie po jednej fotografii s priebehom (bez JavaScriptu funguje obyčajný formulár)
   Server všetko znovu overuje (oprávnenie, CSRF, typ súboru).
   ========================================================================== */
(function () {
  'use strict';
  var doc = document;
  var ICON = {
    wait: '<circle cx="12" cy="12" r="8.5" stroke-dasharray="2 3.4"/>',
    busy: '<path d="M12 3.5a8.5 8.5 0 1 1-8.5 8.5"/>',
    ok: '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    err: '<path d="M6.5 6.5l11 11M17.5 6.5l-11 11"/>',
    star: '<path d="m12 3.5 2.6 5.3 5.9.85-4.25 4.15 1 5.85L12 16.9l-5.25 2.75 1-5.85L3.5 9.65l5.9-.85z"/>'
  };
  function svg(name, size) {
    return '<svg class="gsvg" width="' + (size || 16) + '" height="' + (size || 16) + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ICON[name] + '</svg>';
  }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  /* ---------- Záložky ---------- */
  Array.prototype.forEach.call(doc.querySelectorAll('[data-gtabs]'), function (box) {
    var tabs = Array.prototype.slice.call(box.querySelectorAll('[role="tab"]'));
    function select(t, focus) {
      tabs.forEach(function (x) {
        var on = x === t;
        x.setAttribute('aria-selected', on ? 'true' : 'false');
        x.tabIndex = on ? 0 : -1;
        doc.getElementById(x.getAttribute('aria-controls')).hidden = !on;
      });
      if (focus) t.focus();
    }
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { select(t); });
      t.addEventListener('keydown', function (e) {
        var k = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
        if (k) { e.preventDefault(); select(tabs[(i + k + tabs.length) % tabs.length], true); }
      });
    });
    box.classList.add('is-ready');
    select(tabs[0]);
  });

  /* ---------- Nová kategória ---------- */
  Array.prototype.forEach.call(doc.querySelectorAll('select[data-cat-select]'), function (sel) {
    var box = sel.form && sel.form.querySelector('[data-new-fields]');
    if (!box) return;
    function upd() {
      var isNew = sel.value === 'new';
      box.hidden = !isNew;
      Array.prototype.forEach.call(box.querySelectorAll('[data-required-new]'), function (i) { i.required = isNew; });
      if (sel.form._renderPreview) sel.form._renderPreview();
    }
    sel.addEventListener('change', function () { upd(); if (sel.value === 'new') { var f = box.querySelector('input'); if (f) f.focus(); } });
    upd();
  });

  /* ---------- Nahrávanie fotografií ---------- */
  Array.prototype.forEach.call(doc.querySelectorAll('form[data-gallery-upload]'), function (form) {
    var input = form.querySelector('[data-upload-files]');
    var drop = form.querySelector('[data-drop]');
    var count = form.querySelector('[data-drop-count]');
    var preview = form.querySelector('[data-upload-preview]');
    var list = preview && preview.querySelector('.gup__list');
    var progress = form.querySelector('[data-upload-progress]');
    var btn = form.querySelector('button[type="submit"]');
    var catSel = form.querySelector('select[data-cat-select]');
    var cover = -1, urls = [];

    function isNewCat() { return catSel ? catSel.value === 'new' : false; }

    function render() {
      if (!input || !list) return;
      urls.forEach(function (u) { URL.revokeObjectURL(u); });
      urls = [];
      list.innerHTML = '';
      var files = Array.prototype.slice.call(input.files || []);
      preview.hidden = !files.length;
      if (drop) drop.classList.toggle('has-files', files.length > 0);
      if (count) count.textContent = files.length ? (files.length === 1 ? 'Vybraná 1 fotografia' : 'Vybrané fotografie: ' + files.length) : '';
      if (cover >= files.length) cover = -1;
      if (cover < 0 && isNewCat() && files.length) cover = 0;   // nová kategória: náhľad = prvá fotografia (dá sa zmeniť)
      files.forEach(function (f, i) {
        var li = doc.createElement('li');
        var b = doc.createElement('button');
        b.type = 'button';
        b.className = 'gup__item' + (i === cover ? ' is-cover' : '');
        b.setAttribute('aria-pressed', i === cover ? 'true' : 'false');
        b.setAttribute('aria-label', 'Náhľad kategórie: ' + f.name);
        b.title = f.name;
        var u = URL.createObjectURL(f); urls.push(u);
        b.innerHTML = '<img src="' + u + '" alt=""><span class="gup__tag">' + svg('star', 12) + 'Náhľad</span>';
        b.addEventListener('click', function () { cover = cover === i ? -1 : i; render(); });
        li.appendChild(b);
        list.appendChild(li);
      });
    }
    form._renderPreview = render;
    if (input) input.addEventListener('change', function () { cover = -1; render(); });

    if (drop && input) {
      ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); drop.classList.add('is-over'); });
      });
      ['dragleave', 'dragend'].forEach(function (ev) {
        drop.addEventListener(ev, function () { drop.classList.remove('is-over'); });
      });
      drop.addEventListener('drop', function (e) {
        e.preventDefault(); e.stopPropagation();
        drop.classList.remove('is-over');
        var dt = e.dataTransfer;
        if (!dt || !dt.files || !dt.files.length) return;
        try {
          var keep = new DataTransfer();
          Array.prototype.forEach.call(dt.files, function (f) { if (/^image\/(jpeg|png|webp)$/.test(f.type)) keep.items.add(f); });
          input.files = keep.files;
        } catch (err) { input.files = dt.files; }
        cover = -1; render();
      });
    }

    form.addEventListener('submit', function (e) {
      if (!window.fetch || !window.FormData || !input || !input.files || !input.files.length) return;   // obyčajný formulár
      if (!form.checkValidity()) return;
      e.preventDefault();
      var files = Array.prototype.slice.call(input.files);
      var catId = null, done = 0, errors = [], redirect = null;
      btn.disabled = true; btn.classList.add('is-busy');
      progress.hidden = false; progress.innerHTML = '';
      var rows = files.map(function (f) {
        var li = doc.createElement('li');
        li.className = 'is-wait';
        li.innerHTML = '<span class="gprog__ic">' + svg('wait') + '</span><span class="gprog__name">' + esc(f.name) + '</span>';
        progress.appendChild(li);
        return li;
      });
      function state(li, st, msg) {
        li.className = 'is-' + st;
        li.querySelector('.gprog__ic').innerHTML = svg(st);
        if (msg) li.querySelector('.gprog__name').textContent = msg;
      }
      var base = new FormData(form);
      base.delete('files[]');
      base.delete('cover_pick');

      function next(i) {
        if (i >= files.length) return finish();
        var fd = new FormData();
        base.forEach(function (v, k) { fd.append(k, v); });
        if (catId) { fd.set('category', catId); fd.delete('new_title'); fd.delete('new_desc'); }
        fd.append('files[]', files[i]);
        if (i === cover) fd.append('cover_pick', '0');
        state(rows[i], 'busy');
        fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
          .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Server vrátil chybu (' + r.status + ').' }; }); })
          .then(function (res) {
            if (res.category_id) catId = res.category_id;
            if (res.redirect) redirect = res.redirect;
            if (res.ok && res.uploaded) { done++; state(rows[i], 'ok'); }
            else {
              var msg = (res.errors && res.errors.length ? res.errors.join(' ') : res.message) || 'Chyba pri nahrávaní.';
              errors.push(msg); state(rows[i], 'err', msg);
              if (!catId && !res.category_id && i === 0 && !res.errors) return finish();   // napr. chýba názov kategórie
            }
            next(i + 1);
          })
          .catch(function () { errors.push('spojenie zlyhalo'); state(rows[i], 'err', files[i].name + ': spojenie zlyhalo.'); next(i + 1); });
      }
      function finish() {
        btn.disabled = false; btn.classList.remove('is-busy');
        var p = doc.createElement('li'); p.className = 'gprog__sum';
        p.textContent = 'Nahraté ' + done + ' z ' + files.length + (errors.length ? ' – niektoré súbory sa nenahrali.' : '.');
        progress.appendChild(p);
        if (redirect && done) {
          if (!errors.length) { window.location.href = redirect; return; }
          var a = doc.createElement('a'); a.href = redirect; a.className = 'gbtn'; a.textContent = 'Pokračovať do kategórie';
          p.appendChild(a);
        }
      }
      next(0);
    });
  });
})();
