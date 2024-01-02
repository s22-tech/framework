<?php

	class document {

		public static $layout = 'default';

		public static $title = '';
		public static $description = '';
		public static $head_tags = [];
		public static $style = [];
		public static $content = [];
		public static $foot_tags = [];
		public static $javascript = [];

		public static $snippets = [];
		public static $settings = [];
		public static $jsenv = [];

		public static function init() {
			event::register('before_capture', [__CLASS__, 'before_capture']);
			event::register('after_capture', [__CLASS__, 'after_capture']);
		}

		public static function before_capture() {

			header('Content-Security-Policy: frame-ancestors \'self\';'); // Clickjacking Protection
			header('Access-Control-Allow-Origin: '. self::ilink('')); // Only allow HTTP POST data data from own domain
			header('X-Frame-Options: SAMEORIGIN'); // Clickjacking Protection
			header('X-Powered-By: '. PLATFORM_NAME);

			// Default to AJAX layout on AJAX request
			if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				self::$layout = 'ajax';
			}

			// Set some snippets
			self::$snippets['language'] = language::$selected['code'];
			self::$snippets['text_direction'] = language::$selected['direction'];
			self::$snippets['charset'] = mb_http_output();
			self::$snippets['home_path'] = WS_DIR_APP;
			self::$snippets['template_path'] = preg_match('#^'. preg_quote(BACKEND_ALIAS, '#') .'#', route::$request) ? WS_DIR_APP . 'backend/template/' : WS_DIR_APP . 'frontend/templates/'.settings::get('template').'/';
			self::$title = [settings::get('site_name')];
			self::$head_tags['favicon'] = implode(PHP_EOL, [
				'<link rel="icon" href="'. document::href_rlink('storage://images/favicons/favicon.ico') .'" type="image/x-icon" sizes="32x32 48x48 64x64 96x96">',
				'<link rel="icon" href="'. document::href_rlink('storage://images/favicons/favicon-128x128.png') .'" type="image/png" sizes="128x128">',
				'<link rel="icon" href="'. document::href_rlink('storage://images/favicons/favicon-192x192.png') .'" type="image/png" sizes="192x192">',
				'<link rel="icon" href="'. document::href_rlink('storage://images/favicons/favicon-256x256.png') .'" type="image/png" sizes="255x255">',
			]);
			self::$head_tags['manifest'] = '<link rel="manifest" href="'. self::href_ilink('manifest.json') .'">'; // No namespace as relative to endpoint
			self::$head_tags['fontawesome'] = '<link rel="stylesheet" href="'. self::href_rlink('app://assets/fontawesome/font-awesome.min.css') .'">';
			self::$foot_tags['jquery'] = '<script src="'. self::href_rlink('app://assets/jquery/jquery-3.7.1.min.js') .'"></script>';

			// Hreflang
			if (!empty(route::$selected['resource']) && !preg_match('#^'. preg_quote(BACKEND_ALIAS, '#') .'#', route::$request)) {
				self::$head_tags['hreflang'] = '';
				foreach (language::$languages as $language) {
					if ($language['url_type'] == 'none') continue;
					self::$head_tags['hreflang'] .= '<link rel="alternate" hreflang="'. $language['code'] .'" href="'. self::href_ilink(route::$selected['resource'], [], true, ['page', 'sort'], $language['code']) .'" />' . PHP_EOL;
				}
				self::$head_tags['hreflang'] = trim(self::$head_tags['hreflang']);
			}

			// Get template settings
			if (!$template_config = include 'app://frontend/templates/'. settings::get('template') .'/config.inc.php') {
				$template_config = [];
			}

			self::$settings = settings::get('template_settings') ? json_decode(settings::get('template_settings'), true) : [];

			foreach ($template_config as $setting) {
				if (!isset(self::$settings[$setting['key']])) {
					self::$settings[$setting['key']] = $setting['default_value'];
				}
			}
		}

		public static function after_capture() {

			// JavaScript Environment

			self::$jsenv['platform'] = [
				'path' => WS_DIR_APP,
				'url' => self::ilink('f:'),
			];

			if (!empty(administrator::$data['id'])) {
				self::$jsenv['backend'] = [
					'path' => WS_DIR_APP . BACKEND_ALIAS .'/',
					'url' => self::ilink('b:'),
				];
			}

			self::$jsenv['session'] = [
				'id' => session::get_id(),
				'language_code' => language::$selected['code'],
			];

			self::$jsenv['template'] = [
				'url' => document::link(preg_match('#^'. preg_quote(BACKEND_ALIAS, '#') .'#', route::$request) ? 'backend/template' : 'frontend/templates/'. settings::get('template') .'/'),
				'settings' => self::$settings,
			];

			self::$head_tags[] = '<script>window._env = '. json_encode(self::$jsenv, JSON_UNESCAPED_SLASHES) .';</script>';
		}

		public static function optimize(&$output) {

			// Extract styling
			$output = preg_replace_callback('#(<html[^>]*>)(.*)(</html>)#is', function($matches) use (&$stylesheets, &$styles, &$javascripts, &$javascript) {

				// Extract stylesheets
				$stylesheets = [];

				$matches[2] = preg_replace_callback('#<link([^>]*rel="stylesheet"[^>]*)>\R*#is', function($match) use (&$stylesheets) {
					 $stylesheets[] = trim($match[0]);
				}, $matches[2]);

				// Extract inline styling
				$styles = [];

				$matches[2] = preg_replace_callback('#<style[^>]*>(.+?)</style>\R*#is', function($match) use (&$styles) {
					$styles[] = trim($match[1], "\r\n");
				}, $matches[2]);

				return $matches[1] . $matches[2] . $matches[3];
			}, $output);

			// Extract javascripts
			$output = preg_replace_callback('#(<body[^>]*>)(.*)(</body>)#is', function($matches) use (&$javascripts, &$javascript) {

				// Extract javascript resources
				$javascripts = [];

				$matches[2] = preg_replace_callback('#\R?<script([^>]+src="[^"]+"[^>]*)></script>\R*#is', function($match) use (&$javascripts) {
					$javascripts[] = trim($match[0]);
				}, $matches[2]);

				// Extract inline scripts
				$javascript = [];

				$matches[2] = preg_replace_callback('#<script[^>]*(?!src="[^"]+")[^>]*>(.+?)</script>\R*#is', function($match) use (&$javascript) {
					 $javascript[] = trim($match[1], "\r\n");
				}, $matches[2]);

				return $matches[1] . $matches[2] . $matches[3];
			}, $output);

			// Reinsert extracted stylesheets
			if (!empty($stylesheets)) {
				$stylesheets = implode(PHP_EOL, $stylesheets) . PHP_EOL;
				$output = preg_replace('#</head>#', addcslashes($stylesheets . '</head>', '\\$'), $output, 1);
			}

			// Reinsert inline styles
			if (!empty($styles)) {

				// Minify Inline CSS
				$search_replace = [
					'#/\*(?:.(?!/)|[^\*](?=/)|(?<!\*)/)*\*/#s' => '', // Remove comments
					'#([a-zA-Z0-9 \#=",-:()\[\]]+\{\s*\}\s*)#' => '', // Remove empty selectors
					'#\s+#' => ' ', // Replace multiple whitespace
					'#^\s+#' => ' ', // Replace leading whitespace
					'#\s*([:;{}])\s*#' => '$1',
					'#;}#' => '}',
				];

				$styles = implode(PHP_EOL, [
					'<style>',
					 //'<!--/*--><![CDATA[/*><!--*/', // Do we still need bypassing in 2023?
					 preg_replace(array_keys($search_replace), array_values($search_replace), implode(PHP_EOL . PHP_EOL, $styles)),
					 //'/*]]>*/-->',
					 '</style>',
				]) . PHP_EOL;

				$output = preg_replace('#</head>#', addcslashes($styles . '</head>', '\\$'), $output, 1);
			}

			// Reinsert javascript resources
			if (!empty($javascripts)) {
				$javascripts = implode(PHP_EOL, $javascripts) . PHP_EOL;
				$output = preg_replace('#</body>#is', addcslashes($javascripts .'</body>', '\\$'), $output, 1);
			}

			// Reinsert inline javascripts
			if (!empty($javascript)) {
				$javascript = implode(PHP_EOL, [
					'<script>',
					//. '<!--/*--><![CDATA[/*><!--*/', // Do we still need bypassing in 2023?
					implode(PHP_EOL . PHP_EOL, $javascript),
					//. '/*]]>*/-->',
					'</script>',
				]) . PHP_EOL;

				$output = preg_replace('#</body>#is', addcslashes($javascript . '</body>', '\\$'), $output, 1);
			}

			// Define some resources for preloading
			if (preg_match_all('#<(link|script)[^>]+>#', $output, $matches)) {

				$preloads = [];
				foreach ($matches[0] as $key => $match) {

					if (!preg_match('#(?<==")(https?:)?//[^"]+(?=")#is', $match, $m)) continue;

					switch ($matches[1][$key]) {
						case 'link':
							if (!preg_match('#stylesheet#', $m[0])) continue 2;
							$preloads[$m[0]] = 'style';
							break;
						case 'script':
							$preloads[$m[0]] = 'script';
							break;
					}
				}

				foreach ($preloads as $link => $type) {
					header('Link: <'.$link.'>; rel=preload; as='.$type, false);
				}
			}

			// Remove HTML comments
			$output = preg_replace_callback('#(<html[^>]*>)(.*)(</html>)#is', function($matches) {
				return preg_replace('#<!--.*?-->#ms', '', $matches[0]);
			}, $output);

			// Static domain
			if ($static_domain = settings::get('static_domain')) {
				$output = preg_replace_callback('#"https?://'. preg_quote($_SERVER['HTTP_HOST'], '#') .'(/[^"]+\.(a?png|avif|bmp|css|eot|gif|ico|jpe?g|js|map|otf|png|svg|tiff?|ttf|woff2?)(\?[^"]+)?)"#', function($matches) use ($static_domain) {
					return '"'. rtrim($static_domain, '/') .$matches[1].'"';
				}, $output);
			}
		}

		public static function render() {

			stats::start_watch('rendering');

			if (preg_match('#^'. preg_quote(BACKEND_ALIAS, '#') .'#', route::$request)) {
				$_page = new ent_view('app://backend/template/layouts/'.self::$layout.'.inc.php');
			} else {
				$_page = new ent_view('app://frontend/templates/'.settings::get('template').'/layouts/'.self::$layout.'.inc.php');
			}

			$_page->snippets = array_merge(self::$snippets, [
				'head_tags' => self::$head_tags,
				'style' => self::$style,
				'breadcrumbs' => breadcrumbs::render(),
				'notices' => notices::render(),
				'content' => self::$content,
				'foot_tags' => self::$foot_tags,
				'javascript' => self::$javascript,
				'important_notice' => settings::get('important_notice'),
			]);

			// Prepare title
			if (!empty(self::$title)) {
				if (!is_array(self::$title)) {
					self::$title = [self::$title];
				}
				self::$title = array_filter(self::$title);
				$_page->snippets['title'] = implode(' | ', array_reverse(self::$title));
			}

			// Add meta description
			if (!empty(self::$description)) {
				$_page->snippets['head_tags'][] = '<meta name="description" content="'. functions::escape_html(self::$description) .'">';
			}

			// Prepare styles
			if (!empty(self::$style)) {
				$_page->snippets['style'] = implode(PHP_EOL, [
					'<style>',
					 implode(PHP_EOL . PHP_EOL, self::$style),
					 '</style>',
				]);
			}

			// Prepare javascript
			if (!empty(self::$javascript)) {
				$_page->snippets['javascript'] = implode(PHP_EOL, [
					'<script>',
					 implode(PHP_EOL . PHP_EOL, self::$javascript),
					 '</script>',
				]);
			}

			// Prepare snippets
			foreach ($_page->snippets as $key => $snippet) {
				if (is_array($snippet)) {
					$_page->snippets[$key] = implode(PHP_EOL, $snippet);
				}
			}

			$_page->cleanup = true;

			$output = $_page->render();

			self::optimize($output);

			stats::stop_watch('rendering');

			$output .= PHP_EOL . stats::render();

			return $output;
		}

		public static function ilink($resource=null, $new_params=[], $inherit_params=null, $skip_params=[], $language_code=null) {

			switch (true) {

				case ($resource === null):
					if ($inherit_params === null) $inherit_params = true;
					$resource = route::$request;
					break;

				case (preg_match('#^b:(.*)$#', $resource, $matches)):
					$resource = WS_DIR_APP . BACKEND_ALIAS .'/'. $matches[1];
					break;

				case (preg_match('#^f:(.*)$#', $resource, $matches)):
					$resource = WS_DIR_APP . $matches[1];
					break;

				default:
					if (isset(route::$selected['endpoint']) && route::$selected['endpoint'] == 'backend') {
						$resource = WS_DIR_APP . BACKEND_ALIAS .'/'. $resource;
					} else {
						$resource = WS_DIR_APP . $resource;
					}
					break;
			}

			return (string)route::create_link($resource, $new_params, $inherit_params, $skip_params, $language_code, true);
		}

		public static function href_ilink($resource=null, $new_params=[], $inherit_params=null, $skip_params=[], $language_code=null) {
			return functions::escape_html(self::ilink($resource, $new_params, $inherit_params, $skip_params, $language_code));
		}

		public static function link($path=null, $new_params=[], $inherit_params=null, $skip_params=[], $language_code=null) {

			if (empty($path)) {
				$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
				if ($inherit_params === null) $inherit_params = true;
			}

			if (preg_match('#^(app://|storage://|'. preg_quote(DOCUMENT_ROOT, '#') .')#', $path)) {
				$path = functions::file_webpath($path);
			}

			return (string)route::create_link($path, $new_params, $inherit_params, $skip_params, $language_code, false);
		}

		public static function href_link($path=null, $new_params=[], $inherit_params=null, $skip_params=[], $language_code=null) {
			return functions::escape_html(self::link($path, $new_params, $inherit_params, $skip_params, $language_code));
		}

		public static function rlink($resource) {

			if (empty($resource) || !is_file($resource)) {
				return self::link(preg_replace('#^'. preg_quote(DOCUMENT_ROOT, '#') .'#', '', $resource));
			}

			if (preg_match('#^app://#', $resource)) {
				$webpath = preg_replace('#^app://#', WS_DIR_APP, $resource);

			} else if (preg_match('#^storage://#', $resource)) {
				$webpath = preg_replace('#^storage://#', WS_DIR_STORAGE, $resource);

			} else {
				$webpath = preg_replace('#^('. preg_quote(DOCUMENT_ROOT, '#') .')#', '', str_replace('\\', '/', $resource));
			}

			return self::link($webpath, ['_' => filemtime($resource)]);
		}

		public static function href_rlink($resource) {
			return functions::escape_html(self::rlink($resource));
		}
	}
