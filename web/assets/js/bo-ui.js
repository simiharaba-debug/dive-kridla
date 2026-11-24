/* ==========================================================================
   Biologická ochrana – UI moduly (bez závislostí)
   - [data-bo-gallery]  tematická galéria s „+N ďalších fotografií“ a lightboxom
   - .bo-video[data-yt]  video z YouTube sa prehrá až po kliknutí v modálnom okne
   - [data-reveal]  jemná animácia pri scrollovaní
   Tmavý režim rieši main.js webu (kľúč dk-theme), tu sa nenastavuje.
   Načítava sa s atribútom defer (includes/header.php).
   ========================================================================== */
(function () {
  'use strict';
  var doc = document, root = doc.documentElement;
  root.classList.add('bo-js');
  var mobile = window.matchMedia('(max-width: 767px)');

  /* ---------- Slovenské skloňovanie: 1 fotografia, 2–4 fotografie, 5+ fotografií ---------- */
  function photos(n) { return n === 1 ? 'ďalšia fotografia' : (n >= 2 && n <= 4 ? 'ďalšie fotografie' : 'ďalších fotografií'); }

  /* ======================= GALÉRIA ======================= */
  function setupGallery(g) {
    var items = Array.prototype.slice.call(g.querySelectorAll('.bo-gallery__item'));
    if (!items.length) return;
    var data = items.map(function (a) {
      var img = a.querySelector('img');
      return { full: a.getAttribute('href') || '', thumb: img ? (img.getAttribute('data-src') || img.currentSrc || img.src) : '',
               alt: img ? img.alt : '', caption: a.getAttribute('data-caption') || (img ? img.alt : '') };
    });
    items.forEach(function (a, i) {
      a.addEventListener('click', function (e) { e.preventDefault(); openLightbox(data, i, a); });
    });
    function layout() {
      var want = parseInt(g.getAttribute(mobile.matches ? 'data-visible-mobile' : 'data-visible'), 10) ||
                 (mobile.matches ? 3 : 4);
      var visible = Math.min(want, items.length);
      var rest = items.length - visible;
      items.forEach(function (a, i) {
        a.hidden = i >= visible;
        var old = a.querySelector('.bo-gallery__more');
        if (old) old.remove();
        a.setAttribute('aria-label', 'Otvoriť fotografiu ' + (i + 1) + ' z ' + items.length);
      });
      if (rest > 0) {
        var last = items[visible - 1], badge = doc.createElement('span');
        badge.className = 'bo-gallery__more';
        badge.innerHTML = '<strong>+' + rest + '</strong><span>' + photos(rest) + '</span>';
        last.appendChild(badge);
        last.setAttribute('aria-label', 'Otvoriť galériu – ' + rest + ' ' + photos(rest));
      }
    }
    layout();
    if (mobile.addEventListener) mobile.addEventListener('change', layout);
  }

  /* ======================= LIGHTBOX ======================= */
  var lb, lbImg, lbCap, lbCount, lbThumbs, lbItems = [], lbIndex = 0, lbOpener = null, touchX = null;

  function buildLightbox() {
    lb = doc.createElement('div');
    lb.className = 'bo-lb';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');
    lb.setAttribute('aria-label', 'Galéria fotografií');
    lb.innerHTML =
      '<div class="bo-lb__bar"><span class="bo-lb__count" aria-live="polite"></span>' +
      '<button type="button" class="bo-lb__btn bo-lb__close" aria-label="Zavrieť galériu">✕</button></div>' +
      '<div class="bo-lb__stage">' +
      '<button type="button" class="bo-lb__btn bo-lb__prev" aria-label="Predchádzajúca fotografia">‹</button>' +
      '<img class="bo-lb__img" alt="">' +
      '<button type="button" class="bo-lb__btn bo-lb__next" aria-label="Ďalšia fotografia">›</button></div>' +
      '<div><div class="bo-lb__cap"></div><div class="bo-lb__thumbs"></div></div>';
    doc.body.appendChild(lb);
    lbImg = lb.querySelector('.bo-lb__img');
    lbCap = lb.querySelector('.bo-lb__cap');
    lbCount = lb.querySelector('.bo-lb__count');
    lbThumbs = lb.querySelector('.bo-lb__thumbs');
    lb.querySelector('.bo-lb__close').addEventListener('click', closeLightbox);
    lb.querySelector('.bo-lb__prev').addEventListener('click', function () { show(lbIndex - 1); });
    lb.querySelector('.bo-lb__next').addEventListener('click', function () { show(lbIndex + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb || e.target.classList.contains('bo-lb__stage')) closeLightbox(); });
    lbImg.addEventListener('load', function () { lbImg.classList.add('is-loaded'); });
    lb.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) {
      if (touchX === null) return;
      var dx = e.changedTouches[0].clientX - touchX; touchX = null;
      if (Math.abs(dx) > 45) show(lbIndex + (dx < 0 ? 1 : -1));
    }, { passive: true });
    doc.addEventListener('keydown', function (e) {
      if (!lb.classList.contains('is-open')) return;
      if (e.key === 'Escape') closeLightbox();
      else if (e.key === 'ArrowRight') show(lbIndex + 1);
      else if (e.key === 'ArrowLeft') show(lbIndex - 1);
      else if (e.key === 'Tab') trapFocus(e);
    });
  }

  function trapFocus(e) {
    var f = Array.prototype.filter.call(lb.querySelectorAll('button'), function (b) { return b.offsetParent !== null; });
    if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (e.shiftKey && doc.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && doc.activeElement === last) { e.preventDefault(); first.focus(); }
  }

  function show(i) {
    var n = lbItems.length;
    lbIndex = (i + n) % n;
    var it = lbItems[lbIndex];
    lbImg.classList.remove('is-loaded');
    lbImg.alt = it.alt || '';
    lbImg.src = it.full;
    if (lbImg.complete && lbImg.naturalWidth) lbImg.classList.add('is-loaded');
    lbCap.textContent = it.caption || '';
    lbCount.textContent = (lbIndex + 1) + ' / ' + n;
    Array.prototype.forEach.call(lbThumbs.children, function (b, k) {
      b.setAttribute('aria-current', k === lbIndex ? 'true' : 'false');
      if (k === lbIndex && b.scrollIntoView) b.scrollIntoView({ block: 'nearest', inline: 'center' });
    });
    [lbIndex + 1, lbIndex - 1].forEach(function (k) { var p = new Image(); p.src = lbItems[(k + n) % n].full; }); // prednačítanie susedov
    var single = n < 2;
    lb.querySelector('.bo-lb__prev').hidden = single;
    lb.querySelector('.bo-lb__next').hidden = single;
  }

  function openLightbox(items, index, opener) {
    if (!lb) buildLightbox();
    lbItems = items; lbOpener = opener || null;
    lbThumbs.innerHTML = '';
    items.forEach(function (it, k) {
      var b = doc.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', 'Fotografia ' + (k + 1));
      var im = doc.createElement('img');
      im.src = it.thumb; im.alt = ''; im.loading = 'lazy';
      b.appendChild(im);
      b.addEventListener('click', function () { show(k); });
      lbThumbs.appendChild(b);
    });
    root.classList.add('bo-noscroll');
    lb.classList.add('is-open');
    show(index);
    lb.querySelector('.bo-lb__close').focus({ preventScroll: true });
  }

  function closeLightbox() {
    lb.classList.remove('is-open');
    root.classList.remove('bo-noscroll');
    if (lbOpener) lbOpener.focus({ preventScroll: true });
  }

  /* ======================= VIDEO V MODÁLNOM OKNE (YouTube) =======================
     Náhľad .bo-video[data-yt] → po kliknutí sa otvorí okno s prehrávačom youtube-nocookie.com.
     Okno sa prispôsobí orientácii videa (na šírku / na výšku), šípky listujú v rámci skupiny [data-bo-videos],
     Esc / ✕ / klik mimo videa okno zavrie a video zastaví (iframe sa odstráni). Kým nikto neklikne, z YouTube sa nič nenačíta. */
  var vm, vmFrame, vmTitle, vmDesc, vmCount, vmPrev, vmNext, vmLink, vmList = [], vmIndex = 0, vmOpener = null, vmTouchX = null;

  function buildVideoModal() {
    vm = doc.createElement('div');
    vm.className = 'bo-vm';
    vm.setAttribute('role', 'dialog');
    vm.setAttribute('aria-modal', 'true');
    vm.setAttribute('aria-labelledby', 'bo-vm-title');
    vm.hidden = true;
    vm.innerHTML =
      '<div class="bo-vm__backdrop" data-vm-close></div>' +
      '<div class="bo-vm__box">' +
        '<div class="bo-vm__bar"><span class="bo-vm__count" aria-live="polite"></span>' +
        '<button type="button" class="bo-vm__btn bo-vm__close" data-vm-close aria-label="Zavrieť video">✕</button></div>' +
        '<div class="bo-vm__stage">' +
          '<button type="button" class="bo-vm__btn bo-vm__prev" aria-label="Predchádzajúce video">‹</button>' +
          '<div class="bo-vm__frame"></div>' +
          '<button type="button" class="bo-vm__btn bo-vm__next" aria-label="Ďalšie video">›</button>' +
        '</div>' +
        '<div class="bo-vm__info"><h2 class="bo-vm__title" id="bo-vm-title"></h2><p class="bo-vm__desc"></p>' +
        '<a class="bo-vm__yt" target="_blank" rel="noopener">Otvoriť na YouTube ↗</a></div>' +
      '</div>';
    doc.body.appendChild(vm);
    vmFrame = vm.querySelector('.bo-vm__frame');
    vmTitle = vm.querySelector('.bo-vm__title');
    vmDesc = vm.querySelector('.bo-vm__desc');
    vmCount = vm.querySelector('.bo-vm__count');
    vmPrev = vm.querySelector('.bo-vm__prev');
    vmNext = vm.querySelector('.bo-vm__next');
    vmLink = vm.querySelector('.bo-vm__yt');
    Array.prototype.forEach.call(vm.querySelectorAll('[data-vm-close]'), function (b) { b.addEventListener('click', closeVideo); });
    vm.querySelector('.bo-vm__stage').addEventListener('click', function (e) { if (e.target === e.currentTarget) closeVideo(); });
    vmPrev.addEventListener('click', function () { showVideo(vmIndex - 1); });
    vmNext.addEventListener('click', function () { showVideo(vmIndex + 1); });
    vm.addEventListener('touchstart', function (e) { vmTouchX = e.touches[0].clientX; }, { passive: true });
    vm.addEventListener('touchend', function (e) {
      if (vmTouchX === null) return;
      var dx = e.changedTouches[0].clientX - vmTouchX; vmTouchX = null;
      if (Math.abs(dx) > 60 && vmList.length > 1) showVideo(vmIndex + (dx < 0 ? 1 : -1));
    }, { passive: true });
    doc.addEventListener('keydown', function (e) {
      if (vm.hidden) return;
      if (e.key === 'Escape') { e.preventDefault(); closeVideo(); }
      else if (e.key === 'ArrowRight' && vmList.length > 1) showVideo(vmIndex + 1);
      else if (e.key === 'ArrowLeft' && vmList.length > 1) showVideo(vmIndex - 1);
      else if (e.key === 'Tab') {
        var f = Array.prototype.filter.call(vm.querySelectorAll('button, a[href], iframe'), function (b) { return b.offsetParent !== null; });
        if (!f.length) return;
        if (e.shiftKey && doc.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
        else if (!e.shiftKey && doc.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
      }
    });
  }

  function videoData(el) {
    return { yt: el.getAttribute('data-yt'), title: el.getAttribute('data-title') || '', desc: el.getAttribute('data-desc') || '',
             w: parseInt(el.getAttribute('data-w'), 10) || 16, h: parseInt(el.getAttribute('data-h'), 10) || 9, el: el };
  }

  function showVideo(i) {
    var n = vmList.length;
    vmIndex = (i + n) % n;
    var v = vmList[vmIndex];
    var portrait = v.h > v.w;
    vm.classList.toggle('bo-vm--portrait', portrait);
    vmFrame.style.setProperty('--ar', (v.w / v.h).toFixed(4));
    vmFrame.innerHTML = '';
    var f = doc.createElement('iframe');
    f.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(v.yt) + '?autoplay=1&rel=0&playsinline=1&modestbranding=1';
    f.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen';
    f.allowFullscreen = true;
    f.referrerPolicy = 'strict-origin-when-cross-origin';
    f.title = v.title || 'Video';
    vmFrame.appendChild(f);
    vmTitle.textContent = v.title;
    vmDesc.textContent = v.desc;
    vmDesc.hidden = !v.desc;
    vmLink.href = 'https://www.youtube.com/watch?v=' + encodeURIComponent(v.yt);
    vmCount.textContent = n > 1 ? (vmIndex + 1) + ' / ' + n : '';
    vmPrev.hidden = vmNext.hidden = n < 2;
  }

  function openVideo(el) {
    if (!vm) buildVideoModal();
    var group = el.closest('[data-bo-videos]');
    var els = group ? Array.prototype.slice.call(group.querySelectorAll('.bo-video[data-yt]')) : [el];
    vmList = els.map(videoData);
    vmOpener = el;
    vm.hidden = false;
    root.classList.add('bo-noscroll');
    requestAnimationFrame(function () { vm.classList.add('is-open'); });
    showVideo(els.indexOf(el));
    vm.querySelector('.bo-vm__close').focus({ preventScroll: true });
  }

  function closeVideo() {
    if (!vm || vm.hidden) return;
    vm.classList.remove('is-open');
    vmFrame.innerHTML = '';              // zastaví prehrávanie
    vm.hidden = true;
    root.classList.remove('bo-noscroll');
    if (vmOpener) vmOpener.focus({ preventScroll: true });
  }

  function setupVideo(el) {
    if (!el.getAttribute('data-yt')) return;
    var img = el.querySelector('img');
    if (img) img.addEventListener('error', function () {   // náhľad hqdefault chýba → menší náhľad
      if (img.src.indexOf('/hqdefault.') > -1) img.src = img.src.replace('/hqdefault.', '/mqdefault.');
    });
    el.addEventListener('click', function (e) { e.preventDefault(); openVideo(el); });
  }

  /* Potvrdenie pred odoslaním (napr. odstránenie videa adminom): <button data-confirm="Otázka?"> */
  function setupConfirm() {
    doc.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-confirm]');
      if (b && !window.confirm(b.getAttribute('data-confirm'))) e.preventDefault();
    });
  }

  /* ======================= ANIMÁCIE PRI SCROLLOVANÍ ======================= */
  function setupReveal() {
    var els = doc.querySelectorAll('[data-reveal]');
    if (!('IntersectionObserver' in window)) { Array.prototype.forEach.call(els, function (e) { e.classList.add('is-visible'); }); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    Array.prototype.forEach.call(els, function (e) { io.observe(e); });
  }

  function init() {
    Array.prototype.forEach.call(doc.querySelectorAll('[data-bo-gallery]'), setupGallery);
    Array.prototype.forEach.call(doc.querySelectorAll('.bo-video'), setupVideo);
    setupConfirm();
    setupReveal();
  }
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', init); else init();
  window.BoUI = { openLightbox: openLightbox, openVideo: openVideo };
})();
