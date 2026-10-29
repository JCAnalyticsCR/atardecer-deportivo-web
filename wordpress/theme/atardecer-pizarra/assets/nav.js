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
