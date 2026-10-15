<?php
/**
 * SMTP: tutte le email di WordPress (prenotazioni, Contact Form 7, password…) passano da Qboxmail invece che da Mailpit.
 * Incluso da functions.php.
 *
 * Configurazione (fuori dal codice): conf/evolutioncamp.contolemix.com/smtp.ini, sezione [smtp]
 *   host, port, secure (ssl|tls), user, pass, from, from_name, cc (copia di ogni email, separati da virgola)
 * Senza file o senza password il sito torna al sendmail del container (Mailpit).
 */

if (!defined('ABSPATH')) {
	exit;
}

function evo_smtp_config() {
	static $cfg = null;
	if ($cfg !== null) {
		return $cfg;
	}
	$cfg = [];
	$file = dirname(rtrim(ABSPATH, '/')) . '/conf/evolutioncamp.contolemix.com/smtp.ini';
	if (is_readable($file)) {
		$ini = @parse_ini_file($file, true, INI_SCANNER_RAW);
		foreach ((array) ($ini['smtp'] ?? []) as $k => $v) {
			$cfg[strtolower(trim($k))] = trim($v, " \t\"'");
		}
	}
	return $cfg;
}

add_action('phpmailer_init', function ($phpmailer) {
	$c = evo_smtp_config();
	if (empty($c['host']) || empty($c['user']) || empty($c['pass'])) {
		return;
	}
	$phpmailer->isSMTP();
	$phpmailer->Host       = $c['host'];
	$phpmailer->Port       = (int) ($c['port'] ?? 465);
	$phpmailer->SMTPSecure = $c['secure'] ?? 'ssl';
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Username   = $c['user'];
	$phpmailer->Password   = $c['pass'];
	$phpmailer->Timeout    = 25;
	// Qboxmail accetta solo il mittente autorizzato: l'eventuale Reply-To del messaggio resta invariato.
	if (!empty($c['from'])) {
		$phpmailer->setFrom($c['from'], $c['from_name'] ?? 'Evolution Camp', false);
		$phpmailer->Sender = $c['from'];
	}
	// Copia di ogni email (cc = indirizzi separati da virgola), salvo chi è già destinatario.
	$gia = array_map('strtolower', array_keys($phpmailer->getAllRecipientAddresses()));
	foreach (array_filter(array_map('trim', explode(',', $c['cc'] ?? ''))) as $cc) {
		if (is_email($cc) && !in_array(strtolower($cc), $gia, true)) {
			$phpmailer->addCC($cc);
		}
	}
});

add_action('wp_mail_failed', function ($error) {
	error_log('[evo-smtp] invio non riuscito: ' . $error->get_error_message()); // mai la password nei log
});
