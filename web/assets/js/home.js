/* ==========================================================================
   Divé krídla – pohyb nového dizajnu na GSAP + ScrollTrigger + Lenis (assets/js/vendor)
   - Lenis: plynulé posúvanie (len myš, všetky verejné stránky)
   - úvod: odkrytie a parallax fotografie, veta, ktorá sa rozsvecuje pri posúvaní
   - rozťahovacie panely služieb (fungujú aj bez GSAP)
   Pri prefers-reduced-motion sa pohyb nespustí, obsah je vždy viditeľný.
   ========================================================================== */
(function () {
  'use strict';
  var d = document, root = d.documentElement;
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = matchMedia('(hover: hover) and (pointer: fine)').matches;
  var gsap = window.gsap, ST = window.ScrollTrigger;

  /* ---------- panely služieb: aktívny je ten pod kurzorom / s fokusom ---------- */
  var panels = Array.prototype.slice.call(d.querySelectorAll('.xp__item'));
  panels.forEach(function (p) {
    function on() { panels.forEach(function (x) { x.classList.toggle('is-active', x === p); }); }
    if (fine) p.addEventListener('pointerenter', on);
    p.addEventListener('focusin', on);
  });

  if (reduce) return;
  var motion = !!(gsap && ST);   // GSAP sa načítava iba na úvode
  if (motion) gsap.registerPlugin(ST);

  /* ---------- Lenis ---------- */
  if (fine && window.Lenis) {
    root.style.scrollBehavior = 'auto';
    var lenis = new window.Lenis({
      duration: 1.05,
      anchors: { offset: -90 },
      prevent: function (n) { return !!(n.closest && n.closest('.bo-lb, .lightbox, .bo-vm, [role="dialog"], .nav__dropdown, textarea, select, .table-wrap')); },
      // keď je otvorené okno (lightbox, video), stránka sa neposúva
      virtualScroll: function (v) {
        if (!root.classList.contains('bo-noscroll') && d.body.style.overflow !== 'hidden') return true;
        var t = v.event && v.event.target;
        if (t && t.closest && t.closest('.lightbox, .bo-lb') && v.event.cancelable) v.event.preventDefault();
        return false;
      }
    });
    if (motion) {
      lenis.on('scroll', ST.update);
      gsap.ticker.add(function (t) { lenis.raf(t * 1000); });
      gsap.ticker.lagSmoothing(0);
    } else (function raf(t) { lenis.raf(t); requestAnimationFrame(raf); })(performance.now());
  }

  /* ---------- úvod ---------- */
  var hero = d.querySelector('.hx');
  if (!hero || !motion) return;
  var media = hero.querySelector('.hx__move');
  gsap.fromTo(media, { scale: 1.22 }, { scale: 1.06, duration: 2.2, ease: 'expo.out', delay: 0.15 });
  gsap.fromTo(media, { yPercent: 0 }, { yPercent: 5, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
  gsap.from(hero.querySelectorAll('.deck__item'), { y: 60, opacity: 0, duration: 1.1, ease: 'expo.out', stagger: 0.09, delay: 0.45, clearProps: 'transform,opacity' });

  /* ---------- veta sa rozsvecuje po slovách ---------- */
  var say = d.querySelector('.law__say');
  if (say) {
    var text = say.textContent.trim();
    say.textContent = '';
    // celá veta pre čítačky obrazovky (aria-label na <p> nie je povolený), slová nižšie sú iba vizuálne
    var sr = d.createElement('span'); sr.className = 'sr-only'; sr.textContent = text; say.appendChild(sr);
    var words = [];
    text.split(/\s+/).forEach(function (w, i) {
      if (i) say.appendChild(d.createTextNode(' '));
      var s = d.createElement('span'); s.setAttribute('aria-hidden', 'true'); s.textContent = w; say.appendChild(s); words.push(s);
    });
    // Stlmenie (0.22) sa nastaví až keď je veta tesne pod okrajom obrazovky – kým je ďaleko, text má plný kontrast
    // (prístupnosť / Lighthouse), efekt rozsvecovania pri posúvaní zostáva rovnaký.
    gsap.fromTo(words, { opacity: 0.22 }, { opacity: 1, stagger: 0.1, ease: 'none', immediateRender: false,
      scrollTrigger: { trigger: say, start: 'top 82%', end: 'bottom 50%', scrub: 0.4 } });
    ST.create({ trigger: say, start: 'top bottom+=240',
      onEnter: function () { gsap.set(words, { opacity: 0.22 }); },
      onLeaveBack: function () { gsap.set(words, { opacity: 1 }); } });
  }
  var lawImg = d.querySelector('.law__photo img');
  if (lawImg) gsap.fromTo(lawImg, { yPercent: -6 }, { yPercent: 6, ease: 'none', scrollTrigger: { trigger: '.law', start: 'top bottom', end: 'bottom top', scrub: true } });
})();
