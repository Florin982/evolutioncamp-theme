<?php
/**
 * Evolution Camp - child theme di Kadence.
 */

add_action('wp_enqueue_scripts', function () {
	$ver = wp_get_theme()->get('Version');
	// Kadence carica già il proprio CSS: aggiungiamo quello del child dopo il parent.
	wp_enqueue_style('evolutioncamp-child', get_stylesheet_uri(), ['kadence-global'], $ver);
	wp_enqueue_script('evolutioncamp-child', get_stylesheet_directory_uri() . '/assets/js/site.js', [], $ver, ['in_footer' => true, 'strategy' => 'defer']);
}, 20);

// Precarica i due font principali (parte latina) per evitare il salto di testo.
add_action('wp_head', function () {
	$base = get_stylesheet_directory_uri() . '/assets/fonts/';
	foreach (['montserrat-latin', 'raleway-latin'] as $f) {
		printf('<link rel="preload" href="%s%s.woff2" as="font" type="font/woff2" crossorigin>' . "\n", esc_url($base), esc_attr($f));
	}
}, 1);

// Classe sul body per le pagine costruite a blocchi a tutta larghezza.
add_filter('body_class', function ($c) {
	if (is_page()) {
		$c[] = 'evo-page';
	}
	return $c;
});

// Footer personalizzato (sostituisce quello di default di Kadence), in IT/EN.
add_action('wp', function () {
	remove_all_actions('kadence_footer');
	add_action('kadence_footer', 'evo_footer');
});

function evo_footer() {
	$en = function_exists('pll_current_language') && pll_current_language() === 'en';
	$p  = $en ? '/en' : '';
	$t  = $en ? [
		'tag' => 'Adventure park, timber lodges and yoga in the hills of Marche.',
		'nav' => 'Explore', 'ct' => 'Contact', 'camp' => 'The Camp', 'acc' => 'Accommodation', 'about' => 'About us', 'contact' => 'Contact',
		'privacy' => 'Privacy & cookies', 'eu' => 'Projects co-financed 50% with European Funds, POR FESR 2014-2020.',
	] : [
		'tag' => 'Parco avventura, alloggi in legno e yoga sulle colline delle Marche.',
		'nav' => 'Esplora', 'ct' => 'Contatti', 'camp' => 'Il Camp', 'acc' => 'Alloggi', 'about' => 'Chi siamo', 'contact' => 'Contatti',
		'privacy' => 'Privacy e cookies', 'eu' => 'Progetti cofinanziati al 50% con Fondi Europei, POR FESR 2014-2020.',
	];
	$camp  = $en ? '/en/the-camp/' : '/parco-avventura/';
	$acc   = $en ? '/en/accommodation/' : '/alloggi/';
	$about = $en ? '/en/about/' : '/chi-siamo/';
	$cont  = $en ? '/en/contact/' : '/contatti/';
	?>
	<footer id="colophon" class="evo-site-footer">
	<div class="evo-wrap">
		<div class="evo-footer">
			<div>
				<h4>Evolution Camp</h4>
				<p><?php echo esc_html($t['tag']); ?></p>
				<p class="evo-footer__eu"><?php echo esc_html($t['eu']); ?></p>
			</div>
			<div>
				<h4><?php echo esc_html($t['nav']); ?></h4>
				<p><a href="<?php echo esc_url($camp); ?>"><?php echo esc_html($t['camp']); ?></a></p>
				<p><a href="<?php echo esc_url($acc); ?>"><?php echo esc_html($t['acc']); ?></a></p>
				<p><a href="<?php echo esc_url($about); ?>"><?php echo esc_html($t['about']); ?></a></p>
				<p><a href="<?php echo esc_url($cont); ?>"><?php echo esc_html($t['contact']); ?></a></p>
			</div>
			<div>
				<h4><?php echo esc_html($t['ct']); ?></h4>
				<p>Via Laghi, 3 – Loc. Pian di Rose<br>61040 S. Ippolito (PU)</p>
				<p><a href="tel:+393482502519">+39 348 25 02 519</a><br><a href="mailto:info@evolutioncamp.it">info@evolutioncamp.it</a></p>
				<p><a href="/privacy-cookies-policy/"><?php echo esc_html($t['privacy']); ?></a></p>
			</div>
		</div>
		<p class="evo-footer__eu" style="padding-bottom:2rem">© <?php echo esc_html(gmdate('Y')); ?> Evolution Camp</p>
	</div>
	</footer>
	<?php
}

// Pagine che iniziano con una copertina: l'header sta sopra, trasparente.
add_filter('body_class', function ($c) {
	if (is_page() && false !== strpos((string) get_post_field('post_content', get_queried_object_id()), 'evo-hero')) {
		$c[] = 'evo-has-hero';
	}
	if (is_singular('alloggio')) {
		$c[] = 'evo-has-hero';
		$c[] = 'evo-page';
	}
	return $c;
});

// Selettore lingua IT / EN in coda al menu principale e a quello mobile.
add_filter('wp_nav_menu_items', function ($items, $args) {
	if (!function_exists('pll_the_languages') || !in_array($args->theme_location ?? '', ['primary', 'mobile'], true)) {
		return $items;
	}
	$langs = pll_the_languages(['raw' => 1, 'hide_if_no_translation' => 0]);
	if (!$langs) {
		return $items;
	}
	$out = '';
	foreach ($langs as $l) {
		$out .= sprintf(
			'<a href="%s" hreflang="%s" lang="%s"%s>%s</a>',
			esc_url($l['url']), esc_attr($l['slug']), esc_attr($l['locale']),
			$l['current_lang'] ? ' class="is-current" aria-current="true"' : '',
			esc_html(strtoupper($l['slug']))
		);
	}
	return $items . '<li class="menu-item evo-lang">' . $out . '</li>';
}, 10, 2);

// Menu mobile: contatti rapidi in fondo al pannello.
add_filter('wp_nav_menu_items', function ($items, $args) {
	if (($args->menu_id ?? '') !== 'mobile-menu') {
		return $items;
	}
	$en = function_exists('pll_current_language') && pll_current_language() === 'en';
	$label = $en ? ['Call us', 'Write to us', 'Find us'] : ['Chiamaci', 'Scrivici', 'Dove siamo'];
	return $items . '<li class="menu-item evo-drawer-contact">'
		. '<span><small>' . esc_html($label[0]) . '</small><a href="tel:+393482502519">+39 348 25 02 519</a></span>'
		. '<span><small>' . esc_html($label[1]) . '</small><a href="mailto:info@evolutioncamp.it">info@evolutioncamp.it</a></span>'
		. '<span><small>' . esc_html($label[2]) . '</small>Via Laghi, 3 – Pian di Rose<br>S. Ippolito (PU)</span>'
		. '</li>';
}, 20, 2);

/**
 * Separa le foto della galleria dal testo di un alloggio.
 * Restituisce [elenco foto, contenuto senza galleria].
 */
function evo_alloggio_split_content($post) {
	$blocks = parse_blocks($post->post_content);
	$rest   = array_values(array_filter($blocks, function ($b) { return $b['blockName'] !== 'core/gallery'; }));
	$photos = [];

	// 1) Foto scelte nel riquadro «Galleria foto» dell'alloggio.
	$ids = function_exists('evo_galleria_ids') ? evo_galleria_ids($post->ID) : [];
	foreach ($ids as $id) {
		$full = wp_get_attachment_image_url($id, 'full');
		if (!$full) {
			continue;
		}
		$alt = trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)) ?: get_the_title($post);
		$photos[] = [
			'full'  => $full,
			'large' => wp_get_attachment_image_url($id, 'large') ?: $full,
			'thumb' => wp_get_attachment_image_url($id, 'medium') ?: $full,
			'alt'   => $alt,
		];
	}

	// 2) In mancanza, le foto di un eventuale blocco Galleria nel testo (compatibilità).
	if (!$photos) {
		foreach ($blocks as $block) {
			if ($block['blockName'] !== 'core/gallery') {
				continue;
			}
			foreach ($block['innerBlocks'] as $img) {
				$html = $img['innerHTML'] ?? '';
				$id   = (int) ($img['attrs']['id'] ?? 0);
				if (!$id && preg_match('/wp-image-(\d+)/', $html, $m)) {
					$id = (int) $m[1];
				}
				preg_match('/src="([^"]+)"/', $html, $src);
				preg_match('/alt="([^"]*)"/', $html, $alt);
				$url = $id ? wp_get_attachment_image_url($id, 'full') : ($src[1] ?? '');
				if (!$url) {
					continue;
				}
				$photos[] = [
					'full'  => $url,
					'large' => ($id ? wp_get_attachment_image_url($id, 'large') : '') ?: $url,
					'thumb' => ($id ? wp_get_attachment_image_url($id, 'medium') : '') ?: $url,
					'alt'   => $alt[1] ?? get_the_title($post),
				];
			}
		}
	}
	return [$photos, apply_filters('the_content', serialize_blocks($rest))];
}

function evo_render_slider($photos, $label) {
	if (!$photos) {
		return '';
	}
	$en = function_exists('pll_current_language') && pll_current_language() === 'en';
	$t  = $en
		? ['prev' => 'Previous photo', 'next' => 'Next photo', 'open' => 'Enlarge photo', 'go' => 'Show photo']
		: ['prev' => 'Foto precedente', 'next' => 'Foto successiva', 'open' => 'Ingrandisci foto', 'go' => 'Mostra foto'];
	$n = count($photos);
	ob_start(); ?>
	<div class="evo-slider" data-evo-slider data-count="<?php echo (int) $n; ?>">
		<div class="evo-slider__stage" role="region" aria-roledescription="carousel" aria-label="<?php echo esc_attr($label); ?>">
			<ul class="evo-slider__track">
				<?php foreach ($photos as $i => $p) : ?>
					<li class="evo-slide" aria-roledescription="slide" aria-label="<?php echo (int) ($i + 1) . ' / ' . (int) $n; ?>">
						<button type="button" class="evo-slide__btn" data-full="<?php echo esc_url($p['full']); ?>" data-alt="<?php echo esc_attr($p['alt']); ?>" aria-label="<?php echo esc_attr($t['open'] . ' ' . ($i + 1)); ?>">
							<img src="<?php echo esc_url($p['large']); ?>" alt="<?php echo esc_attr($p['alt']); ?>" <?php echo $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
							<span class="evo-slide__zoom" aria-hidden="true"></span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ($n > 1) : ?>
				<button type="button" class="evo-slider__nav evo-slider__nav--prev" data-dir="-1" aria-label="<?php echo esc_attr($t['prev']); ?>"><span aria-hidden="true"></span></button>
				<button type="button" class="evo-slider__nav evo-slider__nav--next" data-dir="1" aria-label="<?php echo esc_attr($t['next']); ?>"><span aria-hidden="true"></span></button>
			<?php endif; ?>
			<span class="evo-slider__count" aria-hidden="true"><b data-cur>1</b> / <?php echo (int) $n; ?></span>
		</div>
		<?php if ($n > 1) : ?>
			<ul class="evo-slider__thumbs">
				<?php foreach ($photos as $i => $p) : ?>
					<li><button type="button" data-go="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr($t['go'] . ' ' . ($i + 1)); ?>"<?php echo $i === 0 ? ' aria-current="true"' : ''; ?>>
						<img src="<?php echo esc_url($p['thumb']); ?>" alt="" loading="lazy" decoding="async"></button></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

// Galleria "mosaico": i blocchi Galleria con classe evo-mosaic diventano un mosaico con ingrandimento.
add_filter('render_block', function ($html, $block) {
	if (($block['blockName'] ?? '') !== 'core/gallery' || false === strpos($block['attrs']['className'] ?? '', 'evo-mosaic')) {
		return $html;
	}
	$en = function_exists('pll_current_language') && pll_current_language() === 'en';
	$open = $en ? 'Enlarge photo' : 'Ingrandisci foto';
	$out = '';
	$n = 0;
	foreach ($block['innerBlocks'] as $img) {
		$h  = $img['innerHTML'] ?? '';
		$id = (int) ($img['attrs']['id'] ?? 0);
		if (!$id && preg_match('/wp-image-(\d+)/', $h, $m)) {
			$id = (int) $m[1];
		}
		preg_match('/src="([^"]+)"/', $h, $src);
		preg_match('/alt="([^"]*)"/', $h, $alt);
		$full  = $id ? wp_get_attachment_image_url($id, 'full') : ($src[1] ?? '');
		$large = ($id ? wp_get_attachment_image_url($id, 'large') : '') ?: $full;
		if (!$full) {
			continue;
		}
		$n++;
		$out .= sprintf(
			'<li class="evo-mosaic__item"><button type="button" class="evo-mosaic__btn" data-full="%1$s" data-alt="%2$s" aria-label="%3$s"><img src="%4$s" alt="%2$s" loading="lazy" decoding="async"><span class="evo-mosaic__zoom" aria-hidden="true"></span></button></li>',
			esc_url($full), esc_attr($alt[1] ?? ''), esc_attr($open . ' ' . $n), esc_url($large)
		);
	}
	return '<ul class="evo-mosaic evo-reveal" data-evo-lightbox>' . $out . '</ul>';
}, 10, 2);
