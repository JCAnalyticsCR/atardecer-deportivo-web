// Abre y cierra el menú móvil y el buscador.
document.querySelectorAll('[data-toggle]').forEach((btn) => {
	const target = document.getElementById(btn.dataset.toggle);
	if (!target) return;
	btn.addEventListener('click', () => {
		const open = target.classList.toggle('is-open');
		btn.setAttribute('aria-expanded', String(open));
		if (open) target.querySelector('input')?.focus();
	});
});

// Cinta de patrocinadores: las imágenes fuera de la vista horizontal no se cargarían a tiempo con el
// lazy-load nativo (quedan recortadas), así que se piden todas juntas cuando la sección se acerca.
const cinta = document.querySelector('.sponsors__marquee');
if (cinta && 'IntersectionObserver' in window) {
	const io = new IntersectionObserver((entries) => {
		if (!entries[0].isIntersecting) return;
		cinta.querySelectorAll('img[loading="lazy"]').forEach((img) => { img.loading = 'eager'; });
		io.disconnect();
	}, { rootMargin: '50%' });
	io.observe(cinta);
}
