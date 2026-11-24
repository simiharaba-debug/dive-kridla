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
  if (d.classList.contains('adm')) { try { if (localStorage.getItem('dk-adm-collapsed') === '1') d.classList.add('adm-collapsed'); } catch (e) {} }
})();
