/* ==========================================================================
   Administrácia – fotografie stránok (admin-fotky). Načítava sa iba na tejto stránke.
   - „Zmeniť“ otvorí výber fotografie ako modálne okno s predvolenou stránkou a miestom
   - záložky Z fotogalérie / Nahrať novú, filter podľa kategórie, hľadanie v popise
   - označenie aktuálnej fotografie, náhľad nahrávanej fotografie
   Server všetko znovu overuje (oprávnenie, CSRF, existencia fotografie, typ súboru).
   ========================================================================== */
(function () {
  'use strict';
  var doc = document, root = doc.documentElement;
  var picker = doc.getElementById('vyber');
  if (!picker) return;
  var $ = function (s, el) { return (el || picker).querySelector(s); };
  var $$ = function (s, el) { return Array.prototype.slice.call((el || picker).querySelectorAll(s)); };

  var selSlug = $('[data-pp-slug]'), selSlot = $('[data-pp-slot]'), selCat = $('[data-pp-cat]'), q = $('[data-pp-q]');
  var title = $('[data-pp-title]'), closeBtn = $('[data-pp-close]'), selInfo = $('[data-pp-sel]'), empty = $('[data-pp-empty]');
  var items = $$('.pp-item'), boxes = $$('[data-pp-catbox]');
  var opener = null;

  /* ---------- záložky ---------- */
  var tabBox = $('[data-gtabs]');
  var tabs = $$('[role="tab"]', tabBox);
  function selectTab(t, focus) {
    tabs.forEach(function (x) {
      var on = x === t;
      x.setAttribute('aria-selected', on ? 'true' : 'false');
      x.tabIndex = on ? 0 : -1;
      doc.getElementById(x.getAttribute('aria-controls')).hidden = !on;
    });
    if (focus) t.focus();
  }
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () { selectTab(t); });
    t.addEventListener('keydown', function (e) {
      var k = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
      if (k) { e.preventDefault(); selectTab(tabs[(i + k + tabs.length) % tabs.length], true); }
    });
  });
  tabBox.classList.add('is-ready');

  /* ---------- stránka a miesto ---------- */
  function slotLabel() { return selSlot.value === 'card' ? 'náhľad na úvodnej stránke' : 'hlavnú fotografiu'; }
  function syncTarget() {
    var opt = selSlug.options[selSlug.selectedIndex];
    var cardOpt = selSlot.querySelector('option[value="card"]');
    var hasCard = opt && opt.getAttribute('data-card') === '1';
    cardOpt.disabled = !hasCard;
    if (!hasCard && selSlot.value === 'card') selSlot.value = 'hero';
    var heroOpt = selSlot.querySelector('option[value="hero"]'), hasHero = !opt || opt.getAttribute('data-hero') !== '0';
    heroOpt.disabled = !hasHero;
    if (!hasHero && selSlot.value === 'hero') selSlot.value = 'card';
    $('[data-pp-slug-copy]').value = selSlug.value;
    $('[data-pp-slot-copy]').value = selSlot.value;
    title.textContent = 'Vybrať ' + slotLabel() + ' – ' + (opt ? opt.textContent : '');
  }
  selSlug.addEventListener('change', function () { syncTarget(); markCurrent(currentFor()); });
  selSlot.addEventListener('change', function () { syncTarget(); markCurrent(currentFor()); });

  /* aktuálna fotografia podľa tlačidla „Zmeniť“ pri danej stránke a mieste */
  function currentFor() {
    var b = doc.querySelector('[data-pp-pick][data-slug="' + selSlug.value + '"][data-slot="' + selSlot.value + '"]');
    return b ? b.getAttribute('data-current') : '';
  }
  function markCurrent(ref) {
    items.forEach(function (it) {
      var r = it.querySelector('input');
      it.classList.toggle('is-current', r.value === ref);
      r.checked = false;
    });
    selInfo.textContent = 'Kliknutím vyberte fotografiu.';
  }

  /* ---------- výber fotografie ---------- */
  items.forEach(function (it) {
    var r = it.querySelector('input');
    r.addEventListener('change', function () {
      if (r.checked) selInfo.textContent = 'Vybraná: ' + (it.getAttribute('title') || '');
    });
    it.addEventListener('dblclick', function () { r.checked = true; $('#pp-form-set').requestSubmit ? $('#pp-form-set').requestSubmit() : $('#pp-form-set').submit(); });
  });
  $('#pp-form-set').addEventListener('submit', function (e) {
    if (!$('#pp-form-set input[name="ref"]:checked')) { e.preventDefault(); selInfo.textContent = 'Najprv kliknite na fotografiu.'; selInfo.classList.add('is-warn'); }
  });

  /* ---------- filter a hľadanie ---------- */
  function filter() {
    var cat = selCat.value, term = (q.value || '').trim().toLowerCase(), any = false;
    boxes.forEach(function (b) {
      var okCat = !cat || b.getAttribute('data-pp-catbox') === cat, n = 0;
      $$('.pp-item', b).forEach(function (it) {
        var show = okCat && (!term || it.getAttribute('data-pp-text').indexOf(term) !== -1);
        it.hidden = !show; if (show) n++;
      });
      b.hidden = n === 0; if (n) any = true;
    });
    empty.hidden = any;
  }
  selCat.addEventListener('change', filter);
  q.addEventListener('input', filter);

  /* ---------- nahratie: nová kategória, náhľad ---------- */
  var catSel = $('[data-gcat-select]'), gnew = $('[data-gnew]');
  function syncNew() { gnew.hidden = catSel.value !== 'new'; gnew.querySelector('input').required = catSel.value === 'new'; }
  catSel.addEventListener('change', syncNew); syncNew();
  var file = $('#pp-file'), prev = $('[data-pp-preview]');
  file.addEventListener('change', function () {
    var f = file.files && file.files[0];
    if (!f || !/^image\//.test(f.type)) { prev.hidden = true; return; }
    var img = prev.querySelector('img');
    if (img.src) URL.revokeObjectURL(img.src);
    img.src = URL.createObjectURL(f); prev.hidden = false;
  });
  $('[data-pp-upload]').addEventListener('submit', function (e) {
    syncTarget();
    var b = e.target.querySelector('button[type="submit"]'); b.disabled = true; b.lastChild.textContent = 'Nahrávam…';
  });

  /* ---------- modálne okno ---------- */
  function open(slug, slot, ref, from) {
    selSlug.value = slug; selSlot.value = slot; syncTarget(); markCurrent(ref);
    selCat.value = ''; q.value = ''; filter(); selectTab(tabs[0]);
    opener = from || null;
    picker.classList.add('is-modal'); root.classList.add('bo-noscroll');
    closeBtn.hidden = false;
    var cur = picker.querySelector('.pp-item.is-current');
    picker.scrollTop = 0;
    if (cur) cur.scrollIntoView({ block: 'center' });
    closeBtn.focus({ preventScroll: true });
  }
  function close() {
    picker.classList.remove('is-modal'); root.classList.remove('bo-noscroll');
    closeBtn.hidden = true;
    if (opener) opener.focus({ preventScroll: true });
  }
  picker.setAttribute('role', 'dialog'); picker.setAttribute('aria-modal', 'false');
  doc.addEventListener('click', function (e) {
    var b = e.target.closest('[data-pp-pick]');
    if (!b) return;
    e.preventDefault();
    picker.setAttribute('aria-modal', 'true');
    open(b.getAttribute('data-slug'), b.getAttribute('data-slot'), b.getAttribute('data-current'), b);
  });
  closeBtn.addEventListener('click', close);
  picker.addEventListener('click', function (e) { if (e.target === picker) close(); });
  doc.addEventListener('keydown', function (e) { if (e.key === 'Escape' && picker.classList.contains('is-modal')) close(); });

  syncTarget(); markCurrent(currentFor());
})();
