/**
 * Nuestra señal (video de marca).
 * PC con mouse: descarga al acercarse (rootMargin 50%) y se reproduce sin sonido al verse (threshold 0.45).
 * Teléfono, tablet, ahorro de datos o movimiento reducido: no descarga nada hasta que la persona toque reproducir.
 */
( function () {
	const tv = document.querySelector( '.senal__tv' );
	if ( ! tv ) {
		return;
	}
	const v = tv.querySelector( '.senal__video' );
	const bar = tv.querySelector( '.senal__bar' );
	const auto = window.matchMedia( '(min-width: 1024px) and (hover: hover) and (prefers-reduced-motion: no-preference)' ).matches
		&& ! ( navigator.connection && navigator.connection.saveData );
	let done = false;

	const state = ( s ) => { tv.dataset.state = s; };
	const load = () => {
		if ( ! v.getAttribute( 'src' ) ) {
			v.src = v.dataset.src;
			v.preload = 'auto';
		}
	};
	const start = ( withSound ) => {
		load();
		if ( withSound ) {
			v.muted = false;
			if ( done || v.ended ) {
				v.currentTime = 0;
			}
			done = false;
		}
		v.play().catch( () => {
			v.muted = true;
			v.play().catch( () => state( 'idle' ) );
		} );
	};

	v.addEventListener( 'timeupdate', () => { bar.style.transform = 'scaleX(' + ( v.currentTime / ( v.duration || 10 ) ) + ')'; } );
	v.addEventListener( 'play', () => state( 'playing' ) );
	v.addEventListener( 'pause', () => { if ( ! v.ended ) { state( 'idle' ); } } );
	v.addEventListener( 'ended', () => { done = true; state( 'ended' ); } );
	tv.querySelector( '.senal__play' ).addEventListener( 'click', () => start( true ) );
	document.querySelector( '.senal__sound' )?.addEventListener( 'click', () => {
		tv.scrollIntoView( { block: 'center' } );
		start( true );
	} );

	// Fuera de cuadro siempre se pausa (también si la puso la persona).
	new IntersectionObserver( ( es ) => {
		const e = es[ 0 ];
		if ( ! e.isIntersecting && ! v.paused ) {
			v.pause();
		} else if ( auto && e.isIntersecting && ! done && v.paused ) {
			start( false );
		}
	}, { threshold: 0.45 } ).observe( tv );
	if ( auto ) {
		new IntersectionObserver( ( es ) => { if ( es[ 0 ].isIntersecting ) { load(); } }, { rootMargin: '50% 0px' } ).observe( tv );
	}
} )();
