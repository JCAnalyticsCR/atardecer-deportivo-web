// Portada de entrada: sale una vez por visita (pestaña). Con la ✕, «Entrar al sitio» o Esc se entra al portal.
// Arranca de inmediato y con sonido. Si el navegador no permite audio sin un toque previo, arranca sin sonido y el
// audio se activa —desde el inicio— con el primer toque. Si el teléfono bloquea todo (iPhone en ahorro de batería),
// el primer toque lo reproduce.
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
  const opener = document.activeElement;
  root.classList.add('ad-intro-open');

  const say = text => { hint.textContent = text; hint.hidden = !text; };
  v.src = v.dataset.src;
  v.muted = false;
  v.play().then(() => say('')).catch(() => {
    v.muted = true;
    v.play()
      .then(() => say('🔊 Tocá la pantalla para activar el sonido'))
      .catch(() => say('▶ Tocá la pantalla para ver el video'));
  });

  // Primer gesto: activa el sonido y vuelve al inicio para que se escuche completo.
  function unlock(e) {
    if (e.target.closest && e.target.closest('.ad-intro__close, .ad-intro__enter')) return;
    if (!v.muted && !v.paused) return;
    if (v.currentTime > 0.3 || v.ended) v.currentTime = 0;
    v.muted = false;
    v.play().then(() => say('')).catch(() => {});
  }
  function close() {
    if (box.classList.contains('is-leaving')) return;
    v.pause();
    box.classList.add('is-leaving');
    root.classList.remove('ad-intro-open');
    document.removeEventListener('keydown', onKey);
    setTimeout(() => box.remove(), 300);
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
  box.querySelector('.ad-intro__close').focus({ preventScroll: true });
})();
