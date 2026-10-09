<?php
/**
 * Pagina singola di un alloggio.
 */
get_header();

$en = function_exists('pll_current_language') && pll_current_language() === 'en';
$tx = $en ? [
	'back' => 'All accommodation', 'beds' => 'beds', 'bath' => 'bathroom', 'baths' => 'bathrooms', 'ideal' => 'Ideal for',
	'inc_t' => 'Included in your stay', 'feat_t' => 'Highlights', 'book' => 'Book now', 'ask' => 'Ask for information',
	'bk_t' => 'Book this accommodation', 'bk_p' => 'Check availability and prices on our booking page.', 'call' => 'Or call us',
	'others' => 'Other accommodation', 'gal' => 'Gallery',
	'inc' => ['Linen and towels', 'Air conditioning and wifi', 'Wardrobe and hairdryer', 'Shared kitchen, washing machine and Smart TV', 'Parking and garden'],
	'contact' => '/en/contact/#form', 'list' => '/en/accommodation/',
] : [
	'back' => 'Tutti gli alloggi', 'beds' => 'posti letto', 'bath' => 'bagno', 'baths' => 'bagni', 'ideal' => 'Ideale per',
	'inc_t' => 'Incluso nel soggiorno', 'feat_t' => 'Caratteristiche', 'book' => 'Prenota', 'ask' => 'Chiedi informazioni',
	'bk_t' => 'Prenota questo alloggio', 'bk_p' => 'Verifica disponibilità e prezzi sulla nostra pagina di prenotazione.', 'call' => 'Oppure chiamaci',
	'others' => 'Altri alloggi', 'gal' => 'Galleria',
	'inc' => ['Biancheria e asciugamani', 'Aria condizionata e wifi', 'Armadio e asciugacapelli', 'Cucina, lavatrice e Smart TV ad uso comune', 'Parcheggio e giardino'],
	'contact' => '/contatti/#form', 'list' => '/alloggi/',
];

while (have_posts()) :
	the_post();
	$id = get_the_ID();
	$d  = evo_alloggio_data($id);
	$bagni = $d['bagni'] . ' ' . ($d['bagni'] > 1 ? $tx['baths'] : $tx['bath']);
	[$photos, $text] = evo_alloggio_split_content(get_post());
	?>
	<div class="evo-alloggio">
		<header class="evo-single-hero">
			<?php if (has_post_thumbnail()) {
				echo get_the_post_thumbnail($id, 'full', ['class' => 'evo-single-hero__img', 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high']);
			} ?>
			<div class="evo-wrap evo-single-hero__inner">
				<a class="evo-back" href="<?php echo esc_url($tx['list']); ?>">&larr; <?php echo esc_html($tx['back']); ?></a>
				<p class="evo-eyebrow"><?php echo esc_html($d['struttura'] ?: $d['tipo_label']); ?></p>
				<h1><?php the_title(); ?></h1>
				<?php if (has_excerpt()) : ?><p class="evo-lead"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
			</div>
		</header>

		<section class="evo-section">
			<div class="evo-wrap evo-alloggio__grid">
				<div class="evo-alloggio__main">
					<?php echo evo_render_slider($photos, $tx['gal'] . ' – ' . get_the_title()); ?>
					<ul class="evo-facts">
						<?php if ($d['posti']) : ?><li><strong><?php echo (int) $d['posti']; ?></strong><span><?php echo esc_html($tx['beds']); ?></span></li><?php endif; ?>
						<?php if ($d['bagni']) : ?><li><strong><?php echo (int) $d['bagni']; ?></strong><span><?php echo esc_html($d['bagni'] > 1 ? $tx['baths'] : $tx['bath']); ?></span></li><?php endif; ?>
						<li><strong class="evo-facts__txt"><?php echo esc_html($d['tipo_label']); ?></strong><span><?php echo esc_html($tx['ideal']); ?></span></li>
					</ul>

					<div class="evo-alloggio__content entry-content"><?php echo $text; // phpcs:ignore ?></div>

					<?php if ($d['dotazioni']) : ?>
						<h2 class="evo-h3"><?php echo esc_html($tx['feat_t']); ?></h2>
						<ul class="evo-checks"><?php foreach ($d['dotazioni'] as $f) { echo '<li>' . esc_html($f) . '</li>'; } ?></ul>
					<?php endif; ?>

					<h2 class="evo-h3"><?php echo esc_html($tx['inc_t']); ?></h2>
					<ul class="evo-checks"><?php foreach ($tx['inc'] as $f) { echo '<li>' . esc_html($f) . '</li>'; } ?></ul>
				</div>

				<aside class="evo-booking" aria-label="<?php echo esc_attr($tx['bk_t']); ?>">
					<p class="evo-eyebrow"><?php echo esc_html($d['struttura'] ?: $d['tipo_label']); ?></p>
					<h2><?php the_title(); ?></h2>
					<p class="evo-booking__facts"><?php echo esc_html(implode(' · ', array_filter([$d['posti'] ? $d['posti'] . ' ' . $tx['beds'] : '', $d['bagni'] ? $bagni : '']))); ?></p>
					<p><?php echo esc_html($tx['bk_p']); ?></p>
					<a class="evo-btn evo-btn--block" href="<?php echo esc_url($d['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($tx['book']); ?></a>
					<a class="evo-btn evo-btn--ghost evo-btn--block" href="<?php echo esc_url($tx['contact']); ?>"><?php echo esc_html($tx['ask']); ?></a>
					<p class="evo-booking__call"><?php echo esc_html($tx['call']); ?>: <a href="tel:+393482502519">+39 348 25 02 519</a></p>
				</aside>
			</div>
		</section>

		<section class="evo-section evo-section--sabbia">
			<div class="evo-wrap">
				<h2 class="evo-title"><?php echo esc_html($tx['others']); ?></h2>
				<?php echo do_shortcode('[evo_alloggi escludi="' . (int) $id . '"]'); ?>
			</div>
		</section>
	</div>
	<?php
endwhile;

get_footer();
