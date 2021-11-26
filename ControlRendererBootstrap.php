<?php
namespace FA\Theme\Bootstrap;

class ControlRendererBootstrap extends \ControlRenderer
{
	private $tableContext = '';

	function start_form($multi = false, $dummy = false, $action = "", $name = "")
	{
		// $dummy - left for compatibility with 2.0 API
		$context = array(
			'name' => ($name != '') ? $name : '',
			'action' => ($action != '') ? $action : $_SERVER['PHP_SELF'],
			'multi' => $multi,
		);
		echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'form', $context);
	}

	// ---------------------------------------------------------------------------------
	function end_form($breaks = 0)
	{
		global $Ajax;

		$_SESSION['csrf_token'] = hash('sha256', uniqid(mt_rand(), true));
// 		if ($breaks)
// 			br($breaks);
		hidden('_focus');
		hidden('_modified', get_post('_modified', 0));
		hidden('_token', $_SESSION['csrf_token']);

		View::get()->render(); // In case there are any controls between the end of table and the end of form.
		echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'form_end', array());

		$Ajax->activate('_token');
	}

	function start_table($class = false, $extra = "", $padding = '2', $spacing = '0')
	{
		return;
		$this->tableContext = $class;
		$classAttribute = '';
		switch ($class) {
			case TABLESTYLE_NOBORDER:
				$classAttribute = 'tablestyle_noborder';
				break;
			case TABLESTYLE2:
				$classAttribute = 'tablestyle2';
				break;
			case TABLESTYLE:
				$classAttribute = 'tablestyle';
				break;
		}
		$context = array(
			'class' => $classAttribute,
			'extra' => $extra,
			'padding' => $padding,
			'spacing' => $spacing,
		);
		switch ($class) {
			case TABLESTYLE_NOBORDER:
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'table', $context);
				break;
			case TABLESTYLE2:
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'table2', $context);
				break;
			case TABLESTYLE:
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'table', $context);
				break;
			default:
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'table', $context);
		}
	}

	function end_table($breaks = 0)
	{
		View::get()->tableEnd();
		View::get()->render();
// 		if ($breaks)
// 			br($breaks);
// 		$this->tableContext = '';
	}

	function start_outer_table($class = false, $extra = "", $padding = '2', $spacing = '0', $br = false)
	{
// 		if ($br)
// 			br();
// 		start_table($class, $extra, $padding, $spacing);
// 		echo "<tr valign=top><td>\n"; // outer table
	}

	function table_section($number = 1, $width = false)
	{
		if ($number > 1) {
			View::get()->layoutHintColumn($number);
		}
	}

	function end_outer_table($breaks = 0, $close_table = true)
	{
		View::get()->render();
// 		if ($close_table)
// 			echo "</table>\n";
// 		echo "</td></tr>\n";
// 		end_table($breaks);
	}
	//
	// outer table spacer
	//
	function vertical_space($params = '')
	{
// 		echo "</td></tr><tr><td valign=center $params>";
	}

	function meta_forward($forward_to, $params = "", $timeout = 0)
	{
		parent::meta_forward($forward_to, $params, $timeout);
	}

	function hyperlink_back($center = true, $no_menu = true, $type_no = 0, $trans_no = 0, $final = false)
	{
		global $path_to_root;

		if ($center)
			echo "<center>";
		$id = 0;
		if ($no_menu && $trans_no != 0) {
			include_once ($path_to_root . "/admin/db/attachments_db.inc");
			$id = has_attachment($type_no, $trans_no);
			$attach = get_attachment_string($type_no, $trans_no);
			echo $attach;
		}
		$width = ($id != 0 ? "30%" : "20%");
		start_table(false, "width=$width");
		start_row();
		if ($no_menu) {
			echo "<td align=center><a href='javascript:window.print();'>" . _("Print") . "</a></td>\n";
		}
		echo "<td align=center><a href='javascript:goBack(" . ($final ? '-2' : '') . ");'>" . ($no_menu ? _("Close") : _("Back")) . "</a></td>\n";
		end_row();
		end_table();
		if ($center)
			echo "</center>";
		echo "<br>";
	}

	function hyperlink_no_params($target, $label, $center = true)
	{
		$id = default_focus();
		$pars = access_string($label);
		if ($target == '')
			$target = $_SERVER['PHP_SELF'];
		if ($center)
			echo "<br><center>";
		echo "<a href='$target' id='$id' $pars[1]>$pars[0]</a>\n";
		if ($center)
			echo "</center>";
	}

	function hyperlink_no_params_td($target, $label)
	{
		echo "<td>";
		hyperlink_no_params($target, $label);
		echo "</td>\n";
	}

	function viewer_link($label, $url = '', $class = '', $id = '', $icon = null)
	{
		global $path_to_root;

		if ($class != '')
			$class = " class='$class'";

		if ($id != '')
			$class = " id='$id'";

		if ($url != "") {
			$pars = access_string($label);
			if (user_graphic_links() && $icon)
				$pars[0] = set_icon($icon, $pars[0]);
			$preview_str = "<a target='_blank' $class $id href='$path_to_root/$url' onclick=\"javascript:openWindow(this.href,this.target); return false;\"$pars[1]>$pars[0]</a>";
		} else
			$preview_str = $label;
		return $preview_str;
	}

	function menu_link($url, $label, $id = null)
	{
		$id = default_focus($id);
		$pars = access_string($label);
		return "<a href='$url' class='menu_option' id='$id' $pars[1]>$pars[0]</a>";
	}

	function submenu_option($title, $url, $id = null)
	{
		global $path_to_root;
		display_note(menu_link($path_to_root . $url, $title, $id), 0, 1);
	}

	function submenu_view($title, $type, $number, $id = null)
	{
		display_note(get_trans_view_str($type, $number, $title, false, 'viewlink', $id), 0, 1);
	}

	function submenu_print($title, $type, $number, $id = null, $email = 0, $extra = 0)
	{
		display_note(print_document_link($number, $title, true, $type, false, 'printlink', $id, $email, $extra), 0, 1);
	}
	// -----------------------------------------------------------------------------------
	function hyperlink_params($target, $label, $params, $center = true)
	{
		$id = default_focus();

		$pars = access_string($label);
		if ($target == '')
			$target = $_SERVER['PHP_SELF'];
// 		if ($center)
// 			echo "<br><center>";
		$controlAsString = "<a id='$id' href='$target?$params'$pars[1]>$pars[0]</a>\n";
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_LINK, '', $controlAsString));
// 		if ($center)
// 			echo "</center>";
	}

	function hyperlink_params_td($target, $label, $params)
	{
		$this->hyperlink_params($target, $label, $params, false);
	}

	// -----------------------------------------------------------------------------------
	function hyperlink_params_separate($target, $label, $params, $center = false)
	{
		$id = default_focus();

		$pars = access_string($label);
// 		if ($center)
// 			echo "<br><center>";
		$controlAsString = "<a target='_blank' id='$id' href='$target?$params' $pars[1]>$pars[0]</a>\n";
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_LINK, '', $controlAsString));
// 		if ($center)
// 			echo "</center>";
	}

	function hyperlink_params_separate_td($target, $label, $params)
	{
		$this->hyperlink_params_separate($target, $label, $params);
	}

	// --------------------------------------------------------------------------------------------------
	function alt_table_row_color(&$k, $extra_class = null)
	{
		// TODO LayoutHint table layout
		$classes = $extra_class ? array(
			$extra_class
		) : array();
		if ($k == 1) {
			array_push($classes, 'oddrow');
			$k = 0;
		} else {
			array_push($classes, 'evenrow');
			$k ++;
		}
		echo "<tr class='" . implode(' ', $classes) . "'>\n";
	}

	function table_section_title($msg, $colspan = 2)
	{
		View::get()->addControl(View::controlHeading($msg));
	}

	function table_header($labels, $params = '')
	{
		View::get()->tableAddHeader($labels);
	}
	// -----------------------------------------------------------------------------------
	function start_row($param = "")
	{
		View::get()->tableRowStart();
	}

	function end_row()
	{
		View::get()->tableRowEnd();
	}

	/**
	 *
	 * @param array | string $cells
	 */
	function table_add_cells($cells)
	{
		if (is_array($cells)) {
			$c = count($cells);
			if ($c != 2) {
				throw new \Exception("Unsupported array cell render '$c'");
			}
			View::get()->addControl(View::controlFromRenderedString(View::CONTROL_TEXT, '', $cells[0]));
			View::get()->addControl(View::controlFromRenderedString(View::CONTROL_COMBO, '', $cells[1]));
		} else {
			View::get()->addControl(View::controlFromRenderedString(View::CONTROL_TEXT, '', $cells));
		}
	}

	function br($num = 1)
	{
// 		for ($i = 0; $i < $num; $i ++)
// 			echo "<br>";
	}

	var $ajax_divs = array();

	function div_start($id = '', $trigger = null, $non_ajax = false)
	{
		// TODO
		if ($non_ajax) { // div for non-ajax elements
			array_push($this->ajax_divs, array(
				$id,
				null
			));
			echo "<div style='display:none' class='js_only' " . ($id != '' ? "id='$id'" : '') . ">";
		} else { // ajax ready div
			array_push($this->ajax_divs, array(
				$id,
				$trigger === null ? $id : $trigger
			));
			echo "<div " . ($id != '' ? "id='$id'" : '') . ">";
			ob_start();
		}
	}

	function div_end()
	{
		global $Ajax;

		// TODO
		if (count($this->ajax_divs)) {
			$div = array_pop($this->ajax_divs);
			if ($div[1] !== null)
				$Ajax->addUpdate($div[1], $div[0], ob_get_flush());
			echo "</div>";
		}
	}

	// -----------------------------------------------------------------------------
	// Tabbed area:
	// $name - prefix for widget internal elements:
	// Nth tab submit name: {$name}_N
	// div id: _{$name}_div
	// sel (hidden) name: _{$name}_sel
	// $tabs - array of tabs; string: tab title or array(tab_title, enabled_status)
	function tabbed_content_start($name, $tabs, $dft = '')
	{
		global $Ajax;
		$selname = '_' . $name . '_sel';
		$div = '_' . $name . '_div';

		$sel = find_submit($name . '_', false);
		if ($sel == null)
			$sel = get_post($selname, (string) ($dft === '' ? key($tabs) : $dft));

		if ($sel !== @$_POST[$selname])
			$Ajax->activate($name);

		$_POST[$selname] = $sel;

		div_start($name);

		$context = array();
		$context['divID'] = $div;
		$context['selName'] = $selname;
		$context['sel'] = $sel;
		$tabContext = array();
		foreach ($tabs as $tab_no => $tab) {
			$acc = access_string(is_array($tab) ? $tab[0] : $tab);
			$disabled = (is_array($tab) && ! $tab[1]) ? 'disabled ' : '';
			$tabContext[] = array(
				'text' => $acc[0],
				'accessKey' => $acc[1],
				'disabled' => $disabled,
				'isActive' => ($tab_no === $sel),
				'name' => $name . '_' . $tab_no,
			);
		}
		$context['tabs'] = $tabContext;

		echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'tabs', $context);

	}

	function tabbed_content_end()
	{
		View::get()->render();
		// content box (don't change to div_end() unless div_start() is used above)
		echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'tabs_end', array());
		div_end(); // tabs widget
	}

	function tab_changed($name)
	{
		$to = find_submit("{$name}_", false);
		if (! $to)
			return null;

		return array(
			'from' => $from = get_post("_{$name}_sel"),
			'to' => $to
		);
	}

	function pager_link($link_text, $url, $icon = false)
	{
		global $path_to_root;

		if (user_graphic_links() && $icon)
			$link_text = set_icon($icon, $link_text);

		$href = $path_to_root . $url;
		return "<a href='$href'>" . $link_text . "</a>";
	}

	function pager_button($name, $value, $enabled = true, $icon = false)
	{
		global $path_to_root;
		return "<button " . ($enabled ? '' : 'disabled') . " class=\"navibutton\" type=\"submit\"" . " name=\"$name\"  id=\"$name\" value=\"$value\">" . ($icon ? "<img src='$path_to_root/themes/" . user_theme() . "/images/" . $icon . "'>" : '') . "<span>$value</span></button>\n";
	}

	function pager_button_cell($name, $value, $enabled = true, $align = 'left')
	{
		label_cell($this->pager_button($name, $value, $enabled), "align='$align'");
	}

	// -----------------------------------------------------------------------------
	//
	// Sql paged table view. Call this function inside form.
	//
	function pager(&$pager)
	{
		global $use_popup_windows, $use_date_picker, $path_to_root;

		$pager->select_records();

		div_start("_{$pager->name}_span");

		$headers = array();
		foreach ($pager->columns as $num_col => $col) {
			// record status control column is displayed only when control checkbox is on
			if (isset($col['head']) && ($col['type'] != 'inactive' || get_post('show_inactive'))) {
				if (! isset($col['ord']))
					$headers[] = $col['head'];
				else {
					$icon = (($col['ord'] == 'desc') ? 'sort_desc.gif' : ($col['ord'] == 'asc' ? 'sort_asc.gif' : 'sort_none.gif'));
					$headers[] = navi_button($pager->name . '_sort_' . $num_col, $col['head'], true, $icon);
				}
			}
		}
		/* show a table of records returned by the sql */
		start_table(TABLESTYLE, "width=$pager->width");
		table_header($headers);

		if ($pager->header_fun) { // if set header handler
			start_row("class='{$pager->header_class}'");
			$fun = $pager->header_fun;
			if (method_exists($pager, $fun)) {
				$h = $pager->$fun($pager);
			} elseif (function_exists($fun)) {
				$h = $fun($pager);
			}

			foreach ($h as $c) { // draw header columns
				$pars = isset($c[1]) ? $c[1] : '';
				label_cell($c[0], $pars);
			}
			end_row();
		}

		$cc = 0; // row colour counter
		$data = [];
		// Support for the new PagerInterface whilst maintaining compatibility upstream.
		if (is_a($pager, 'SGW\common\Pager\PagerInterface')) {
			$data = $pager->generator();
		} else {
			$data = $pager->data;
		}
		foreach ($data as $line_no => $row) {
			$marker = $pager->marker;
			if ($marker && $marker($row))
				start_row("class='$pager->marker_class'");
			else
				alt_table_row_color($cc);
			foreach ($pager->columns as $k => $col) {
				$coltype = $col['type'];
				$cell = '';
				if (isset($col['name'])) {
					$property = $col['name'];
					if (is_object($row)) {
						if (property_exists($row, $property)) {
							$cell = $row->$property;
						}
					} else {
						$cell = $row[$property];
					}
				}

				if (isset($col['fun'])) { // use data input function if defined
					$fun = $col['fun'];
					if (is_string($fun) && method_exists($pager, $fun)) {
						$cell = $pager->$fun($row, $cell);
					} elseif (is_string($fun) && function_exists($fun)) {
						$cell = $fun($row, $cell);
					} elseif (is_callable($fun)) {
						$cell = $fun($row, $cell);
					} else
						$cell = '';
				}
				switch ($coltype) { // format column
					case 'time':
						label_cell($cell, "width=40");
						break;
					case 'date':
						label_cell(sql2date($cell), "align='center' nowrap");
						break;
					case 'dstamp': // time stamp displayed as date
						label_cell(sql2date(substr($cell, 0, 10)), "align='center' nowrap");
						break;
					case 'tstamp': // time stamp - FIX user format
						label_cell(sql2date(substr($cell, 0, 10)) . ' ' . substr($cell, 10), "align='center'");
						break;
					case 'percent':
						percent_cell($cell);
						break;
					case 'amount':
						if ($cell == '')
							label_cell('');
						else
							amount_cell($cell, false);
						break;
					case 'qty':
						if ($cell == '')
							label_cell('');
						else
							qty_cell($cell, false, isset($col['dec']) ? $col['dec'] : null);
						break;
					case 'email':
						email_cell($cell, isset($col['align']) ? "align='" . $col['align'] . "'" : null);
						break;
					case 'rate':
						label_cell(number_format2($cell, user_exrate_dec()), "align=center");
						break;
					case 'inactive':
						if (get_post('show_inactive'))
							$pager->inactive_control_cell($row);
						break;
					default:
						// case 'text':
						if (isset($col['align']))
							label_cell($cell, "align='" . $col['align'] . "'");
						else
							label_cell($cell);
					case 'skip': // column not displayed
				}
			}
			end_row();
		}
		// end of while loop

		if ($pager->footer_fun) { // if set footer handler
			start_row("class='{$pager->footer_class}'");
			$fun = $pager->footer_fun;
			if (method_exists($pager, $fun)) {
				$h = $pager->$fun($pager);
			} elseif (function_exists($fun)) {
				$h = $fun($pager);
			}

			foreach ($h as $c) { // draw footer columns
				$pars = isset($c[1]) ? $c[1] : '';
				label_cell($c[0], $pars);
			}
			end_row();
		}

		start_row("class='navibar'");
		$colspan = count($pager->columns);
		$inact = @$pager->inactive_ctrl == true ? ' ' . checkbox(null, 'show_inactive', null, true) . _("Show also Inactive") : '';

		end_row();
		end_table();

			$but_pref = $pager->name . '_page_';
// 			if (@$pager->inactive_ctrl)
// 				submit('Update', _('Update'), true, '', null); // inactive update
			$context = array(
				'first'    => $this->pager_button($but_pref . 'first', _('&laquo;'), $pager->first_page),
				'previous' => $this->pager_button($but_pref . 'prev', _('&lsaquo;'), $pager->prev_page),
				'next'     => $this->pager_button($but_pref . 'next', _('&rsaquo;'), $pager->next_page),
				'last'     => $this->pager_button($but_pref . 'last', _('&raquo;'), $pager->last_page),
			);
			$from = ($pager->curr_page - 1) * $pager->page_len + 1;
			$to = $from + $pager->page_len - 1;
			if ($to > $pager->rec_count)
				$to = $pager->rec_count;
			$all = $pager->rec_count;
// 			echo sprintf(_('Records %d-%d of %d'), $from, $to, $all);
// 			echo $inact;
// 			echo "</td>";

			echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'pager', $context);


/*
		if ($pager->rec_count) {
			echo "<td colspan=$colspan class='navibar' style='border:none;padding:3px;'>";
			echo "<div style='float:right;'>";
			$but_pref = $pager->name . '_page_';
			start_table();
			start_row();
			if (@$pager->inactive_ctrl)
				submit('Update', _('Update'), true, '', null); // inactive update
			echo navi_button_cell($but_pref . 'first', _('First'), $pager->first_page, 'right');
			echo navi_button_cell($but_pref . 'prev', _('Prev'), $pager->prev_page, 'right');
			echo navi_button_cell($but_pref . 'next', _('Next'), $pager->next_page, 'right');
			echo navi_button_cell($but_pref . 'last', _('Last'), $pager->last_page, 'right');
			end_row();
			end_table();
			echo "</div>";
			$from = ($pager->curr_page - 1) * $pager->page_len + 1;
			$to = $from + $pager->page_len - 1;
			if ($to > $pager->rec_count)
				$to = $pager->rec_count;
			$all = $pager->rec_count;
			echo sprintf(_('Records %d-%d of %d'), $from, $to, $all);
			echo $inact;
			echo "</td>";
		} else {
			label_cell(_('No records') . $inact, "colspan=$colspan class='navibar'");
		}
*/
		if (isset($pager->marker_txt))
			display_note($pager->marker_txt, 0, 1, "class='$pager->notice_class'");

		div_end();
		return true;
	}
}

?>