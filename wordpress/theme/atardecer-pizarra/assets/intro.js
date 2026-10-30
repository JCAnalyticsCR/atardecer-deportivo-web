// Portada de entrada: sale una vez por visita (pestaña). Con la ✕, «Entrar al sitio» o Esc se entra al portal.
// Siempre intenta arrancar con sonido. Si el navegador lo bloquea (pasa cuando la persona aún no tocó la página),
// arranca sin sonido y el audio se activa —desde el inicio— con el primer toque, clic o tecla.
(() => {
  const box = document.getElementById('ad-intro');
  if (!box) return;
  let seen = false;
  try { seen = !!sessionStorage.getItem('ad-intro'); } catch (e) { /* sin almacenamiento: se muestra */ }
  if (seen) { box.remove(); return; }
  try { sessionStorage.setItem('ad-intro', '1'); } catch (e) { /* idem */ }

  const root = document.documentElement;
  const v = box.querySelector('.ad-intro__video');
  const bar = box.querySelector('.ad-intro__bar');
  const hint = box.querySelector('.ad-intro__hint');
  const btns = [...box.querySelectorAll('button')];
  const calm = matchMedia('(prefers-reduced-motion: reduce)').matches || !!(navigator.connection && navigator.connection.saveData);
  const opener = document.activeElement;
  let started = false;
  root.classList.add('ad-intro-open');
  v.src = v.dataset.src;

  const showHint = on => { hint.hidden = !on; };
  function start() {
    if (started || calm) return;
    started = true;
    v.muted = false;
    v.play().then(() => showHint(false)).catch(() => {
      v.muted = true;
      showHint(true);
      v.play().catch(() => {});
    });
  }
  // Primer gesto: activa el sonido y vuelve al inicio para que se escuche completo.
  function unlock(e) {
    if (e.target.closest && e.target.closest('.ad-intro__close, .ad-intro__enter')) return;
    if (!v.muted && !v.paused) return;
    if (v.currentTime > 0.3 || v.ended) v.currentTime = 0;
    v.muted = false;
    started = true;
    v.play().then(() => showHint(false)).catch(() => {});
  }
  function close() {
    if (box.classList.contains('is-leaving')) return;
    v.pause();
    box.classList.add('is-leaving');
    root.classList.remove('ad-intro-open');
    document.removeEventListener('keydown', onKey);
    setTimeout(() => box.remove(), calm ? 0 : 300);
    if (opener && opener.focus) opener.focus();
  }
  function onKey(e) {
    if (e.key === 'Escape') return close();
    if (e.key === 'Tab') {
      const i = btns.indexOf(document.activeElement);
      const next = e.shiftKey ? (i <= 0 ? btns.length - 1 : i - 1) : (i + 1) % btns.length;
      e.preventDefault();
      btns[next].focus();
      return;
    }
    if (e.key !== 'Enter' && e.key !== ' ') unlock(e);
  }

  v.addEventListener('timeupdate', () => { bar.style.transform = 'scaleX(' + (v.currentTime / (v.duration || 10)) + ')'; });
  v.addEventListener('ended', () => box.classList.add('is-ended'));
  v.addEventListener('play', () => box.classList.remove('is-ended'));
  box.addEventListener('click', unlock);
  box.querySelector('.ad-intro__close').addEventListener('click', close);
  box.querySelector('.ad-intro__enter').addEventListener('click', close);
  document.addEventListener('keydown', onKey);

  if (calm) {
    hint.textContent = 'Tocá la pantalla para ver la presentación';
    showHint(true);
  } else {
    // Arranca cuando ya hay video suficiente para no trabarse (o a los 3 s, lo que pase primero).
    v.addEventListener('canplaythrough', start, { once: true });
    setTimeout(start, 3000);
  }
  box.querySelector('.ad-intro__close').focus();
})();
