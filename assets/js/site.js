document.documentElement.classList.add('evo-js');
// Rivela gli elementi .evo-reveal quando entrano nello schermo.
(function () {
	var els = document.querySelectorAll('.evo-reveal');
	if (!('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('is-in'); }); return; }
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
	}, { threshold: 0.12 });
	els.forEach(function (e) { io.observe(e); });
})();

// Header: diventa solido quando si scorre la pagina.
(function () {
	var b = document.body, on = false;
	function upd() {
		var s = window.scrollY > 40;
		if (s !== on) { on = s; b.classList.toggle('evo-scrolled', s); }
	}
	upd();
	window.addEventListener('scroll', upd, { passive: true });
})();

// Slider foto + ingrandimento (lightbox) per le pagine degli alloggi.
(function () {
	var sliders = document.querySelectorAll('[data-evo-slider]');
	var groups = document.querySelectorAll('[data-evo-lightbox]');
	if (!sliders.length && !groups.length) return;
	var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var behavior = reduce ? 'auto' : 'smooth';

	/* ---------- Lightbox (una sola per pagina) ---------- */
	var lb, lbImg, lbCount, lbPrev, lbNext, lbClose, items = [], cur = 0, opener = null, onGo = null;
	function build() {
		lb = document.createElement('div');
		lb.className = 'evo-lb';
		lb.setAttribute('role', 'dialog');
		lb.setAttribute('aria-modal', 'true');
		lb.setAttribute('aria-label', document.documentElement.lang.indexOf('en') === 0 ? 'Photo gallery' : 'Galleria fotografica');
		lb.hidden = true;
		lb.innerHTML = '<figure class="evo-lb__fig"><img alt=""></figure>' +
			'<button type="button" class="evo-lb__btn evo-lb__close" aria-label="Chiudi / Close"><span></span></button>' +
			'<button type="button" class="evo-lb__btn evo-lb__nav evo-lb__prev" aria-label="Precedente / Previous"><span></span></button>' +
			'<button type="button" class="evo-lb__btn evo-lb__nav evo-lb__next" aria-label="Successiva / Next"><span></span></button>' +
			'<span class="evo-lb__count" aria-live="polite"></span>';
		document.body.appendChild(lb);
		lbImg = lb.querySelector('img'); lbCount = lb.querySelector('.evo-lb__count');
		lbPrev = lb.querySelector('.evo-lb__prev'); lbNext = lb.querySelector('.evo-lb__next'); lbClose = lb.querySelector('.evo-lb__close');
		lbClose.addEventListener('click', close);
		lbPrev.addEventListener('click', function () { step(-1); });
		lbNext.addEventListener('click', function () { step(1); });
		lb.addEventListener('click', function (e) { if (e.target === lb || e.target.classList.contains('evo-lb__fig')) close(); });
		document.addEventListener('keydown', function (e) {
			if (lb.hidden) return;
			if (e.key === 'Escape') close();
			else if (e.key === 'ArrowLeft') step(-1);
			else if (e.key === 'ArrowRight') step(1);
			else if (e.key === 'Tab') {
				var f = [lbClose, lbPrev, lbNext].filter(function (b) { return b.offsetParent !== null; });
				var i = f.indexOf(document.activeElement);
				e.preventDefault();
				f[(i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
			}
		});
		var x0 = null;
		lb.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
		lb.addEventListener('touchend', function (e) {
			if (x0 === null) return;
			var dx = e.changedTouches[0].clientX - x0; x0 = null;
			if (Math.abs(dx) > 50) step(dx < 0 ? 1 : -1);
		}, { passive: true });
	}
	function show(i) {
		cur = (i + items.length) % items.length;
		var it = items[cur];
		lbImg.classList.remove('is-in');
		lbImg.onload = function () { lbImg.classList.add('is-in'); };
		lbImg.src = it.full; lbImg.alt = it.alt || '';
		if (lbImg.complete) lbImg.classList.add('is-in');
		lbCount.textContent = (cur + 1) + ' / ' + items.length;
		var one = items.length < 2;
		lbPrev.hidden = lbNext.hidden = one;
		[1, -1].forEach(function (d) { var n = items[(cur + d + items.length) % items.length]; if (n) { (new Image()).src = n.full; } });
		if (onGo) onGo(cur);
	}
	function step(d) { show(cur + d); }
	function open(list, i, trigger, cb) {
		if (!lb) build();
		items = list; opener = trigger; onGo = cb;
		lb.hidden = false;
		document.documentElement.classList.add('evo-lb-open');
		requestAnimationFrame(function () { lb.classList.add('is-open'); });
		show(i);
		lbClose.focus();
	}
	function close() {
		lb.classList.remove('is-open');
		document.documentElement.classList.remove('evo-lb-open');
		setTimeout(function () { lb.hidden = true; lbImg.removeAttribute('src'); }, reduce ? 0 : 220);
		if (opener) opener.focus();
	}

	/* ---------- Slider ---------- */
	sliders.forEach(function (root) {
		var track = root.querySelector('.evo-slider__track');
		var slides = root.querySelectorAll('.evo-slide');
		var btns = root.querySelectorAll('.evo-slide__btn');
		var thumbs = root.querySelectorAll('.evo-slider__thumbs button');
		var thumbBox = root.querySelector('.evo-slider__thumbs');
		var counter = root.querySelector('[data-cur]');
		var list = Array.prototype.map.call(btns, function (b) { return { full: b.dataset.full, alt: b.dataset.alt }; });
		var idx = 0;

		function index() { return Math.max(0, Math.min(slides.length - 1, Math.round(track.scrollLeft / track.clientWidth))); }
		function mark(i) {
			idx = i;
			if (counter) counter.textContent = i + 1;
			thumbs.forEach(function (t, k) {
				if (k === i) { t.setAttribute('aria-current', 'true'); } else { t.removeAttribute('aria-current'); }
			});
			var t = thumbs[i];
			if (t && thumbBox) thumbBox.scrollTo({ left: t.offsetLeft - (thumbBox.clientWidth - t.clientWidth) / 2, behavior: behavior });
		}
		function go(i, instant) {
			i = (i + slides.length) % slides.length;
			track.scrollTo({ left: i * track.clientWidth, behavior: instant ? 'auto' : behavior });
			mark(i);
		}
		var tick = null;
		track.addEventListener('scroll', function () {
			if (tick) return;
			tick = requestAnimationFrame(function () { tick = null; var i = index(); if (i !== idx) mark(i); });
		}, { passive: true });
		root.querySelectorAll('.evo-slider__nav').forEach(function (b) {
			b.addEventListener('click', function () { go(idx + parseInt(b.dataset.dir, 10)); });
		});
		thumbs.forEach(function (t) { t.addEventListener('click', function () { go(parseInt(t.dataset.go, 10)); }); });
		btns.forEach(function (b, i) {
			b.addEventListener('click', function () { open(list, i, b, function (n) { go(n, true); }); });
		});
		root.querySelector('.evo-slider__stage').addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') { go(idx - 1); e.preventDefault(); }
			else if (e.key === 'ArrowRight') { go(idx + 1); e.preventDefault(); }
		});
		window.addEventListener('resize', function () { track.scrollTo({ left: idx * track.clientWidth, behavior: 'auto' }); });
	});

	/* ---------- Mosaico: ogni foto apre l'ingrandimento ---------- */
	groups.forEach(function (g) {
		var btns = g.querySelectorAll('[data-full]');
		var list = Array.prototype.map.call(btns, function (b) { return { full: b.dataset.full, alt: b.dataset.alt }; });
		btns.forEach(function (b, i) { b.addEventListener('click', function () { open(list, i, b, null); }); });
	});
})();
