<?php

	function form_begin($name='', $method='post', $action=false, $multipart=false, $parameters='') {
		return  '<form'. (($name) ? ' name="'. functions::escape_html($name) .'"' : '') .' method="'. ((strtolower($method) == 'get') ? 'get' : 'post') .'" enctype="'. (($multipart == true) ? 'multipart/form-data' : 'application/x-www-form-urlencoded') .'" accept-charset="'. mb_http_output() .'"'. (($action) ? ' action="'. functions::escape_html($action) .'"' : '') . (($parameters) ? ' ' . $parameters : '') .'>';
	}

	function form_end() {
		return '</form>' . PHP_EOL;
	}

	function form_reinsert_value($name, $array_value=null) {

		if (empty($name)) return;

		foreach ([$_POST, $_GET] as $superglobal) {
			if (empty($superglobal)) continue;

			// Extract name parts
			$parts = preg_split('#[\]\[]+#', preg_replace('#\[\]$#', '', $name), -1, PREG_SPLIT_NO_EMPTY);

			// Get array node
			$node = $superglobal;
			foreach ($parts as $part) {
				if (!isset($node[$part])) continue 2;
				$node = $node[$part];
			}

			// Reinsert node value
			if (is_array($node) && $array_value !== null) {

				// Attempt reinserting a numerical indexed array value
				if (preg_match('#\[\]$#', $name)) {
					if (!is_array($node) || !in_array($array_value, $node)) continue;
					return $array_value;

				// Reinsert a defined key array value
				} else {
					if ($array_value != $node) continue;
					return $array_value;
				}
			}

			if (!empty($node) || $node != '') return $node;
		}

		return '';
	}

	function form_button($name, $value, $type='submit', $parameters='', $fonticon='') {

		if (!is_array($value)) {
			$value = [$value, $value];
		}

		return '<button'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="btn btn-default"' : '') .' type="'. functions::escape_html($type) .'" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($value[0]) .'"'. (($parameters) ? ' '.$parameters : '') .'>'. ((!empty($fonticon)) ? functions::draw_fonticon($fonticon) . ' ' : '') . (isset($value[1]) ? $value[1] : $value[0]) .'</button>';
	}

	function form_input_captcha($name, $id, $parameters='') {

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-text" style="padding: 0;">'. functions::captcha_generate(100, 40, 4, $id, 'numbers', 'align="absbottom"') .'</span>' . PHP_EOL
				 . '  ' . form_input_text('captcha', '', $parameters . ' autocomplete="off" style="font-size: 24px; padding: 0; text-align: center;"') . PHP_EOL
				 . '</div>';
	}

	function form_checkbox($name, $value, $input=true, $parameters='') {

		if (is_array($value)) {

			if ($input === true) {
				$input = form_reinsert_value($name, $value[0]);
			}

			return '<label'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-check"' : '') .'>' . PHP_EOL
			. '  <input type="checkbox" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($value[0]) .'" '. (!strcmp($input, $value[0]) ? ' checked' : '') . (($parameters) ? ' ' . $parameters : '') .' />' . PHP_EOL
			. '  ' . (isset($value[1]) ? $value[1] : $value[0]) . PHP_EOL
			. '</label>';
		}

		if ($input === true) {
			$input = form_reinsert_value($name, $value);
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-check"' : '') .' type="checkbox" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($value) .'" '. (!strcmp($input, $value) ? ' checked' : '') . (($parameters) ? ' ' . $parameters : '') .' />';
	}

	function form_input_code($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<textarea'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-code"' : '') .' name="'. functions::escape_html($name) .'"'. (($parameters) ? ' '.$parameters : '') .'>'. functions::escape_html($input) .'</textarea>';
	}

	function form_input_color($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="color" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_csv($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		if (!$csv = functions::csv_decode($input)) {
			return form_textarea($name, $input, $parameters);
		}

		$columns = array_keys($csv[0]);

		$html = '<table class="table table-striped table-hover data-table" data-toggle="csv">' . PHP_EOL
					. '  <thead>' . PHP_EOL
					. '    <tr>' . PHP_EOL;

		foreach ($columns as $column) {
			$html .= '      <th>'. $column .'</th>' . PHP_EOL;
		}

		$html .= '      <th><a class="add-column" href="#">'. functions::draw_fonticon('fa-plus', 'style="color: #6c6;"') .'</a></th>' . PHP_EOL
					 . '    </tr>' . PHP_EOL
					 . '  </thead>' . PHP_EOL
					 . '  <tbody>' . PHP_EOL;

		foreach ($csv as $line => $row) {
			$html .= '    <tr>' . PHP_EOL;
			foreach ($columns as $column) {
				$html .= '      <td contenteditable>'. $row[$column] .'</td>' . PHP_EOL;
			}
			$html .= '      <td><a class="btn btn-default btn-sm remove" href="#">'. functions::draw_fonticon('fa-times', 'style="color: #d33"') .'</a></td>' . PHP_EOL
						 . '    </tr>' . PHP_EOL;
		}

		$html .= '  </tbody>' . PHP_EOL
					 . '  <tfoot>' . PHP_EOL
					 . '    <tr>' . PHP_EOL
					 . '      <td colspan="'. (count($columns)+1) .'"><a class="add-row" href="#">'. functions::draw_fonticon('fa-plus', 'style="color: #6c6;"') .'</a></td>' . PHP_EOL
					 . '    </tr>' . PHP_EOL
					 . '  </tfoot>' . PHP_EOL
					 . '</table>' . PHP_EOL
					 . PHP_EOL
					 . form_textarea($name, $input, 'style="display: none;"');

		document::$snippets['javascript']['table2csv'] =
<<<END
$('table[data-toggle="csv"]').on('click', '.remove', function(e) {
	e.preventDefault();
	let parent = $(this).closest('tbody');
	$(this).closest('tr').remove();
	$(parent).trigger('keyup');
});

$('table[data-toggle="csv"] .add-row').click(function(e) {
	e.preventDefault();
	let n = $(this).closest('table').find('thead th:not(:last-child)').length;
	$(this).closest('table').find('tbody').append(
		'<tr>' + ('<td contenteditable></td>'.repeat(n)) + '<td><a class="btn btn-default btn-sm remove" href="#"><i class="fa fa-times" style="color: #d33;"></i></a></td>' +'</tr>'
	).trigger('keyup');
});

$('table[data-toggle="csv"] .add-column').click(function(e) {
	e.preventDefault();
	let table = $(this).closest('table');
	let title = prompt("<?php echo language::translate('title_column_title', 'Column Title'); ?>");
	if (!title) return;
	$(table).find('thead tr th:last-child:last-child').before('<th>'+ title +'</th>');
	$(table).find('tbody tr td:last-child:last-child').before('<td contenteditable></td>');
	$(table).find('tfoot tr td').attr('colspan', $(this).closest('table').find('tfoot tr td').attr('colspan') + 1);
	$(this).trigger('keyup');
});

$('table[data-toggle="csv"]').keyup(function(e) {
	 let csv = $(this).find('thead tr, tbody tr').map(function (i, row) {
			return $(row).find('th:not(:last-child),td:not(:last-child)').map(function (j, col) {
				let text = \$(col).text();
				if (/("|,)/.test(text)) {
					return '"'+ text.replace(/"/g, '""') +'"';
				} else {
					return text;
				}
			}).get().join(',');
		}).get().join("\\r\\n");
	$(this).next('textarea').val(csv);
});
END;

		return $html;
	}

	function form_input_date($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		if (!empty($input) && !in_array(substr($input, 0, 10), ['0000-00-00', '1970-01-01'])) {
			$input = date('Y-m-d', strtotime($input));
		} else {
			$input = '';
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="date" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'" placeholder="YYYY-MM-DD"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_datetime($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		if (!empty($input) && !in_array(substr($input, 0, 10), ['0000-00-00', '1970-01-01'])) {
			$input = date('Y-m-d\TH:i', strtotime($input));
		} else {
			$input = '';
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="datetime-local" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'" placeholder="YYYY-MM-DD [hh:nn]"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_decimal($name, $input=true, $decimals=2, $parameters='') {

		if (count($args = func_get_args()) > 4) {
			trigger_error('Passing min and max separate parameters in form_input_decimal() is deprecated. Instead define min="0" max="999" in $parameters', E_USER_DEPRECATED);
			if (isset($args[5])) $parameters = $args[5];
			if (isset($args[3])) $parameters .= ($parameters ? ' ' : '') . 'min="'. (int)$args[3] .'"';
			if (isset($args[4])) $parameters .= ($parameters ? ' ' : '') . 'min="'. (int)$args[4] .'"';
		}

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		if ($input != '') {
			$input = number_format((float)$input, (int)$decimals, '.', '');
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="number" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'" data-decimals="'. $decimals .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_dropdown($name, $options=[], $input=true, $parameters='') {

		$html = '<div class="dropdown"'. (($parameters) ? ' ' . $parameters : '') .'>' . PHP_EOL
					. '  <div class="form-select" data-toggle="dropdown">-- '. language::translate('title_select', 'Select') .' --</div>' . PHP_EOL
					. '  <ul class="dropdown-menu">' . PHP_EOL;

		$is_numerical_index = (array_keys($options) === range(0, count($options) - 1));

		foreach ($options as $key => $option) {

			if (!is_array($option)) {
				if ($is_numerical_index) {
					$option = [$option, $option];
				} else {
					$option = [$key, $option];
				}
			}

			if (preg_match('#\[\]$#', $name)) {
				$html .= '<li class="option">' . functions::form_checkbox($name, $option, $input, isset($option[2]) ? $option[2] : '') .'</li>' . PHP_EOL;
			} else {
				$html .= '<li class="option">' . functions::form_radio_button($name, $option, $input, isset($option[2]) ? $option[2] : '') .'</li>' . PHP_EOL;
			}
		}

		$html .= '  </ul>' . PHP_EOL
					 . '</div>';

		return $html;
	}

	function form_input_email($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-icon">'. functions::draw_fonticon('fa-envelope-o fa-fw') .'</span>' . PHP_EOL
				 . '  <input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="email" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />'
				 . '</div>';
	}

	function form_input_file($name, $parameters='') {
		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="file" name="'. functions::escape_html($name) .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_fonticon($name, $input=true, $type='text', $icon='', $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-icon">'. functions::draw_fonticon($icon) .'</span>' . PHP_EOL
				 . '  <input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="'. functions::escape_html($type) .'" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />' . PHP_EOL
				 . '</div>';
	}

	function form_input_hidden($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<input type="hidden" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_image($name, $src, $parameters='') {
		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="image" name="'. functions::escape_html($name) .'" src="'. functions::escape_html($src) .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input($name, $input=true, $type='text', $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="'. functions::escape_html($type) .'" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_link_button($url, $title, $parameters='', $fonticon='') {
		return '<a '. (!preg_match('#class="([^"]+)?"#', $parameters) ? 'class="btn btn-default"' : '') .' href="'. functions::escape_html($url) .'"'. (($parameters) ? ' '.$parameters : '') .'>'. (!empty($fonticon) ? functions::draw_fonticon($fonticon) . ' ' : '') . $title .'</a>';
	}

	function form_input_month($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		if (!in_array(substr($input, 0, 7), ['', '0000-00', '1970-00', '1970-01'])) {
			$input = date('Y-m', strtotime($input));
		} else {
			$input = '';
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="month" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'" maxlength="7" pattern="[0-9]{4}-[0-9]{2}" placeholder="YYYY-MM"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_number($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = (int)form_reinsert_value($name);
		}

		if ($input != '') {
			$input = round($input);
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="number" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'" step="1"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_password($name, $input='', $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-icon">'. functions::draw_fonticon('fa-key fa-fw') .'</span>' . PHP_EOL
				 . '  <input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="password" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />'
				 . '</div>';
	}

	function form_input_phone($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-icon">'. functions::draw_fonticon('fa-phone fa-fw') .'</span>' . PHP_EOL
				 . '  <input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="tel" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'" pattern="^\+?([0-9]|-| )+$"'. (($parameters) ? ' '.$parameters : '') .' />'
				 . '</div>';
	}

	function form_radio_button($name, $value, $input=true, $parameters='') {

		if (is_array($value)) {

			if ($input === true) {
				$input = form_reinsert_value($name, $value[0]);
			}

			return '<label'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-check"' : '') .'>' . PHP_EOL
					. '  <input type="radio" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($value[0]) .'" '. (!strcmp($input, $value[0]) ? ' checked' : '') . (($parameters) ? ' ' . $parameters : '') .' />' . PHP_EOL
					. '  ' . (isset($value[1]) ? $value[1] : $value[0]) . PHP_EOL
					. '</label>';
		}

		if ($input === true) {
			$input = form_reinsert_value($name, $value);
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-check"' : '') .' type="radio" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($value) .'" '. (!strcmp($input, $value) ? ' checked' : '') . (($parameters) ? ' ' . $parameters : '') .' />';
	}

	function form_range_slider($name, $input=true, $min='', $max='', $step='', $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-range"' : '') .' type="range" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'" min="'. (float)$min .'" max="'. (float)$max .'" step="'. (float)$step .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_regional($name, $language_code='', $input=true, $type='text', $parameters='') {

		if (preg_match('#^[a-z]{2}$#', $name)) {
			trigger_error('Passing $language code as 1st parameter in form_input_regional_input() is deprecated. Instead, use form_input_regional_input($name, $language_code, $input, $parameters)', E_USER_DEPRECATED);
			list($name, $language_code) = [$language_code, $name];
		}

		if (empty($language_code)) {
			$language_code = settings::get('site_language_code');
		}

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-text" style="font-family: monospace;" title="'. functions::escape_html(language::$languages[$language_code]['name']) .'">'. functions::escape_html($language_code) .'</span>' . PHP_EOL
				 . '  <input class="form-input" name="'. functions::escape_html($name) .'" type="'. functions::escape_html($type) .'" value="'. functions::escape_html($input) .'" />' . PHP_EOL
				 . '</div>';
	}

	function form_input_regional_text($name, $language_code='', $input=true, $parameters='') {

		if (empty($language_code)) {
			$language_code = settings::get('site_language_code');
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-text" style="font-family: monospace;" title="'. functions::escape_html(language::$languages[$language_code]['name']) .'">'. functions::escape_html($language_code) .'</span>' . PHP_EOL
				 . '  ' . form_input_text($name, $input, $parameters) . PHP_EOL
				 . '</div>';
	}

	function form_regional_textarea($name, $language_code='', $input=true, $parameters='') {

		if (preg_match('#^[a-z]{2}$#', $name)) {
			trigger_error('Passing language code as 1st parameter in form_regional_textarea() is deprecated. Instead, use form_regional_textarea($name, $language_code, $input, $parameters)', E_USER_DEPRECATED);
			list($name, $language_code) = [$language_code, $name];
		}

		if (empty($language_code)) {
			$language_code = settings::get('site_language_code');
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-text" style="font-family: monospace;" title="'. functions::escape_html(language::$languages[$language_code]['name']) .'">'. functions::escape_html($language_code) .'</span>' . PHP_EOL
				 . '  ' . form_textarea($name, $input, $parameters) . PHP_EOL
				 . '</div>';
	}

	function form_input_regional_wysiwyg($name, $language_code='', $input=true, $parameters='') {

		if (preg_match('#^[a-z]{2}$#', $name)) {
			trigger_error('Passing language code as 1st parameter in form_input_regional_wysiwyg() is deprecated. Instead, use form_input_regional_wysiwyg($name, $language_code, $input, $parameters)', E_USER_DEPRECATED);
			list($name, $language_code) = [$language_code, $name];
		}

		if (empty($language_code)) {
			$language_code = settings::get('site_language_code');
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-text" style="font-family: monospace;" title="'. functions::escape_html(language::$languages[$language_code]['name']) .'">'. functions::escape_html($language_code) .'</span>' . PHP_EOL
				 . '  ' . form_input_wysiwyg($name, $input, $parameters) . PHP_EOL
				 . '</div>';
	}

	function form_input_search($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-icon">'. functions::draw_fonticon('fa-search fa-fw') .'</span>' . PHP_EOL
				 . '  <input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="search" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />' . PHP_EOL
				 . '</div>';
	}

	function form_select($name, $options=[], $input=true, $parameters='') {

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		}

		$html = '<select '. (!preg_match('#class="([^"]+)?"#', $parameters) ? 'class="form-select"' : '') .' name="'. functions::escape_html($name) .'"'. (($parameters) ? ' ' . $parameters : '') .'>' . PHP_EOL;

		$is_numerical_index = (array_keys($options) === range(0, count($options) - 1));

		foreach ($options as $key => $option) {

			if (!is_array($option)) {
				if ($is_numerical_index) {
					$option = [$option];
				} else {
					$option = [$key, $option];
				}
			}

			if ($input === true) {
				$option_input = form_reinsert_value($name, $option[0]);
			} else {
				$option_input = $input;
			}

			$html .= '  <option value="'. functions::escape_html($option[0]) .'"'. (!strcmp((string)$option[0], (string)$option_input) ? ' selected' : '') . ((isset($option[2])) ? ' ' . $option[2] : '') . '>'. (isset($option[1]) ? $option[1] : $option[0]) .'</option>' . PHP_EOL;
		}

		$html .= '</select>';

		return $html;
	}

	function form_select_multiple($name, $options=[], $input=true, $parameters='') {

		$html = '<div class="form-input"' . (($parameters) ? ' ' . $parameters : '') .'>' . PHP_EOL;

		$is_numerical_index = (array_keys($options) === range(0, count($options) - 1));

		foreach ($options as $key => $option) {

			if (!is_array($option)) {
				if ($is_numerical_index) {
					$option = [$option, $option];
				} else {
					$option = [$key, $option];
				}
			}

			$html .= form_checkbox($name, $option, $input, isset($option[2]) ? $option[2] : '');
		}

		$html .= '</div>';

		return $html;
	}

	function form_select_optgroup($name, $groups=[], $input=true, $parameters='') {

		if (count($args = func_get_args()) > 3 && is_bool($args[3])) {
			trigger_error('Passing $multiple as 4th parameter in form_select_optgroup() is deprecated as determined by input name instead.', E_USER_DEPRECATED);
			if (isset($args[4])) $parameters = $args[3];
		}

		if (!is_array($groups)) {
			 $groups = [$groups];
		}

		$html = '<select class="form-select" name="'. functions::escape_html($name) .'"'. (preg_match('#\[\]$#', $name) ? ' multiple' : '') . (($parameters) ? ' ' . $parameters : '') .'>' . PHP_EOL;

		foreach ($groups as $group) {
			$html .= '    <optgroup label="'. $group['label'] .'">' . PHP_EOL;

			$is_numerical_index = (array_keys($group['options']) === range(0, count($group['options']) - 1));

			foreach ($group['options'] as $key => $option) {

				if (!is_array($option)) {
					if ($is_numerical_index) {
						$option = [$option, $option];
					} else {
						$option = [$key, $option];
					}
				}

				if ($input === true) {
					$option_input = form_reinsert_value($name, $option[0]);
				} else {
					$option_input = $input;
				}

				$html .= '      <option value="'. functions::escape_html($option[0]) .'"'. (($option[0] == $option_input) ? ' selected' : '') . ((isset($option[2])) ? ' ' . $option[2] : '') . '>'. $option[1] .'</option>' . PHP_EOL;
			}

			$html .= '    </optgroup>' . PHP_EOL;
		}

		$html .= '  </select>';

		return $html;
	}

	function form_switch($name, $value, $label, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<label><input '. (!preg_match('#class="([^"]+)?"#', $parameters) ? 'class="form-switch"' : '') .' name="'. functions::escape_html($name) .'"'. (($parameters) ? ' '.$parameters : '') .'>'. functions::escape_html($label) .'</label>';
	}

	function form_textarea($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<textarea'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' name="'. functions::escape_html($name) .'"'. (($parameters) ? ' '.$parameters : '') .'>'. functions::escape_html($input) .'</textarea>';
	}

	function form_input_text($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="text" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_time($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="time" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_toggle($name, $type='t/f', $input=true, $parameters='') {

		if (strpos($input, '/') === true) {
			trigger_error('Passing type as 3rd parameter in form_toggle() is deprecated. Use instead form_toggle($name, $type, $input, $parameters)', E_USER_DEPRECATED);
			list($type, $input) = [$input, $type];
		}

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		$input = preg_match('#^(1|active|enabled|on|true|yes)$#i', $input) ? '1' : '0';

		switch ($type) {
			case 'a/i':
				$options = [
					'1' => language::translate('title_active', 'Active'),
					'0' => language::translate('title_inactive', 'Inactive'),
				];
				break;

			case 'e/d':
				$options = [
					'1' => language::translate('title_enabled', 'Enabled'),
					'0' => language::translate('title_disabled', 'Disabled'),
				];
				break;

			case 'y/n':
				$options = [
					'1' => language::translate('title_yes', 'Yes'),
					'0' => language::translate('title_no', 'No'),
				];
				break;

			case 'o/o':
				$options = [
					'1' => language::translate('title_on', 'On'),
					'0' => language::translate('title_off', 'Off'),
				];
				break;

			case 't/f':
			default:
				$options = [
					'1' => language::translate('title_true', 'True'),
					'0' => language::translate('title_false', 'False'),
				];
				break;
		}

		return form_toggle_buttons($name, $options, $input, $parameters);
	}

	function form_toggle_buttons($name, $options, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		$html = '<div '. (!preg_match('#class="([^"]+)?"#', $parameters) ? 'class="btn-group btn-block btn-group-inline"' : '') .' data-toggle="buttons"'. (($parameters) ? ' '.$parameters : '') .'>'. PHP_EOL;

		$is_numerical_index = (array_keys($options) === range(0, count($options) - 1));

		foreach ($options as $key => $option) {

			if (!is_array($option)) {
				if ($is_numerical_index) {
					$option = [$option, $option];
				} else {
					$option = [$key, $option];
				}
			}

			$html .= '  <label class="btn btn-default'. ($input == $option[0] ? ' active' : '') .'">' . PHP_EOL
						 . '    <input type="radio" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($option[0]) .'"'. (!strcmp($input, $option[0]) ? ' checked' : '') . (!empty($option[2]) ? ' '. $option[2] : '') .' />'. $option[1]
						 . '  </label>'. PHP_EOL;
		}

		$html .= '</div>';

		return $html;
	}

	function form_input_url($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="url" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_input_username($name, $input=true, $parameters='') {
		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<div class="input-group">' . PHP_EOL
				 . '  <span class="input-group-icon">'. functions::draw_fonticon('fa-user fa-fw') .'</span>' . PHP_EOL
				 . '  <input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="text" name="'. functions::escape_html($name) .'" value="'. functions::escape_html($input) .'"'. (($parameters) ? ' '.$parameters : '') .' />'
				 . '</div>';
	}

	function form_input_wysiwyg($name, $input=true, $parameters='') {

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		document::$snippets['head_tags']['trumbowyg'] = '<link href="'. document::href_rlink('app://assets/trumbowyg/ui/trumbowyg.min.css') .'" rel="stylesheet" />' . PHP_EOL
																									. '<link href="'. document::href_rlink('app://assets/trumbowyg/plugins/colors/ui/trumbowyg.colors.min.css') .'" rel="stylesheet" />'
																									. '<link href="'. document::href_rlink('app://assets/trumbowyg/plugins/table/ui/trumbowyg.table.min.css') .'" rel="stylesheet" />';

		document::$snippets['foot_tags']['trumbowyg'] = '<script src="'. document::href_rlink('app://assets/trumbowyg/trumbowyg.min.js') .'"></script>' . PHP_EOL
																									. ((language::$selected['code'] != 'en') ? '<script src="'. document::href_rlink('app://assets/trumbowyg/langs/'. language::$selected['code'] .'.min.js') .'"></script>' . PHP_EOL : '')
																									. '<script src="'. document::href_rlink('app://assets/trumbowyg/plugins/colors/trumbowyg.colors.min.js') .'"></script>' . PHP_EOL
																									. '<script src="'. document::href_rlink('app://assets/trumbowyg/plugins/upload/trumbowyg.upload.min.js') .'"></script>' . PHP_EOL
																									. '<script src="'. document::href_rlink('app://assets/trumbowyg/plugins/table/trumbowyg.table.min.js') .'"></script>';

		document::$snippets['javascript'][] = '  $(\'textarea[name="'. $name .'"]\').trumbowyg({' . PHP_EOL
																				. '    btns: [["viewHTML"], ["formatting"], ["strong", "em", "underline", "del"], ["foreColor", "backColor"], ["link"], ["insertImage"], ["table"], ["justifyLeft", "justifyCenter", "justifyRight"], ["lists"], ["preformatted"], ["horizontalRule"], ["removeformat"], ["fullscreen"]],' . PHP_EOL
																				. '    btnsDef: {' . PHP_EOL
																				. '      lists: {' . PHP_EOL
																				. '        dropdown: ["unorderedList", "orderedList"],' . PHP_EOL
																				. '        title: "Lists",' . PHP_EOL
																				. '        ico: "unorderedList",' . PHP_EOL
																				. '      }' . PHP_EOL
																				. '    },' . PHP_EOL
																				. '    plugins: {' . PHP_EOL
																				. '      upload: {' . PHP_EOL
																				. '        serverPath: "'. document::href_rlink('app://assets/trumbowyg/plugins/upload/trumbowyg.upload.php') .'",' . PHP_EOL
																				. '      }' . PHP_EOL
																				. '    },' . PHP_EOL
																				. '    lang: "'. language::$selected['code'] .'",' . PHP_EOL
																				. '    autogrowOnEnter: true,' . PHP_EOL
																				. '    imageWidthModalEdit: true,' . PHP_EOL
																				. '    removeformatPasted: true,' . PHP_EOL
																				. '    semantic: false' . PHP_EOL
																				. '  });';

		return '<textarea name="'. functions::escape_html($name) .'"'. (($parameters) ? ' '.$parameters : '') .'>'. functions::escape_html($input) .'</textarea>';
	}

	##################################
	# Platform specific form helpers #
	##################################

	function form_function($name, $function, $input=true, $parameters='') {

		if (preg_match('#\)$#', $name)) {
			trigger_error('Passing function as 1st parameter in form_function() is deprecated. Instead, use form_function($name, $function, $input, $parameters)', E_USER_DEPRECATED);
			list($name, $function) = [$function, $name];
		}

		if (!preg_match('#(\w*)\((.*?)\)$#i', $function, $matches)) {
			trigger_error('Invalid function name ('. $function .')', E_USER_WARNING);
		}

		$options = [];
		if (!empty($matches[2])) {
			$options = preg_split('#\s*,\s*#', $matches[2], -1, PREG_SPLIT_NO_EMPTY);
			$options = array_map(function($s){ return trim($s, '\'" '); }, $options);
		}

		switch ($matches[1]) {

			case 'administrator':
			case 'administrators':
				return form_select_administrator($name, $input, $parameters);

			case 'date':
				return form_input_date($name, $input, $parameters);

			case 'datetime':
				return form_input_datetime($name, $input, $parameters);

			case 'decimal':
			case 'float':
				return form_input_decimal($name, $input, 2, $parameters);

			case 'number':
			case 'int':
				return form_input_number($name, $input, $parameters);

			case 'checkbox':
				$html = '';
				foreach ($options as $option) {
					$html .= form_checkbox($name, [$option, $option], $input, $parameters);
				}
				return $html;

			case 'color':
				return form_input_color($name, $input, $parameters);

			case 'text':
				return form_input_text($name, $input, $parameters);

			case 'password':
				return form_input_password($name, $input, $parameters);

			case 'mediumtext':
			case 'textarea':
				return form_textarea($name, $input, $parameters . ' rows="5"');

			case 'bigtext':
				return form_textarea($name, $input, $parameters . ' rows="10"');

			case 'csv':
				return form_textarea($name, $input, true, $parameters);

			case 'email':
				return form_input_email($name, $input, $parameters);

			case 'file':
			case 'files':
				return form_select_file($name, $options[0], $input, $parameters);

			case 'language':
			case 'languages':
				return form_select_language($name, $input, $parameters);

			case 'page':
			case 'pages':
				return form_select_page($name, $input, $parameters);

			case 'password':
				return functions::form_input_password($name, $input);

			case 'phone':
				return functions::form_input_phone($name, $input);

			case 'radio':
				$html = '';
				foreach ($options as $option) {
					$html .= form_radio_button($name, [$option, $option], $input, $parameters);
				}
				return $html;

			case 'regional_text':
				$html = '';
				foreach (array_keys(language::$languages) as $language_code) {
					$html .= form_input_regional_text($name.'['. $language_code.']', $language_code, $input, $parameters);
				}
				return $html;

			case 'regional_textarea':
				$html = '';
				foreach (array_keys(language::$languages) as $language_code) {
					$html .= form_regional_textarea($name.'['. $language_code.']', $language_code, $input, $parameters);
				}
				return $html;

			case 'regional_wysiwyg':
				$html = '';
				foreach (array_keys(language::$languages) as $language_code) {
					$html .= form_input_regional_wysiwyg($name.'['. $language_code.']', $language_code, $input, $parameters);
				}
				return $html;

			case 'page':
			case 'pages':
				return form_select_page($name, $input, $parameters);

			case 'radio':
				$html = '';
				for ($i=0; $i<count($options); $i++) {
					$html .= '<div class="radio"><label>'. form_radio_button($name, $options[$i], $input, $parameters) .' '. $options[$i] .'</label></div>';
				}
				return $html;

			case 'select':
				for ($i=0; $i<count($options); $i++) $options[$i] = [$options[$i]];
				return form_select($name, $options, $input, $parameters);

			case 'timezone':
			case 'timezones':
				return form_select_timezone($name, $input, $parameters);

			case 'template':
			case 'templates':
				return form_select_template($name, $input, $parameters);

			case 'time':
				return form_input_time($name, $input, $parameters);

			case 'toggle':
				return form_toggle($name, fallback($options[0], null), $input);

			case 'upload':
				return form_input_file($name, $parameters);

			case 'url':
				return form_input_url($name, $input, $parameters);

			case 'user':
			case 'users':
				return form_select_user($name, $input, $parameters);

			case 'wysiwyg':
				return form_input_regional_wysiwyg($input, $name, $parameters);

			case 'zone':
			case 'zones':
				$option = !empty($options) ? $options[0] : '';
				//if (empty($option)) $option = settings::get('site_country_code');
				return form_select_zone($name, $option, $input, $parameters);

			default:
				trigger_error('Unknown function name ('. $function .')', E_USER_WARNING);
				return form_input_text($name, $input, $parameters);
				break;
		}
	}

	function form_select_administrator($name, $input=true, $parameters='') {

		if (count($args = func_get_args()) > 2 && is_bool($args[2])) {
			trigger_error('Passing $multiple as 3rd parameter in form_select_administrator() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[3])) $parameters = $args[2];
		}

		$administrators_query = database::query(
			"select id, username from ". DB_TABLE_PREFIX ."administrators
			order by username;"
		);

		$options = [];
		while ($administrator = database::fetch($administrators_query)) {
			$options[] = [$administrator['id'], $administrator['username']];
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_country($name, $input=true, $parameters='') {

		if (count($args = func_get_args()) > 2 && is_bool($args[2])) {
			trigger_error('Passing $multiple as 3rd parameter in form_select_country() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[3])) $parameters = $args[2];
		}

		if ($input === true) {
			$input = form_reinsert_value($name);

			if ($input == '' && file_get_contents('php://input') == '') {
				$input = settings::get('default_country_code');
			}
		}

		switch ($input) {
			case 'customer_country_code':
				$input = customer::$data['country_code'];
				break;
			case 'default_country_code':
				$input = settings::get('default_country_code');
				break;
			case 'site_country_code':
				$input = settings::get('site_country_code');
				break;
		}

		$countries_query = database::query(
			"select * from ". DB_TABLE_PREFIX ."countries
			where status
			order by name asc;"
		);

		$options = [];
		while ($country = database::fetch($countries_query)) {
			$options[] = [$country['iso_code_2'], $country['name'], 'data-tax-id-format="'. $country['tax_id_format'] .'" data-postcode-format="'. $country['postcode_format'] .'" data-phone-code="'. $country['phone_code'] .'"'];
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_encoding($name, $input=true, $parameters='') {

		if (count($args = func_get_args()) > 2 && is_bool($args[2])) {
			trigger_error('Passing $multiple as 3rd parameter in form_select_encoding() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[3])) $parameters = $args[2];
		}

		$encodings = [
			'BIG-5',
			'CP50220',
			'CP50221',
			'CP50222',
			'CP51932',
			'CP850',
			'CP932',
			'EUC-CN',
			'EUC-JP',
			'EUC-KR',
			'EUC-TW',
			'GB18030',
			'ISO-8859-1',
			'ISO-8859-2',
			'ISO-8859-3',
			'ISO-8859-4',
			'ISO-8859-5',
			'ISO-8859-6',
			'ISO-8859-7',
			'ISO-8859-8',
			'ISO-8859-9',
			'ISO-8859-10',
			'ISO-8859-13',
			'ISO-8859-14',
			'ISO-8859-15',
			'ISO-8859-16',
			'KOI8-R',
			'KOI8-U',
			'SJIS',
			'UTF-8',
			'UTF-16',
			'Windows-1251',
			'Windows-1252',
			'Windows-1254',
		];

		$options = [];
		foreach ($encodings as $encoding) {
			$options[] = $encoding;
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_file($name, $parameters='') {

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple_files($name, $options, $input, $parameters);
		}

		if ($input === true) {
			$input = form_reinsert_value($name);
		}

		return '<div class="form-input"'. (($parameters) ? ' ' . $parameters : '') .'>' . PHP_EOL
				 . '  ' . form_input_hidden($name, true) . PHP_EOL
				 . '  <span class="value">'. (!empty($input) ? $input : '('. language::translate('title_none', 'None') .')') .'</span> <a href="'. document::href_ilink('b:files/file_picker') .'" data-toggle="lightbox" class="btn btn-default btn-sm" style="margin-inline-start: 5px;">'. language::translate('title_change', 'Change') .'</a>' . PHP_EOL
				 . '</div>';

		return '<input'. (!preg_match('#class="([^"]+)?"#', $parameters) ? ' class="form-input"' : '') .' type="file" name="'. functions::escape_html($name) .'"'. (($parameters) ? ' '.$parameters : '') .' />';
	}

	function form_select_multiple_files($name, $glob, $input=true, $parameters='') {

		if (!preg_match('#\[\]$#', $name)) {
			return form_select_file($name, $options, $input, $parameters);
		}

		$options = [];

		foreach (glob(FS_DIR_APP . $glob) as $file) {
			$file = preg_replace('#^'. preg_quote(FS_DIR_APP, '#') .'#', '', $file);
			if (is_dir(FS_DIR_APP . $file)) {
				$options[] = [basename($file).'/', $file.'/'];
			} else {
				$options[] = [basename($file), $file];
			}
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_language($name, $input=true, $parameters='') {

		if (count($args = func_get_args()) > 2 && is_bool($args[2])) {
			trigger_error('Passing $multiple as 3rd parameter in form_languages_list() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[3])) $parameters = $args[2];
		}

		$options = [];

		foreach (language::$languages as $language) {
			$options[] = [$language['code'], $language['name'], 'data-decimal-point="'. $language['decimal_point'] .'" data-thousands-sep="'. $language['thousands_sep'] .'"'];
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_mysql_collation($name, $input=true, $parameters='') {

		if (count($args = func_get_args()) > 2 && is_bool($args[2])) {
			trigger_error('Passing $multiple as 3rd parameter in form_select_mysql_collation() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[3])) $parameters = $args[2];
		}

		$collations_query = database::query(
			"select * from information_schema.COLLATIONS
			where CHARACTER_SET_NAME = '". database::input(DB_CONNECTION_CHARSET) ."'
			order by COLLATION_NAME;"
		);

		$options = [];
		while ($row = database::fetch($collations_query)) {
			$options[] = $row['COLLATION_NAME'];
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_page($name, $input=true, $parameters='') {

		if (count($args = func_get_args()) > 2 && is_bool($args[2])) {
			trigger_error('Passing $multiple as 3rd parameter in form_select_page() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[3])) $parameters = $args[2];
		}

		$iterator = function($parent_id, $level) use (&$iterator) {

			$options = [];

			if (empty($parent_id)) {
				$options[] = ['0', '['.language::translate('title_root', 'Root').']'];
			}

			$pages_query = database::query(
				"select p.id, pi.title from ". DB_TABLE_PREFIX ."pages p
				left join ". DB_TABLE_PREFIX ."pages_info pi on (pi.page_id = p.id and pi.language_code = '". database::input(language::$selected['code']) ."')
				where p.parent_id = '". (int)$parent_id ."'
				order by p.priority asc, pi.title asc;"
			);

			while ($page = database::fetch($pages_query)) {

				$options[] = [$page['id'], str_repeat('&nbsp;&nbsp;&nbsp;', $level) . $page['title']];

				$sub_pages_query = database::query(
					"select id from ". DB_TABLE_PREFIX ."pages
					where parent_id = '". (int)$page['id'] ."'
					limit 1;"
				);

				$sub_options = $iterator($page['id'], $level+1);

				$options = array_merge($options, $sub_options);
			}

			return $options;
		};

		$options = $iterator(0, 1);

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_template($name, $input=true, $parameters='') {

		if (count($args = func_get_args()) > 2 && is_bool($args[2])) {
			trigger_error('Passing $multiple as 3rd parameter in form_select_template() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[3])) $parameters = $args[2];
		}

		$folders = functions::file_search('app://frontend/templates/*', GLOB_ONLYDIR);

		$options = [];
		foreach ($folders as $folder) {
			$options[] = basename($folder);
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_timezone($name, $input=true, $parameters='') {

		if (count($args = func_get_args()) > 2 && is_bool($args[2])) {
			trigger_error('Passing $multiple as 3rd parameter in form_select_timezone() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[3])) $parameters = $args[2];
		}

		$options = [];
		foreach (timezone_identifiers_list() as $timezone) {
			$timezone = explode('/', $timezone); // 0 => Continent, 1 => City

			if (in_array($timezone[0], ['Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific'])) {
				if (!empty($timezone[1])) {
					$options[] = implode('/', $timezone);
				}
			}
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_user($name, $input=true, $parameters='') {

		if (count($args = func_get_args()) > 2 && is_bool($args[2])) {
			trigger_error('Passing $multiple as 3rd parameter in form_select_user() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[3])) $parameters = $args[2];
		}

		$users_query = database::query(
			"select id, username from ". DB_TABLE_PREFIX ."users
			order by username;"
		);

		$options = [];
		while ($user = database::fetch($users_query)) {
			$options[] = [$user['id'], $user['username']];
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
			return form_select($name, $options, $input, $parameters);
		}
	}

	function form_select_zone($name, $country_code='', $input=true, $parameters='', $preamble='none') {

		if (preg_match('#^([A-Z]{2}|default_country_code|store_country_code)$#', $name)) {
			trigger_error('form_select_zone() no longer takes country code as 1st parameter. Instead, use form_select_zone($name, $country_code, $input)', E_USER_DEPRECATED);
			list($name, $country_code) = [$country_code, $name];
		}

		if (count($args = func_get_args()) > 3 && is_bool($args[3])) {
			trigger_error('Passing $multiple as 4th parameter in form_select_zone() is deprecated as instead determined by input name.', E_USER_DEPRECATED);
			if (isset($args[4])) $parameters = $args[3];
		}

		if ($country_code == '') $country_code = settings::get('store_country_code');
		if ($country_code == 'default_country_code') $country_code = settings::get('default_country_code');
		if ($country_code == 'store_country_code') $country_code = settings::get('store_country_code');

		$zones_query = database::query(
			"select * from ". DB_TABLE_PREFIX ."zones
			where country_code = '". database::input($country_code) ."'
			order by name asc;"
		);

		$options = [];

		if (!database::num_rows($zones_query)) {
			$parameters .= ' disabled';
		}

		while ($zone = database::fetch($zones_query)) {
			$options[] = [$zone['code'], $zone['name']];
		}

		if (preg_match('#\[\]$#', $name)) {
			return form_select_multiple($name, $options, $input, $parameters);
		} else {
			switch($preamble) {
				case 'all':
					array_unshift($options, ['', '-- '. language::translate('title_all_zones', 'All Zones') . ' --']);
					break;
				case 'select':
					array_unshift($options, ['', '-- '. language::translate('title_select', 'Select') . ' --']);
					break;
			}
			return form_select($name, $options, $input, $parameters);
		}
	}
