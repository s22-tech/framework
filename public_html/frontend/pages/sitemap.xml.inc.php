<?php

	@set_time_limit(300);

	ob_clean();
	header('Content-type: application/xml; charset='. mb_http_output());

	language::set(settings::get('site_language_code'));

	echo '<?xml version="1.0" encoding="'. mb_http_output() .'"?>' . PHP_EOL
		 . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;

	$hreflangs = [];
	foreach (language::$languages as $language) {
		if ($language['url_type'] == 'none') continue;
		$hreflangs[] = '  <xhtml:link rel="alternate" hreflang="'. $language['code'] .'" href="'. document::href_ilink('', [], false, [], $language['code']) .'">';
	}

	echo implode(PHP_EOL, [
		'  <url>',
		'    <loc>'. document::ilink('') .'</loc>',
		implode(PHP_EOL, $hreflangs),
		'    <lastmod>'. date('Y-m-d') .'</lastmod>',
		'    <changefreq>daily</changefreq>',
		'    <priority>1.0</priority>',
		'  </url>'
	]) . PHP_EOL;

	echo '</urlset>';

	exit; // As we don't need app_footer to process this with a template
