/* Verzia webu na GitHub Pages nemá PHP – formulár otvorí pripravený e-mail v poštovej aplikácii návštevníka. */
document.addEventListener('submit', function (e) {
  var f = e.target, to = f.getAttribute('data-mailto');
  if (!to) return;
  var bad = e.defaultPrevented;   // neprešla validácia (main.js)
  e.preventDefault();
  var b = f.querySelector('button[type=submit]'); if (b) b.classList.remove('is-loading');
  if (bad) return;
  var v = function (n) { var x = f.elements[n]; return x ? String(x.value).trim() : ''; };
  var lines = [['Meno', 'name'], ['E-mail', 'email'], ['Telefón', 'phone'], ['Organizácia', 'org'], ['Téma', 'topic']]
    .filter(function (p) { return v(p[1]); }).map(function (p) { return p[0] + ': ' + v(p[1]); });
  location.href = 'mailto:' + to + '?subject=' + encodeURIComponent(v('subject') || v('topic') || 'Správa z webu')
    + '&body=' + encodeURIComponent(lines.join('\r\n') + '\r\n\r\n' + v('message'));
  if (window.dkToast) window.dkToast('success', 'Otvárame vašu e-mailovú aplikáciu – správu v nej odošlite.');
});
