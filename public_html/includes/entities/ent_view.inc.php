<?php

	class ent_view {
		public $view;
		public $html;
		public $snippets = [];
		public $wrapper = ['{{', '}}'];
		public $cleanup = false;
		private $_parsers = [];

		public function __construct($view='') {

			if ($view) {

				// Absolute path
				if (preg_match('#^app://#', $view) || preg_match('#^([a-zA-Z]:)?/#', $view)) {
					$this->view = $view;

				// Relative path
				} else {
					// Fall back to relative path
					$this->view = 'app://'. route::$selected['endpoint'] .'/template/' . preg_replace('#\.inc\.php$#', '', $view) . '.inc.php';
				}

				if (!is_file($this->view)) {
					$this->view = 'app://frontend/template/'. $view;
				}
			}

			// Register default parser for snippets {{var|modifier1|modifier2}}
			$this->register_parser('([0-9a-zA-Z_\.]+)(|[^'. preg_quote($this->wrapper[1][0], '#') .']+)?', function($matches) {

				if (!isset($this->snippets[$matches[1]])) {
					return $matches[0];
				}

				if ($this->snippets[$matches[1]] instanceof ent_view) {
					$this->snippets[$matches[1]] = (string)$this->snippets[$matches[1]];
				}

				if (empty($matches[2])) {
					return $this->snippets[$matches[1]];
				}

				$output = $this->snippets[$matches[1]];
				$modifiers = !empty($matches[2]) ? preg_split('#\|#', $matches[2], -1, PREG_SPLIT_NO_EMPTY) : [];

				// Title Casing
				if (in_array('title', $modifiers)) {
					$output = ucwords(strtolower($output));
				}

				// Uppercasing
				if (in_array('uppercase', $modifiers)) {
					$output = strtoupper($output);
				}

				// Lowercasing
				if (in_array('lowercase', $modifiers)) {
					$output = strtolower($output);
				}

				// Currency Calculation
				if (in_array('calculate', $modifiers)) {
					$output = currency::calculate($output);
				}

				// Money Formatting
				if (in_array('money', $modifiers)) {
					$output = currency::format($output);
				}

				// Escape Special HTML Characters
				if (in_array('escape', $modifiers)) {
					$output = functions::escape_html($output);
				}

				return $output;
			});

/*
			// Parser for Translations: {{translate "title_key" "Text"}}
			$this->register_parser('translate "([^\"]+)"(?:, "([^\"]+)")?', function($matches) {
				return language::translate($matches[1], isset($matches[2]) ? $matches[2] : '');
			});

			// Parser for Settings {{setting "key"}}
			$this->register_parser('setting "([^\"]+)"', function($matches) {
				return settings::get($matches[1]);
			});

			// Parser for Fonticons: {{fonticon "key"}}
			$this->register_parser('fonticon "([^\"]+)"', function($matches) {
				return functions::draw_fonticon($matches[1]);
			});

			// Parser for Includes: {{include "path/to/file.tpl"}}
			//$this->register_parser('#>(.*?)#', function($matches) {
			$this->register_parser('include "(.*?)"', function($matches) {
				if (file_exists($file = FS_DIR_TEMPLATE . $matches[1] .'.tpl')) {
					return file_get_contents($file);
				}
			});

			// Parser for Each iteration: {{each $array as $var}} {{/each}}
			$this->register_parser('each (.*?) as (.*?)'. preg_quote($this->wrapper[1], '#') .'(.*?)'. preg_quote($this->wrapper[0], '#') .'/each', function($matches) {

				if (!empty($this->snippets[$matches[1]]) || !is_array($this->snippets[$matches[1]])) return '';

				$view = new ent_view();
				$view->html = $matches[3];
				$view->snippets = $this->snippets;
				$view->snippets[$matches[2]] = $this->snippets[$matches[1]];

				return $view->stitch();
			});

			// Parser for If conditions: {if $var} {/if}
			$this->register_parser('if (.*?)'. preg_quote($this->wrapper[1], '#') .'(.*?)'. preg_quote($this->wrapper[0], '#') .'/if', function($matches) {
				if (!empty($this->snippets[$matches[1]]) && (float)$this->snippets[$matches[1]] != 0) return '';
				return $this->snippets[$matches[1]];
			});
*/
		}

		public function register_parser($pattern, $callable) {
			$pattern = '#'. preg_quote($this->wrapper[0], '#') . $pattern . preg_quote($this->wrapper[1], '#') .'#s';
			$this->_parsers = [$pattern => $callable] + $this->_parsers;
		}

		public function __toString() {
			return $this->render();
		}

		// Render and make it stick
		public function stitch($view=null, $cleanup=null) {
			return $this->html = $this->render($view, $cleanup);
		}

		// Render but don't make it stick
		public function render($view=null, $cleanup=null) {

			if ($view) {

				// Absolute path
				if (preg_match('#^app://#', $view) || preg_match('#^([a-zA-Z]:)?/#', $view)) {
					$this->view = $view;

				// Relative path
				} else {
					// Fall back to relative path
					$this->view = 'app://'. route::$selected['endpoint'] .'/template/' . preg_replace('#\.inc\.php$#', '', $view) . '.inc.php';
				}

				if (!is_file($this->view)) {
					$this->view = 'app://frontend/template/'. $view;
				}

			} else {
				$view = $this->view;
			}

			if ($cleanup === null) {
				$cleanup = $this->cleanup;
			}

			$html = $this->html;

			// Load and process view in an isolated scope
			if ($view) {
				$html = (function(){
					ob_start();
					extract(func_get_arg(1));
					include func_get_arg(0);
					return ob_get_clean();
				})($view, $this->snippets);
			}

			if (!$html) {
				return '';
			}

			foreach ($this->snippets as $key => $snippet) {
				if (!is_string($snippet)) continue;
				$this->snippets[$key] = preg_replace_callback_array($this->_parsers, $snippet);
			}

			if (is_string($html)) {
				$html = preg_replace_callback_array($this->_parsers, $html);
			}

			if ($cleanup) {
				//$html = preg_replace('#'. preg_quote($this->wrapper[0], '#') .'(if|each) .*?'. preg_quote($this->wrapper[0], '#') .'\1'. preg_quote($this->wrapper[1], '#') .'#', '', $html);
				$html = preg_replace('#'. preg_quote($this->wrapper[0], '#') .'.*?'. preg_quote($this->wrapper[1], '#') .'#', '', $html);
				$html = str_replace(['\\{\\{', '\\}\\}'], ['{{', '}}'], $html); // Unescape escaped wrappers
			}

			return $html;
		}
	}
