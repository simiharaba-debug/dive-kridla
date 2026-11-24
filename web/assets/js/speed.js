/* Rýchlosť: lazy load fotografií (img[data-src], pripravuje includes/speed.php) a prednačítanie podstránok. */
(function () {
  'use strict';
  var d = document;

  /* ---------- fotografie sa stiahnu až tesne pred zobrazením ---------- */
  function load(img) {
    var src = img.getAttribute('data-src'), set = img.getAttribute('data-srcset');
    if (!src) return;
    if (set) {   // potrebná šírka podľa skutočného rámčeka (object-fit: cover môže fotku zväčšiť)
      var w = Math.max(img.clientWidth, img.clientHeight * (parseFloat(img.getAttribute('data-ar')) || 1));
      img.sizes = Math.max(1, Math.ceil(w)) + 'px';
      img.srcset = set;
    }
    img.src = src;
    img.removeAttribute('data-src'); img.removeAttribute('data-srcset');
  }
  var imgs = Array.prototype.slice.call(d.querySelectorAll('img[data-src]'));
  // poloha sa meria priamo (nie IntersectionObserver – ten nevidí fotky skryté odhaľovacou maskou clip-path)
  var waiting = 0;
  function check() {
    waiting = 0;
    var H = window.innerHeight + 300, rest = [], now = [];
    imgs.forEach(function (i) {
      var r = i.getClientRects().length ? i.getBoundingClientRect() : null;   // null = skrytá (display: none)
      (r && r.top < H && r.bottom > -300 ? now : rest).push(i);
    });
    imgs = rest;
    now.forEach(load);
    if (!imgs.length) { window.removeEventListener('scroll', later); window.removeEventListener('resize', later); }
  }
  function later() { if (!waiting) waiting = setTimeout(check, 60); }
  window.addEventListener('scroll', later, { passive: true });
  window.addEventListener('resize', later);
  window.addEventListener('load', later);
  window.addEventListener('beforeprint', function () { imgs.forEach(load); imgs = []; });
  d.addEventListener('DOMContentLoaded', check);   // po všetkých defer skriptoch (galérie už majú skryté fotky navyše)
  d.addEventListener('click', function () { setTimeout(later, 50); });

  /* ---------- podstránka sa prednačíta pri prejdení myšou / dotyku, klik je potom okamžitý ---------- */
  var c = navigator.connection || {}, done = {};
  if (c.saveData || /2g/.test(c.effectiveType || '')) return;
  var l = d.createElement('link');
  if (!l.relList || !l.relList.supports || !l.relList.supports('prefetch')) return;
  var timer = 0;
  function target(e) {
    var a = e.target.closest && e.target.closest('a[href]');
    if (!a || a.origin !== location.origin || a.target === '_blank') return null;
    if (a.pathname === location.pathname && a.search === location.search) return null;
    if (/\.[a-z0-9]{2,5}$/i.test(a.pathname) || /\/(admin[a-z-]*|ucet|moja-galeria|prihlasenie|registracia|odhlasenie|diagnostika)$/.test(a.pathname)) return null;
    return a.origin + a.pathname + a.search;
  }
  function prefetch(u) {
    if (!u || done[u]) return;
    done[u] = 1;
    var x = d.createElement('link'); x.rel = 'prefetch'; x.href = u; d.head.appendChild(x);
  }
  d.addEventListener('mouseover', function (e) {
    var u = target(e); clearTimeout(timer);
    if (u) timer = setTimeout(function () { prefetch(u); }, 65);
  }, { passive: true });
  d.addEventListener('touchstart', function (e) { prefetch(target(e)); }, { passive: true });
  d.addEventListener('focusin', function (e) { prefetch(target(e)); });
})();
