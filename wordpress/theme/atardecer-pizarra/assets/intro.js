// Portada de entrada: sale una vez por visita (pestaña). Con la ✕, «Entrar al sitio» o Esc se entra al portal.
// Movimiento reducido o ahorro de datos: muestra el logo quieto y el video solo corre si la persona lo pide.
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
  const snd = box.querySelector('.ad-intro__sound');
  const btns = [...box.querySelectorAll('button')];
  const calm = matchMedia('(prefers-reduced-motion: reduce)').matches || !!(navigator.connection && navigator.connection.saveData);
  const opener = document.activeElement;
  root.classList.add('ad-intro-open');

  const play = sound => {
    if (!v.getAttribute('src')) v.src = v.dataset.src;
    if (v.ended) v.currentTime = 0;
    v.muted = !sound;
    v.play().catch(() => { v.muted = true; v.play().catch(() => {}); });
  };
  const setSound = on => {
    snd.setAttribute('aria-pressed', String(on));
    snd.textContent = on ? '🔊 Sonido activado' : '🔈 Activar sonido';
  };
  function close() {
    if (box.classList.contains('is-leaving')) return;
    v.pause();
    box.classList.add('is-leaving');
    root.classList.remove('ad-intro-open');
    document.removeEventListener('keydown', onKey);
    setTimeout(() => box.remove(), calm ? 0 : 350);
    if (opener && opener.focus) opener.focus();
  }
  function onKey(e) {
    if (e.key === 'Escape') return close();
    if (e.key !== 'Tab') return;
    const i = btns.indexOf(document.activeElement);
    const next = e.shiftKey ? (i <= 0 ? btns.length - 1 : i - 1) : (i + 1) % btns.length;
    e.preventDefault();
    btns[next].focus();
  }

  v.addEventListener('timeupdate', () => { bar.style.transform = 'scaleX(' + (v.currentTime / (v.duration || 10)) + ')'; });
  v.addEventListener('ended', () => box.classList.add('is-ended'));
  v.addEventListener('play', () => box.classList.remove('is-ended'));
  snd.addEventListener('click', () => {
    const on = snd.getAttribute('aria-pressed') !== 'true';
    if (on) play(true); else v.muted = true;
    setSound(on);
  });
  box.querySelector('.ad-intro__close').addEventListener('click', close);
  box.querySelector('.ad-intro__enter').addEventListener('click', close);
  document.addEventListener('keydown', onKey);

  if (calm) snd.textContent = '▶ Ver presentación';
  else play(false);
  box.querySelector('.ad-intro__close').focus();
})();
