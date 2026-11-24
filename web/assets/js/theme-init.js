/* Spúšťa sa synchronne v <head> ešte pred vykreslením stránky, aby pri načítaní nebolo „blikanie“ témy.
   Poradie: uložená voľba návštevníka (localStorage) → predvolený režim z administrácie (data-theme-default na <html>:
   system | light | dark) → nastavenie systému (prefers-color-scheme) → svetlý režim.
   V administrácii navyše obnoví zbalený bočný panel (dk-adm-collapsed). */
(function () {
  var d = document.documentElement, t = null, def = d.getAttribute('data-theme-default');
  d.classList.add('js');
  try { t = localStorage.getItem('dk-theme'); } catch (e) {}
  if (t !== 'dark' && t !== 'light') {
    if (def === 'dark' || def === 'light') t = def;
    else t = (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
  }
  d.setAttribute('data-theme', t);
  /* Verejný web: po otvorení a obnovení začína stránka úplne hore (inak si prehliadač zapamätá posun a odreže oznámenie).
     Návrat tlačidlom Späť pozíciu zachová, odkazy s kotvou (#…) fungujú ďalej. */
  if (!d.classList.contains('adm') && !location.hash && 'scrollRestoration' in history) {
    var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
    var top = function (max) { if (window.pageYOffset > 0 && window.pageYOffset < max) window.scrollTo({ top: 0, behavior: 'instant' }); };
    var touched = false;   // návštevník už sám posúva – nezasahovať
    ['wheel', 'touchstart', 'keydown', 'pointerdown'].forEach(function (n) { window.addEventListener(n, function () { touched = true; }, { passive: true, once: true }); });
    var back = nav && nav.type === 'back_forward';
    if (!back) {
      history.scrollRestoration = 'manual';
      window.addEventListener('pagehide', function () { history.scrollRestoration = 'auto'; });
      window.addEventListener('pageshow', function (e) { if (e.persisted) history.scrollRestoration = 'manual'; });
    }
    window.addEventListener('load', function () { [0, 150, 500].forEach(function (ms) { setTimeout(function () { if (!touched) top(120); }, ms); }); });   // pár pixelov posunu = úplne hore
  }
  if (d.classList.contains('adm')) { try { if (localStorage.getItem('dk-adm-collapsed') === '1') d.classList.add('adm-collapsed'); } catch (e) {} }
})();
