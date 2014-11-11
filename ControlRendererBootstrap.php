<?php
namespace FA\Theme\Bootstrap;

class ControlRendererBootstrap extends \ControlRenderer
{
	private $tableContext = '';

	function start_form($multi = false, $dummy = false, $action = "", $name = "")
	{
		// $dummy - left for compatibility with 2.0 API
		$context = array(
			'name' => ($name != '') ? "name='$name'" : '',
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
		if ($breaks)
			br($breaks);
		hidden('_focus');
		hidden('_modified', get_post('_modified', 0));
		hidden('_token', $_SESSION['csrf_token']);

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
		View::get()->render();
// 		if ($breaks)
// 			br($breaks);
// 		$this->tableContext = '';
	}

	function start_outer_table($class = false, $extra = "", $padding = '2', $spacing = '0', $br = false)
	{
		if ($br)
			br();
		start_table($class, $extra, $padding, $spacing);
		echo "<tr valign=top><td>\n"; // outer table
	}

	function table_section($number = 1, $width = false)
	{
		if ($number > 1) {
			View::get()->layoutHintColumn($number);
		}
	}

	function end_outer_table($breaks = 0, $close_table = true)
	{
		if ($close_table)
			echo "</table>\n";
		echo "</td></tr>\n";
		end_table($breaks);
	}
	//
	// outer table spacer
	//
	function vertical_space($params = '')
	{
		echo "</td></tr><tr><td valign=center $params>";
	}

	function meta_forward($forward_to, $params = "")
	{
		global $Ajax;
		echo "<meta http-equiv='Refresh' content='0; url=$forward_to?$params'>\n";
		echo "<center><br>" . _("You should automatically be forwarded.");
		echo " " . _("If this does not happen") . " " . "<a href='$forward_to?$params'>" . _("click here") . "</a> " . _("to continue") . ".<br><br></center>\n";
		if ($params != '')
			$params = '?' . $params;
		$Ajax->redirect($forward_to . $params);
		exit();
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
		start_row();
		foreach ($labels as $label)
			labelheader_cell($label, $params);
		end_row();
	}
	// -----------------------------------------------------------------------------------
	function start_row($param = "")
	{
		View::get()->layoutHintRow();
// 		if ($param != "")
// 			echo "<tr $param>\n";
// 		else
// 			echo "<tr>\n";
	}

	function end_row()
	{
// 		echo "</tr>\n";
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
}

?>