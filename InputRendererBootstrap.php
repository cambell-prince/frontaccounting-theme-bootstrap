<?php
namespace FA\Theme\Bootstrap;

class InputRendererBootstrap extends \InputRenderer
{

	// ------------------------------------------------------------------------------
	//
	// Helper function for simple db table editor pages
	//
	function simple_page_mode($numeric_id = true)
	{
		global $Ajax, $Mode, $selected_id;

		$default = $numeric_id ? - 1 : '';
		$selected_id = get_post('selected_id', $default);
		foreach (array(
			'ADD_ITEM',
			'UPDATE_ITEM',
			'RESET',
			'CLONE'
		) as $m) {
			if (isset($_POST[$m])) {
				$Ajax->activate('_page_body');
				if ($m == 'RESET' || $m == 'CLONE')
					$selected_id = $default;
				unset($_POST['_focus']);
				$Mode = $m;
				return;
			}
		}
		foreach (array(
			'Edit',
			'Delete'
		) as $m) {
			foreach ($_POST as $p => $pvar) {
				if (strpos($p, $m) === 0) {
					// $selected_id = strtr(substr($p, strlen($m)), array('%2E'=>'.'));
					unset($_POST['_focus']); // focus on first form entry
					$selected_id = quoted_printable_decode(substr($p, strlen($m)));
					$Ajax->activate('_page_body');
					$Mode = $m;
					return;
				}
			}
		}
		$Mode = '';
	}

	// ------------------------------------------------------------------------------
	//
	// Read numeric value from user formatted input
	//
	function input_num($postname = null, $dflt = 0)
	{
		if (! isset($_POST[$postname]) || $_POST[$postname] == "")
			return $dflt;

		return user_numeric($_POST[$postname]);
	}

	// ---------------------------------------------------------------------------------
	function hidden($name, $value = null, $echo = true)
	{
		global $Ajax;

		if ($value === null)
			$value = get_post($name);

		$ret = "<input type=\"hidden\" name=\"$name\" value=\"$value\">";
		$Ajax->addUpdate($name, $name, $value);
		if ($echo)
			View::get()->addControl(View::controlFromRenderedString(View::CONTROL_HIDDEN, '', $ret));
		else
			return $ret;
	}
	function submit($name, $value, $echo = true, $title = false, $atype = false, $icon = false)
	{
		global $path_to_root;

		$aspect = '';
		if ($atype === null) {
			$aspect = fallback_mode() ? " aspect='fallback'" : " style='display:none;'";
		} elseif (! is_bool($atype)) { // necessary: switch uses '=='

			$aspect = "aspect='$atype' ";
			$types = explode(' ', $atype);

			foreach ($types as $type) {
				switch ($type) {
					case 'selector':
						$aspect = " aspect='selector' rel = '$value'";
						$value = _("Select");
						if ($icon === false)
							$icon = ICON_SUBMIT;
						break;

					case 'default':
						if ($icon === false)
							$icon = ICON_SUBMIT;
						break;

					case 'cancel':
						if ($icon === false)
							$icon = ICON_ESCAPE;
						break;

					case 'nonajax':
						$atype = false;
				}
			}
		}
		$iconClass = ThemeBootstrap::fontAwesomeIcon($icon ? $icon : $name);
		$submit_str = "<button class=\"btn btn-default " . ($atype ? 'ajaxsubmit' : 'inputsubmit') . "\" type=\"submit\"" . $aspect . " name=\"$name\"  id=\"$name\" value=\"$value\"" . ($title ? " title='$title'" : '') . ">";
		if ($iconClass) {
			$submit_str .= "<i class=\"fa $iconClass\"></i>";
		}
		$submit_str .= $value . "</button>\n";
		if ($echo)
			View::get()->addControl(View::controlFromRenderedString(View::CONTROL_BUTTON, '', $submit_str));
		else
			return $submit_str;
	}

	function submit_center($name, $value, $echo = true, $title = false, $async = false, $icon = false)
	{
		$this->submit($name, $value, $echo, $title, $async, $icon);
	}

	function submit_center_first($name, $value, $title = false, $async = false, $icon = false)
	{
		$this->submit($name, $value, true, $title, $async, $icon);
	}

	function submit_center_last($name, $value, $title = false, $async = false, $icon = false)
	{
		$this->submit($name, $value, true, $title, $async, $icon);
	}
	/*
	For following controls:
		'both' - use both Ctrl-Enter and Escape hotkeys 
		'upgrade' - use Ctrl-Enter with progress ajax indicator and Escape hotkeys. Nonajax request for OK option is performed.
		'cancel' - apply to 'RESET' button
	 */
	function submit_add_or_update($add = true, $title = false, $async = false, $clone = false)
	{
		$cancel = $async;

		if ($async === 'both') {
			$async = 'default';
			$cancel = 'cancel';
		} elseif ($async === 'upgrade') {
			$async = 'default nonajax process';
			$cancel = 'cancel';
		} else if ($async === 'default')
			$cancel = true;
		else if ($async === 'cancel')
			$async = true;

		if ($add)
			submit('ADD_ITEM', _("Add new"), true, $title, $async);
		else {
			submit('UPDATE_ITEM', _("Update"), true, _('Submit changes'), $async);
			if ($clone)
				submit('CLONE', _("Clone"), true, _('Edit new record with current data'), $async);
			submit('RESET', _("Cancel"), true, _('Cancel edition'), $cancel);
		}
	}

	function submit_add_or_update_center($add = true, $title = false, $async = false, $clone = false)
	{
		$this->submit_add_or_update($add, $title, $async, $clone);
	}

	function submit_add_or_update_row($add = true, $right = true, $extra = "", $title = false, $async = false, $clone = false)
	{
		View::get()->layoutHintRow();
// Probably need to make $right available to the view CP 2014-11
// 		if ($right)
// 			echo "<td>&nbsp;</td>\n";
		$this->submit_add_or_update($add, $title, $async, $clone);
	}

	function submit_cells($name, $value, $extra = "", $title = false, $async = false)
	{
		$this->submit($name, $value, true, $title, $async);
	}

	function submit_row($name, $value, $right = true, $extra = "", $title = false, $async = false)
	{
		View::get()->layoutHintRow();
// 		if ($right)
// 			echo "<td>&nbsp;</td>\n";
		$this->submit_cells($name, $value, $extra, $title, $async);
	}

	function submit_return($name, $value, $title = false)
	{
		if (@$_REQUEST['popup']) {
			$this->submit($name, $value, true, $title, 'selector');
		}
	}

	function submit_js_confirm($name, $msg, $set = true)
	{
		global $Ajax;
		$js = "_validate.$name=" . ($set ? "function(){ return confirm('" . strtr($msg, array(
			"\n" => '\\n'
		)) . "');};" : 'null;');
		if (in_ajax()) {
			$Ajax->addScript(true, $js);
		} else
			add_js_source($js);
	}
	// -----------------------------------------------------------------------------------
	function set_icon($icon, $title = false)
	{
		global $path_to_root;
		if (basename($icon) === $icon) // standard icons does not contain path separator
			$icon = "$path_to_root/themes/" . user_theme() . "/images/$icon";
		return "<img src='$icon' width='12' height='12' border='0'" . ($title ? " title='$title'" : "") . " />\n";
	}

	function button($name, $value, $title = false, $icon = false, $aspect = '')
	{
		// php silently changes dots,spaces,'[' and characters 128-159
		// to underscore in POST names, to maintain compatibility with register_globals
		$rel = '';
		if ($aspect == 'selector') {
			$rel = " rel='$value'";
			$value = _("Select");
		}
		if (user_graphic_links() && $icon) {
			if ($value == _("Delete")) // Helper during implementation
				$icon = ICON_DELETE;
			return "<button type='submit' class='btn editbutton' name='" . htmlentities(strtr($name, array(
				'.' => '=2E',
				'=' => '=3D' // ' '=>'=20','['=>'=5B'
			))) . "' value='1'" . ($title ? " title='$title'" : " title='$value'") . ($aspect ? " aspect='$aspect'" : '') . $rel . " />" . set_icon($icon) . "</button>\n";
		} else
			return "<input type='submit' class='btn editbutton' name='" . htmlentities(strtr($name, array(
				'.' => '=2E',
				'=' => '=3D' // ' '=>'=20','['=>'=5B'
			))) . "' value='$value'" . ($title ? " title='$title'" : '') . ($aspect ? " aspect='$aspect'" : '') . $rel . " />\n";
	}

	function button_cell($name, $value, $title = false, $icon = false, $aspect = '')
	{
		$controlAsString = button($name, $value, $title, $icon, $aspect);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_BUTTON, '', $controlAsString));
	}

	function delete_button_cell($name, $value, $title = false)
	{
		$this->button_cell($name, $value, $title, ICON_DELETE);
	}

	function edit_button_cell($name, $value, $title = false)
	{
		$this->button_cell($name, $value, $title, ICON_EDIT);
	}

	function select_button_cell($name, $value, $title = false)
	{
		$this->button_cell($name, $value, $title, ICON_ADD, 'selector');
	}

	function checkbox($label, $name, $value = null, $submit_on_change = false, $title = false)
	{
		global $Ajax;

		$str = '';

		if ($label)
			$str .= $label . "  ";
		if ($submit_on_change !== false) {
			if ($submit_on_change === true)
				$submit_on_change = "JsHttpRequest.request(\"_{$name}_update\", this.form);";
		}
		if ($value === null)
			$value = get_post($name, 0);

		$str .= "<input" . ($value == 1 ? ' checked' : '') . " type='checkbox' name='$name' value='1'" . ($submit_on_change ? " onclick='$submit_on_change'" : '') . ($title ? " title='$title'" : '') . " >\n";

		$Ajax->addUpdate($name, $name, $value);
		return $str;
	}

	function check($label, $name, $value = null, $submit_on_change = false, $title = false)
	{
		return $this->checkbox($label, $name, $value, $submit_on_change, $title);
	}

	function check_cells($label, $name, $value = null, $submit_on_change = false, $title = false, $params = '')
	{
		$controlAsString = $this->check(null, $name, $value, $submit_on_change, $title);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_CHECK, $label, $controlAsString));
	}

	function check_row($label, $name, $value = null, $submit_on_change = false, $title = false)
	{
		View::get()->layoutHintRow();
		$this->check_cells($label, $name, $value, $submit_on_change, $title);
	}

	// -----------------------------------------------------------------------------------
	function radio($label, $name, $value, $selected = null, $submit_on_change = false)
	{
		if (! isset($selected))
			$selected = get_post($name) === (string)$value;

		if ($submit_on_change === true)
			$submit_on_change = "JsHttpRequest.request(\"_{$name}_update\", this.form);";

		return "<input type='radio' class='form-control' name=$name value='$value' " . ($selected ? "checked" : '') . ($submit_on_change ? " onclick='$submit_on_change'" : '') . ">" . ($label ? $label : '');
	}

	// -----------------------------------------------------------------------------------
	function labelheader_cell($label, $params = "")
	{
		// TODO
		echo "<td class='tableheader' $params>$label</td>\n";
	}

	function label_cell($label, $params = "", $id = null)
	{
		/*
		 * This function is assumed to be only for real tables.
		 */

		global $Ajax;

		if (isset($id)) {
			$params .= " id='$id'";
			$Ajax->addUpdate($id, $id, $label);
		}
		$attributes = explode(' ', $params);
		$columnSpan = null;
		foreach ($attributes as $attribute) {
			if (strstr($attribute, '=')) {
				list($key, $value) = explode('=', $attribute);
				$key = trim($key);
				$value = trim($value);
				if ($key == 'colspan') {
					$columnSpan = $value;
				}
			}
		}

		if ($columnSpan) {
			View::get()->tableAddCellSpanningColumns($label, $columnSpan);
		} else {
			View::get()->tableAddCell($label); // What to do with id? CP 2014-11
		}
		return $label;
	}

	function email_cell($label, $params = "", $id = null)
	{
		label_cell("<a href='mailto:$label'>$label</a>", $params, $id);
	}

	function amount_decimal_cell($label, $params = "", $id = null)
	{
		$dec = 0;
		label_cell(price_decimal_format($label, $dec), "nowrap align=right " . $params, $id);
	}

	function amount_cell($label, $bold = false, $params = "", $id = null)
	{
		if ($bold)
			label_cell("<b>" . price_format($label) . "</b>", "nowrap align=right " . $params, $id);
		else
			label_cell(price_format($label), "nowrap align=right " . $params, $id);
	}

	// JAM Allow entered unit prices to be fractional
	function unit_amount_cell($label, $bold = false, $params = "", $id = null)
	{
		if ($bold)
			label_cell("<b>" . unit_price_format($label) . "</b>", "nowrap align=right " . $params, $id);
		else
			label_cell(unit_price_format($label), "nowrap align=right " . $params, $id);
	}

	function percent_cell($label, $bold = false, $id = null)
	{
		if ($bold)
			label_cell("<b>" . percent_format($label) . "</b>", "nowrap align=right", $id);
		else
			label_cell(percent_format($label), "nowrap align=right", $id);
	}
	// 2008-06-15. Changed
	function qty_cell($label, $bold = false, $dec = null, $id = null)
	{
		if (! isset($dec))
			$dec = get_qty_dec();
		if ($bold)
			label_cell("<b>" . number_format2($label, $dec) . "</b>", "nowrap align=right", $id);
		else
			label_cell(number_format2($label, $dec), "nowrap align=right", $id);
	}

	function label_cells($label, $value, $params = "", $params2 = "", $id = '')
	{
		$label = ($label == '&nbsp;') ? '' : $label;
		$controlAsString = $value;
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_LABEL, $label, $controlAsString));
	}

	function label_row($label, $value, $params = "", $params2 = "", $leftfill = 0, $id = '')
	{
		View::get()->layoutHintRow();
		$this->label_cells($label, $value, $params, $params2, $id);
	}

	// -----------------------------------------------------------------------------------
	function text_cells($label, $name, $value = null, $size = "", $max = "", $title = false, $labparams = "", $post_label = "", $inparams = "")
	{
		global $Ajax;

		default_focus($name);

		if ($value === null)
			$value = get_post($name);
		$controlAsString = "<input $inparams type=\"text\" class=\"form-control\" name=\"$name\" size=\"$size\" maxlength=\"$max\" value=\"$value\"" . ($title ? " title='$title'" : '') . ">";

// 		if ($post_label != "")
// 			echo " " . $post_label;

		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_TEXT, $label, $controlAsString));

		$Ajax->addUpdate($name, $name, $value);
	}

	function text_cells_ex($label, $name, $size, $max = null, $init = null, $title = null, $labparams = null, $post_label = null, $submit_on_change = false)
	{
		global $Ajax;

		default_focus($name);
		if (! isset($_POST[$name]) || $_POST[$name] == "") {
			if ($init)
				$_POST[$name] = $init;
			else
				$_POST[$name] = "";
		}

		if (! isset($max))
			$max = $size;

		$class = $submit_on_change ? 'form-control searchbox' : 'form-control';
		$controlAsString = "<input class=\"$class\" type=\"text\" name=\"$name\" size=\"$size\" maxlength=\"$max\" value=\"" . $_POST[$name] . "\"" . ($title ? " title='$title'" : '') . " >";
// 		if ($post_label)
// 			echo " " . $post_label;

		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_TEXT, $label, $controlAsString));

		$Ajax->addUpdate($name, $name, $_POST[$name]);
	}

	function text_row($label, $name, $value, $size, $max, $title = null, $params = "", $post_label = "")
	{
		View::get()->layoutHintRow();
		$this->text_cells($label, $name, $value, $size, $max, $title, $params, $post_label);
	}

	// -----------------------------------------------------------------------------------
	function text_row_ex($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null)
	{
		View::get()->layoutHintRow();
		$this->text_cells_ex($label, $name, $size, $max, $value, $title, $params, $post_label);
	}

	// -----------------------------------------------------------------------------------
	function email_row($label, $name, $value, $size, $max, $title = null, $params = "", $post_label = "")
	{
		View::get()->layoutHintRow();
		if (get_post($name))
			$label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
		$this->text_row($label, $name, $value, $size, $max, $title, $params, $post_label);
	}

	function email_row_ex($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null)
	{
		View::get()->layoutHintRow();
		if (get_post($name))
			$label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
		$this->text_row_ex($label, $name, $size, $max, $title, $value, $params, $post_label);
	}

	function link_row($label, $name, $value, $size, $max, $title = null, $params = "", $post_label = "")
	{
		View::get()->layoutHintRow();
		$val = get_post($name);
		if ($val) {
			if (strpos($val, 'http://') === false)
				$val = 'http://' . $val;
			$label = "<a href='$val' target='_blank'>$label</a>";
		}
		$this->text_row($label, $name, $value, $size, $max, $title, $params, $post_label);
	}

	function link_row_ex($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null)
	{
		View::get()->layoutHintRow();
		$val = get_post($name);
		if ($val) {
			if (strpos($val, 'http://') === false)
				$val = 'http://' . $val;
			$label = "<a href='$val' target='_blank'>$label</a>";
		}
		text_row_ex($label, $name, $size, $max, $title, $value, $params, $post_label);
	}

	// -----------------------------------------------------------------------------------
	//
	// Since FA 2.2 $init parameter is superseded by $check.
	// When $check!=null current date is displayed in red when set to other
	// than current date.
	//
	function date_cells($label, $name, $title = null, $check = null, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = null, $submit_on_change = false)
	{
		global $path_to_root, $Ajax;

		if (! isset($_POST[$name]) || $_POST[$name] == "") {
			if ($inc_years == 1001)
				$_POST[$name] = null;
			else {
				$dd = Today();
				if ($inc_days != 0)
					$dd = add_days($dd, $inc_days);
				if ($inc_months != 0)
					$dd = add_months($dd, $inc_months);
				if ($inc_years != 0)
					$dd = add_years($dd, $inc_years);
				$_POST[$name] = $dd;
			}
		}
		if (user_use_date_picker()) {
			$calc_image = (file_exists("$path_to_root/themes/" . user_theme() . "/images/cal.gif")) ? "$path_to_root/themes/" . user_theme() . "/images/cal.gif" : "$path_to_root/themes/default/images/cal.gif";
			$post_label = "<a tabindex='-1' href=\"javascript:date_picker(document.getElementsByName('$name')[0]);\">" . "	<img src='$calc_image' width='15' height='15' border='0' alt='" . _('Click Here to Pick up the date') . "'></a>\n";
		} else
			$post_label = "";

		$class = $submit_on_change ? 'date active form-control' : 'date form-control';

		$aspect = $check ? 'aspect="cdate"' : '';
		if ($check && (get_post($name) != Today()))
			$aspect .= ' style="color:#FF0000"';

		default_focus($name);
		$size = (user_date_format() > 3) ? 11 : 10;

		$controlAsString = "<div class=\"input-group\">"
			. "<input type=\"text\" name=\"$name\" class=\"$class\" $aspect size=\"$size\" maxlength=\"12\" value=\"" . $_POST[$name] . "\"" . ($title ? " title='$title'" : '') . " > "
			. "<span class=\"input-group-addon\">$post_label</span>"
			. "</div>";
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_DATE, $label, $controlAsString)); // CONTROL_DATE? CP 2014-11

		$Ajax->addUpdate($name, $name, $_POST[$name]);
	}

	function date_row($label, $name, $title = null, $check = null, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = null, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->date_cells($label, $name, $title, $check, $inc_days, $inc_months, $inc_years, $params, $submit_on_change);
	}

	// -----------------------------------------------------------------------------------
	function password_row($label, $name, $value)
	{
		View::get()->layoutHintRow();
		$controlAsString = "<input type='password' class='form-control' name='$name' size=20 maxlength=20 value='$value' />";
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_TEXT, $label, $controlAsString)); // CONTROL_PASSWORD? CP 2014-11
	}

	// -----------------------------------------------------------------------------------
	function file_cells($label, $name, $id = "")
	{
		if ($id != "")
			$id = "id='$id'";
		$controlAsString = "<input type='file' name='$name' $id />";
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_FILE, $label, $controlAsString));
	}

	function file_row($label, $name, $id = "")
	{
		View::get()->layoutHintRow();
		$this->file_cells($label, $name, $id);
	}

	/*-----------------------------------------------------------------------------------
	
	 Reference number input.
	
	 Optional  $context array contains transaction data used in number parsing:
	 	'data' - data used for month/year codes
		'location' - location code
	 	'customer' - debtor_no
	 	'supplier' - supplier id
	 	'branch' - branch_code
	*/
	function ref_cells($label, $name, $title = null, $init = null, $params = null, $submit_on_change = false, $type = null, $context = null)
	{
		// parent::ref_cells($label, $name, $title, $init, $params, $submit_on_change, $type, $context);
		global $Ajax, $Refs;

		if (isset($type)) {
			if (empty($_POST[$name.'_list'])) // restore refline id
				$_POST[$name.'_list'] = $Refs->reflines->find_refline_id(empty($_POST[$name]) ? $init : $_POST[$name], $type);

			if (empty($_POST[$name])) // initialization
			{
				if (isset($init))
				{
					$_POST[$name] = $init;
				} else {
					$_POST[$name] = $Refs->get_next($type, $_POST[$name.'_list'], $context);
				}
				$Ajax->addUpdate(true, $name, $_POST[$name]);
			}

			if (check_ui_refresh($name)) { // call context changed
				$_POST[$name] = $Refs->normalize($_POST[$name], $type, $context, $_POST[$name.'_list']);
				$Ajax->addUpdate(true, $name, $_POST[$name]);
			}

			if ($Refs->reflines->count($type)>1) {
				if (list_updated($name.'_list')) {
					$_POST[$name] = $Refs->get_next($type, $_POST[$name.'_list'], $context);
					$Ajax->addUpdate(true, $name, $_POST[$name]);
				}
				$list = refline_list($name.'_list', $type);
			} else {
				$list = '';
			}

			$controlAsString = $list."<input class='form-control' type='text' name='".$name."' "
				.(check_edit_access($name) ? '' : 'disabled ')
				."value='".@$_POST[$name]."' size=10 maxlength=35>";
			View::get()->addControl(View::controlFromRenderedString(View::CONTROL_TEXT, $label, $controlAsString));			

		}
		else // just wildcard ref field (e.g. for global inquires)
		{
			text_cells_ex($label, $name, 16, 35, $init, $title, $params, null, $submit_on_change);
		}
	}

	// -----------------------------------------------------------------------------------
	function ref_row($label, $name, $title = null, $init = null, $submit_on_change = false, $type=null, $context = null)
	{
		View::get()->layoutHintRow();
		View::get()->tableRowStart();
		$this->ref_cells($label, $name, $title, $init, null, $submit_on_change, $type, $context);
		View::get()->tableRowEnd();
	}

	// -----------------------------------------------------------------------------------
	function percent_row($label, $name, $init = null)
	{
		if (! isset($_POST[$name]) || $_POST[$name] == "") {
			$_POST[$name] = $init == null ? '' : $init;
		}

		$this->small_amount_row($label, $name, $_POST[$name], null, "%", user_percent_dec());
	}

	function amount_cells_ex($label, $name, $size, $max = null, $init = null, $params = null, $post_label = null, $dec = null)
	{
		global $Ajax;

		if (! isset($dec))
			$dec = user_price_dec();
		if (! isset($_POST[$name]) || $_POST[$name] == "") {
			if ($init !== null)
				$_POST[$name] = $init;
			else
				$_POST[$name] = '';
		}
		if (! isset($max))
			$max = $size;

		$controlAsString = "<input class='amount form-control' type=\"text\" name=\"$name\" size=\"$size\" maxlength=\"$max\" dec=\"$dec\" value=\"" . $_POST[$name] . "\">";

		if ($post_label) {
			$spanAsString = "<span id='_{$name}_label'> $post_label</span>";
			View::get()->addControl(View::controlFromRenderedString(View::CONTROL_TEXT, $label, $spanAsString));
			$Ajax->addUpdate($name, '_' . $name . '_label', $post_label);
		}
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_TEXT, $label, $controlAsString));
		$Ajax->addUpdate($name, $name, $_POST[$name]);
		$Ajax->addAssign($name, $name, 'dec', $dec);
	}

	// -----------------------------------------------------------------------------------
	function amount_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
	{
		$this->amount_cells_ex($label, $name, 15, 15, $init, $params, $post_label, $dec);
	}

	// JAM Allow entered unit prices to be fractional
	function unit_amount_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
	{
		if (! isset($dec))
			$dec = user_price_dec() + 2;

		$this->amount_cells_ex($label, $name, 15, 15, $init, $params, $post_label, $dec + 2);
	}

	function amount_row($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
	{
		View::get()->layoutHintRow();
		$this->amount_cells($label, $name, $init, $params, $post_label, $dec);
	}

	function small_amount_row($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
	{
		View::get()->layoutHintRow();
		$this->small_amount_cells($label, $name, $init, $params, $post_label, $dec);
	}

	// -----------------------------------------------------------------------------------
	function qty_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
	{
		if (! isset($dec))
			$dec = user_qty_dec();

		$this->amount_cells_ex($label, $name, 15, 15, $init, $params, $post_label, $dec);
	}

	function qty_row($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
	{
		View::get()->layoutHintRow();
		if (! isset($dec))
			$dec = user_qty_dec();

		$this->amount_cells($label, $name, $init, $params, $post_label, $dec);
	}

	function small_qty_row($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
	{
		View::get()->layoutHintRow();
		if (! isset($dec))
			$dec = user_qty_dec();

		$this->small_amount_cells($label, $name, $init, $params, $post_label, $dec);
	}

	// -----------------------------------------------------------------------------------
	function small_amount_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
	{
		$this->amount_cells_ex($label, $name, 7, 12, $init, $params, $post_label, $dec);
	}

	// -----------------------------------------------------------------------------------
	function small_qty_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null)
	{
		if (! isset($dec))
			$dec = user_qty_dec();
		$this->amount_cells_ex($label, $name, 7, 12, $init, $params, $post_label, $dec);
	}

	// -----------------------------------------------------------------------------------
	function textarea_cells($label, $name, $value, $cols, $rows, $title = null, $params = "")
	{
		global $Ajax;

		default_focus($name);

		if ($value == null)
			$value = (! isset($_POST[$name]) ? "" : $_POST[$name]);

		$controlAsString = "<textarea class='form-control' name='$name' cols='$cols' rows='$rows'" . ($title ? " title='$title'" : '') . ">$value</textarea></td>\n";
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_TEXTAREA, $label, $controlAsString));

		$Ajax->addUpdate($name, $name, $value);
	}

	function textarea_row($label, $name, $value, $cols, $rows, $title = null, $params = "")
	{
		View::get()->layoutHintRow();
		$this->textarea_cells($label, $name, $value, $cols, $rows, $title, $params);
	}

	// -----------------------------------------------------------------------------------
	//
	// When show_inactive page option is set
	// displays value of inactive field as checkbox cell.
	// Also updates database record after status change.
	//
	function inactive_control_cell($id, $value, $table, $key)
	{
		global $Ajax;

		if (check_value('show_inactive')) {
			$name = "Inactive" . $id;
			$value = $value ? 1 : 0;
			if (isset($_POST['LInact'][$id]) && (get_post('_Inactive' . $id . '_update') || get_post('Update')) && (check_value('Inactive' . $id) != $value)) {
				update_record_status($id, ! $value, $table, $key);
			}
			$cellAsString = checkbox(null, $name, $value, true, '') . hidden("LInact[$id]", $value, false);
			View::get()->tableAddCell($cellAsString);
		}
	}
	//
	// Displays controls for optional display of inactive records
	//
	function inactive_control_row($th)
	{
		$cellAsString = "<div style='float:left;'>" . checkbox(null, 'show_inactive', null, true) . _("Show also Inactive") . "</div><div style='float:right;'>" . submit('Update', _('Update'), false, '', null) . "</div>";
		View::get()->tableRowStart();
		View::get()->tableAddCellSpanningColumns($cellAsString, count($th));
		View::get()->tableRowEnd();
	}
	//
	// Inserts additional column header when display of inactive records is on.
	//
	function inactive_control_column(&$th)
	{
		global $Ajax;

		if (check_value('show_inactive'))
			array_insert($th, count($th) - 2, _("Inactive"));
		if (get_post('_show_inactive_update')) {
			$Ajax->activate('_page_body');
		}
	}

	function customer_credit_row($customer, $credit, $parms = '')
	{
		global $path_to_root;

		$this->label_row(_("Current Credit:"), "<a target='_blank' " . ($credit < 0 ? 'class="redfg"' : '') . "href='$path_to_root/sales/inquiry/customer_inquiry.php?customer_id=" . $customer . "'" . " onclick=\"javascript:openWindow(this.href,this.target); return false;\" >" . price_format($credit) . "</a>", $parms);
	}

	function supplier_credit_row($supplier, $credit, $parms = '')
	{
		global $path_to_root;

		$this->label_row(_("Current Credit:"), "<a target='_blank' " . ($credit < 0 ? 'class="redfg"' : '') . "href='$path_to_root/purchasing/inquiry/supplier_inquiry.php?supplier_id=" . $supplier . "'" . " onclick=\"javascript:openWindow(this.href,this.target); return false;\" >" . price_format($credit) . "</a>", $parms);
	}

	function bank_balance_row($bank_acc, $parms = '')
	{
		global $path_to_root;

		$to = add_days(Today(), 1);
		$bal = get_balance_before_for_bank_account($bank_acc, $to);
		$this->label_row(_("Bank Balance:"), "<a target='_blank' " . ($bal < 0 ? 'class="redfg"' : '') . "href='$path_to_root/gl/inquiry/bank_inquiry.php?bank_account=" . $bank_acc . "'" . " onclick=\"javascript:openWindow(this.href,this.target); return false;\" >&nbsp;" . price_format($bal) . "</a>", $parms);
	}
}

?>