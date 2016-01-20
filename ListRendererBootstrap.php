<?php
namespace FA\Theme\Bootstrap;

class ListRendererBootstrap extends \ListRenderer
{

	// ----------------------------------------------------------------------------
	// Universal sql combo generator
	// $sql must return selector values and selector texts in columns 0 & 1
	// Options are merged with defaults.
	function combo_input($name, $selected_id, $sql, $valfield, $namefield, $options = null, $type=null)
	{
		global $Ajax, $path_to_root, $SysPrefs;

		$opts = array( // default options
			'where' => array(), // additional constraints
			'order' => $namefield, // list sort order
			                       // special option parameters
			'spec_option' => false, // option text or false
			'spec_id' => 0, // option id
			                // submit on select parameters
			'default' => '', // default value when $_POST is not set
			'multi' => false, // multiple select
			'select_submit' => false, // submit on select: true/false
			'async' => true, // select update via ajax (true) vs _page_body reload
			                 // search box parameters
			'sel_hint' => null,
			'search_box' => false, // name or true/false
			'type' => 0, // type of extended selector:
			             // 0 - with (optional) visible search box, search by fragment inside id
			             // 1 - with hidden search box, search by option text
			             // 2 - with (optional) visible search box, search by fragment at the start of id
			             // 3 - TODO reverse: box with hidden selector available via enter; this
			             // would be convenient for optional ad hoc adding of new item
			'search_submit' => true, // search submit button: true/false
			'size' => 8, // size and max of box tag
			'max' => 50,
			'height' => false, // number of lines in select box
			'cells' => false, // combo displayed as 2 <td></td> cells
			'search' => array(), // sql field names to search
			'format' => null, // format functions for regular options
			'disabled' => false,
			'box_hint' => null, // box/selectors hints; null = std see below
			'category' => false, // category column name or false
			'show_inactive' => false, // show inactive records.
			'editable' => false, // false, or length of editable entry field
			'editlink' => false	// link to entity entry/edit page (optional)
				);
		// ------ merge options with defaults ----------
		if ($options != null)
			$opts = array_merge($opts, $options);
		if (! is_array($opts['where']))
			$opts['where'] = array(
				$opts['where']
			);

		$search_box = $opts['search_box'] === true ? '_' . $name . '_edit' : $opts['search_box'];
		// select content filtered by search field:
		$search_submit = $opts['search_submit'] === true ? '_' . $name . '_button' : $opts['search_submit'];
		// select set by select content field
		$search_button = $opts['editable'] ? '_' . $name . '_button' : ($search_box ? $search_submit : false);

		$select_submit = $opts['select_submit'];
		$spec_id = $opts['spec_id'];
		$spec_option = $opts['spec_option'];
		if ($opts['type'] == 0) {
			$by_id = true;
			$class = 'combo';
		} elseif ($opts['type'] == 1) {
			$by_id = false;
			$class = 'combo2';
		} else {
			$by_id = true;
			$class = 'combo3';
		}
		$class .= ' form-control';

		$disabled = $opts['disabled'] ? "disabled" : '';
		$multi = $opts['multi'];

		if (! count($opts['search'])) {
			$opts['search'] = array(
				$by_id ? $valfield : $namefield
			);
		}
		if ($opts['sel_hint'] === null)
			$opts['sel_hint'] = $by_id || $search_box == false ? '' : _('Press Space tab for search pattern entry');

		if ($opts['box_hint'] === null)
			$opts['box_hint'] = $search_box && $search_submit != false ? ($by_id ? _('Enter code fragment to search or * for all') : _('Enter description fragment to search or * for all')) : '';

		if ($selected_id == null) {
			$selected_id = get_post($name, (string) $opts['default']);
		}
		if (! is_array($selected_id))
			$selected_id = array(
				(string) $selected_id
			); // code is generalized for multiple selection support

		$txt = get_post($search_box);
		$rel = '';
		$limit = '';
		if (isset($_POST['_' . $name . '_update'])) { // select list or search box change
			if ($by_id)
				$txt = $_POST[$name];

			if (! $opts['async'])
				$Ajax->activate('_page_body');
			else
				$Ajax->activate($name);
		}
		if (isset($_POST[$search_button])) {
			if (! $opts['async'])
				$Ajax->activate('_page_body');
			else
				$Ajax->activate($name);
		}
		if ($search_box) {
			// search related sql modifications

			$rel = "rel='$search_box'"; // set relation to list
			if ($opts['search_submit']) {
				if (isset($_POST[$search_button])) {
					$selected_id = array(); // ignore selected_id while search
					if (! $opts['async'])
						$Ajax->activate('_page_body');
					else
						$Ajax->activate($name);
				}
				if ($txt == '') {
					if ($spec_option === false && $selected_id == array())
						$limit = ' LIMIT 1';
					else
						$opts['where'][] = $valfield . "=" . db_escape(get_post($name, $spec_id));
				} else if ($txt != '*') {

					foreach ($opts['search'] as $i => $s)
						$opts['search'][$i] = $s . " LIKE " . db_escape(($class == 'combo3' ? '' : '%') . $txt . '%');
					$opts['where'][] = '(' . implode($opts['search'], ' OR ') . ')';
				}
			}
		}

		// sql completion
		if (count($opts['where'])) {
			$where = strpos($sql, 'WHERE') == false ? ' WHERE ' : ' AND ';
			$where .= '(' . implode($opts['where'], ' AND ') . ')';
			$group_pos = strpos($sql, 'GROUP BY');
			if ($group_pos) {
				$group = substr($sql, $group_pos);
				$sql = substr($sql, 0, $group_pos) . $where . ' ' . $group;
			} else {
				$sql .= $where;
			}
		}
		if ($opts['order'] != false) {
			if (! is_array($opts['order']))
				$opts['order'] = array(
					$opts['order']
				);
			$sql .= ' ORDER BY ' . implode(',', $opts['order']);
		}

		$sql .= $limit;
		// ------ make selector ----------
		$selector = $first_opt = '';
		$first_id = false;
		$found = false;
		$lastcat = null;
		$edit = false;
		if ($result = db_query($sql)) {
			while ($contact_row = db_fetch($result)) {
				$value = $contact_row[0];
				$descr = $opts['format'] == null ? $contact_row[1] : call_user_func($opts['format'], $contact_row);
				$sel = '';
				if (get_post($search_button) && ($txt == $value)) {
					$selected_id[] = $value;
				}

				if (in_array((string) $value, $selected_id, true)) {
					$sel = 'selected';
					$found = $value;
					$edit = $opts['editable'] && $contact_row['editable'] && (@$_POST[$search_box] == $value) ? $contact_row[1] : false; // get non-formatted description
					if ($edit)
						break; // selected field is editable - abandon list construction
				}
				// show selected option even if inactive
				if (! $opts['show_inactive'] && @$contact_row['inactive'] && $sel === '') {
					continue;
				} else
					$optclass = @$contact_row['inactive'] ? "class='inactive'" : '';

				if ($first_id === false) {
					$first_id = $value;
					$first_opt = $descr;
				}
				$cat = $contact_row[$opts['category']];
				if ($opts['category'] !== false && $cat != $lastcat) {
					if ($lastcat!==null)
						$selector .= "</optgroup>";
					$selector .= "<optgroup label='" . $cat . "'>\n";
					$lastcat = $cat;
				}
				$selector .= "<option $sel $optclass value='$value'>$descr</option>\n";
			}
			if ($lastcat!==null)
				$selector .= "</optgroup>";
			db_free_result($result);
		}

		// Prepend special option.
		if ($spec_option !== false) { // if special option used - add it
			$first_id = $spec_id;
			$first_opt = $spec_option;
			$sel = $found === false ? 'selected' : '';
			$optclass = @$contact_row['inactive'] ? "class='inactive'" : '';
			$selector = "<option $sel value='$first_id'>$first_opt</option>\n" . $selector;
		}

		if ($found === false) {
			$selected_id = array(
				$first_id
			);
		}

		$_POST[$name] = $multi ? $selected_id : $selected_id[0];

		if ($SysPrefs->use_popup_search)
			$selector = "<select id='$name' autocomplete='off' ".($multi ? "multiple" : '')
			. ($opts['height']!==false ? ' size="'.$opts['height'].'"' : '')
			. "$disabled name='$name".($multi ? '[]':'')."' class='$class' title='"
			. $opts['sel_hint']."' $rel>".$selector."</select>\n";
		else
			$selector = "<select autocomplete='off' ".($multi ? "multiple" : '')
			. ($opts['height']!==false ? ' size="'.$opts['height'].'"' : '')
			. "$disabled name='$name".($multi ? '[]':'')."' class='$class' title='"
			. $opts['sel_hint']."' $rel>".$selector."</select>\n";

		if ($by_id && ($search_box != false || $opts['editable'])) {
			// on first display show selector list
			if (isset($_POST[$search_box]) && $opts['editable'] && $edit) {
				$selector = "<input type='hidden' name='$name' value='" . $_POST[$name] . "'>" . "<input type='text' $disabled name='{$name}_text' id='{$name}_text' size='" . $opts['editable'] . "' maxlength='" . $opts['max'] . "' $rel value='$edit'>\n";
				set_focus($name . '_text'); // prevent lost focus
			} else if (get_post($search_submit ? $search_submit : "_{$name}_button"))
				set_focus($name); // prevent lost focus
			if (! $opts['editable'])
				$txt = $found;
			$Ajax->addUpdate($name, $search_box, $txt ? $txt : '');
		}

		$Ajax->addUpdate($name, "_{$name}_sel", $selector);

		// span for select list/input field update
		$selector = "<span id='_{$name}_sel'>" . $selector . "</span>\n";

		// if selectable or editable list is used - add select button
		if ($select_submit != false || $search_button) {
			// button class selects form reload/ajax selector update
			$selector .= sprintf(SELECT_BUTTON, $disabled, user_theme(), (fallback_mode() ? '' : 'display:none;'), '_' . $name . '_update') . "\n";
		}
		// ------ make combo ----------
		$edit_entry = '';
		if ($search_box != false) {
			$edit_entry = "<input $disabled type='text' name='$search_box' id='$search_box' size='" . $opts['size'] . "' maxlength='" . $opts['max'] . "' value='$txt' class='$class' rel='$name' autocomplete='off' title='" . $opts['box_hint'] . "'" . (! fallback_mode() && ! $by_id ? " style=display:none;" : '') . ">\n";
			if ($search_submit != false || $opts['editable']) {
				$edit_entry .= sprintf(SEARCH_BUTTON, $disabled, user_theme(), (fallback_mode() ? '' : 'display:none;'), $search_submit ? $search_submit : "_{$name}_button") . "\n";
			}
		}
		default_focus(($search_box && $by_id) ? $search_box : $name);

		$img = "";
		if ($SysPrefs->use_popup_search && (!isset($opts['fixed_asset']) || !$opts['fixed_asset']))
		{
			$img_title = "";
			$link = "";
			$id = $name;
			if ($SysPrefs->use_popup_windows) {
				switch (strtolower($type)) {
					case "stock":
						$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=all&client_id=" . $id;
						$img_title = _("Search items");
						break;
					case "stock_manufactured":
						$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=manufactured&client_id=" . $id;
						$img_title = _("Search items");
						break;
					case "stock_purchased":
						$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=purchasable&client_id=" . $id;
						$img_title = _("Search items");
						break;
					case "stock_sales":
						$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=sales&client_id=" . $id;
						$img_title = _("Search items");
						break;
					case "stock_costable":
						$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=costable&client_id=" . $id;
						$img_title = _("Search items");
						break;
					case "component":
						$parent = $opts['parent'];
						$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=component&parent=".$parent."&client_id=" . $id;
						$img_title = _("Search items");
						break;
					case "kits":
						$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=kits&client_id=" . $id;
						$img_title = _("Search items");
						break;
					case "customer":
						$link = $path_to_root . "/sales/inquiry/customers_list.php?popup=1&client_id=" . $id;
						$img_title = _("Search customers");
						break;
					case "branch":
						$link = $path_to_root . "/sales/inquiry/customer_branches_list.php?popup=1&client_id=" . $id . "#customer_id";
						$img_title = _("Search branches");
						break;
					case "supplier":
						$link = $path_to_root . "/purchasing/inquiry/suppliers_list.php?popup=1&client_id=" . $id;
						$img_title = _("Search suppliers");
						break;
					case "account":
						$link = $path_to_root . "/gl/inquiry/accounts_list.php?popup=1&client_id=" . $id;
						$img_title = _("Search GL accounts");
						break;
				}
			}
		
			if ($link !=="") {
				$theme = user_theme();
				$img = '<img src="'.$path_to_root.'/themes/'.$theme.'/images/'.ICON_VIEW.
				'" style="vertical-align:middle;width:12px;height:12px;border:0;" onclick="javascript:lookupWindow(&quot;'.
				$link.'&quot;, &quot;&quot;);" title="' . $img_title . '" style="cursor:pointer;" />';
			}
		}
		
		if ($opts['editlink'])
			$selector .= ' '.$opts['editlink'];
		
		if ($search_box && $opts['cells'])
			$str = ($edit_entry != '' ? "<td>$edit_entry</td>" : '') . "<td>$selector$img</td>";
		else
			$str = $edit_entry . $selector . $img;
		return $str;
	}

	/*
	 * Helper function. 
	 * Returns true if selector $name is subject to update.
	 */
	function list_updated($name)
	{
		return isset($_POST['_' . $name . '_update']) || isset($_POST['_' . $name . '_button']);
	}
	// ----------------------------------------------------------------------------------------------
	// Universal array combo generator
	// $items is array of options 'value' => 'description'
	// Options is reduced set of combo_selector options and is merged with defaults.
	function array_selector($name, $selected_id, $items, $options = null)
	{
		global $Ajax;

		$opts = array( // default options
			'spec_option' => false, // option text or false
			'spec_id' => 0, // option id
			'select_submit' => false, // submit on select: true/false
			'async' => true, // select update via ajax (true) vs _page_body reload
			'default' => '', // default value when $_POST is not set
			'multi' => false, // multiple select
			                // search box parameters
			'height' => false, // number of lines in select box
			'sel_hint' => null,
			'disabled' => false
		);
		// ------ merge options with defaults ----------
		if ($options != null)
			$opts = array_merge($opts, $options);
		$select_submit = $opts['select_submit'];
		$spec_id = $opts['spec_id'];
		$spec_option = $opts['spec_option'];
		$disabled = $opts['disabled'] ? "disabled" : '';
		$multi = $opts['multi'];

		if ($selected_id == null) {
			$selected_id = get_post($name, $opts['default']);
		}
		if (! is_array($selected_id))
			$selected_id = array(
				(string) $selected_id
			); // code is generalized for multiple selection support

		if (isset($_POST['_' . $name . '_update'])) {
			if (! $opts['async'])
				$Ajax->activate('_page_body');
			else
				$Ajax->activate($name);
		}

		// ------ make selector ----------
		$selector = $first_opt = '';
		$first_id = false;
		$found = false;
		foreach ($items as $value => $descr) {
			$sel = '';
			if (in_array((string) $value, $selected_id, true)) {
				$sel = 'selected';
				$found = $value;
			}
			if ($first_id === false) {
				$first_id = $value;
				$first_opt = $descr;
			}
			$selector .= "<option $sel value='$value'>$descr</option>\n";
		}

		if ($first_id !== false) {
			$sel = ($found === $first_id) || ($found === false && ($spec_option === false)) ? "selected='selected'" : '';
		}
		// Prepend special option.
		if ($spec_option !== false) { // if special option used - add it
			$first_id = $spec_id;
			$first_opt = $spec_option;
			$sel = $found === false ? 'selected' : '';
			$selector = "<option $sel value='$spec_id'>$spec_option</option>\n" . $selector;
		}

		if ($found === false) {
			$selected_id = array(
				$first_id
			);
		}
		$_POST[$name] = $multi ? $selected_id : $selected_id[0];

		$selector = "<select autocomplete='off' " . ($multi ? "multiple" : '') . ($opts['height'] !== false ? ' size="' . $opts['height'] . '"' : '') . "$disabled name='$name" . ($multi ? '[]' : '') . "' class='combo form-control' title='" . $opts['sel_hint'] . "'>" . $selector . "</select>\n";

		$Ajax->addUpdate($name, "_{$name}_sel", $selector);

		$selector = "<span id='_{$name}_sel'>" . $selector . "</span>\n";

		if ($select_submit != false) { // if submit on change is used - add select button
			$selector .= sprintf(SELECT_BUTTON, $disabled, user_theme(), (fallback_mode() ? '' : 'display:none;'), '_' . $name . '_update') . "\n";
		}
		default_focus($name);

		return $selector;
	}
	// ----------------------------------------------------------------------------------------------
	function array_selector_row($label, $name, $selected_id, $items, $options=null)
	{
		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $selected_id, $items, $options);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_COMBO, $label, $controlAsString));
	}
	
	//----------------------------------------------------------------------------------------------
	function _format_add_curr($row)
	{
		static $company_currency;

		if ($company_currency == null) {
			$company_currency = get_company_currency();
		}
		return $row[1] . ($row[2] == $company_currency ? '' : ("&nbsp;-&nbsp;" . $row[2]));
	}

	function add_edit_combo($type)
	{
		global $path_to_root, $popup_editors, $SysPrefs;

		if (! isset($SysPrefs->use_icon_for_editkey) || $SysPrefs->use_icon_for_editkey == 0)
			return "";
			// Derive theme path
		$theme_path = $path_to_root . '/themes/' . user_theme();

		$key = $popup_editors[$type][1];
		$onclick = "onclick=\"javascript:callEditor($key); return false;\"";
		$img = "<img width='12' height='12' border='0' alt='Add/Edit' title='Add/Edit' src='$theme_path/images/" . ICON_EDIT . "'>";
		return "<a target = '_blank' href='#' $onclick tabindex='-1'>$img</a>";
	}

	function supplier_list($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $all = false, $editkey = false)
	{
		$sql = "SELECT supplier_id, supp_ref, curr_code, inactive FROM " . TB_PREF . "suppliers ";

		$mode = get_company_pref('no_supplier_list');

		if ($editkey)
			set_editor('supplier', $name, $editkey);

		$ret = combo_input($name, $selected_id, $sql, 'supplier_id', 'supp_name', array(
			'format' => '_format_add_curr',
			'order' => array(
				'supp_ref'
			),
			'search_box' => $mode != 0,
			'type' => 1,
			'search' => array(
				"supp_ref",
				"supp_name",
				"gst_no"
			),
			'spec_option' => $spec_option === true ? _("All Suppliers") : $spec_option,
			'spec_id' => ALL_TEXT,
			'select_submit' => $submit_on_change,
			'async' => false,
			'sel_hint' => $mode ? _('Press Space tab to filter by name fragment') : _('Select supplier'),
			'show_inactive' => $all,
			'editlink' => $editkey ? add_edit_combo('supplier') : false
		), "supplier");
		return $ret;
	}

	function supplier_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false)
	{
		$controlAsString = supplier_list($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_COMBO, $label, $controlAsString));
	}

	function supplier_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false)
	{
		View::get()->layoutHintRow();
		$controlAsString = supplier_list($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_COMBO, $label, $controlAsString));
	}
	// ----------------------------------------------------------------------------------------------
	function customer_list($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false)
	{
		global $all_items;

		$sql = "SELECT debtor_no, debtor_ref, curr_code, inactive FROM " . TB_PREF . "debtors_master ";

		$mode = get_company_pref('no_customer_list');

		if ($editkey)
			set_editor('customer', $name, $editkey);

		$ret = $this->combo_input($name, $selected_id, $sql, 'debtor_no', 'debtor_ref', array(
			'format' => '_format_add_curr',
			'order' => array(
				'debtor_ref'
			),
			'search_box' => $mode != 0,
			'type' => 1,
			'size' => 20,
			'search' => array(
				"debtor_ref",
				"name",
				"tax_id"
			),
			'spec_option' => $spec_option === true ? _("All Customers") : $spec_option,
			'spec_id' => ALL_TEXT,
			'select_submit' => $submit_on_change,
			'async' => false,
			'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new customer') : _('Select customer'),
			'show_inactive' => $show_inactive,
			'editlink' => $editkey ? add_edit_combo('customer') : false
		), "customer" );
		return $ret;
	}

	function customer_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false)
	{
		$controlAsString = customer_list($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_COMBO, $label, $controlAsString));
	}

	function customer_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false)
	{
		// TODO !!! path_to_root not used
		global $path_to_root;

		View::get()->layoutHintRow();
		$controlAsString = customer_list($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_COMBO, $label, $controlAsString));
	}

	// ------------------------------------------------------------------------------------------------
	function customer_branches_list($customer_id, $name, $selected_id = null, $spec_option = true, $enabled = true, $submit_on_change = false, $editkey = false)
	{
		$sql = "SELECT branch_code, branch_ref FROM " . TB_PREF . "cust_branch
		WHERE debtor_no=" . db_escape($customer_id) . " ";

		if ($editkey)
			set_editor('branch', $name, $editkey);

		$where = $enabled ? array(
			"inactive = 0"
		) : array();
		$ret = combo_input($name, $selected_id, $sql, 'branch_code', 'branch_ref', array(
			'where' => $where,
			'order' => array(
				'branch_ref'
			),
			'spec_option' => $spec_option === true ? _('All branches') : $spec_option,
			'spec_id' => ALL_TEXT,
			'select_submit' => $submit_on_change,
			'sel_hint' => _('Select customer branch'),
			'editlink' => $editkey ? add_edit_combo('branch') : false
		), "branch" );
		return $ret;
	}
	// ------------------------------------------------------------------------------------------------
	function customer_branches_list_cells($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false)
	{
		$controls = $this->customer_branches_list($customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
		View::get()->addComboControls($label, $controls);
	}

	function customer_branches_list_row($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false)
	{
		View::get()->layoutHintRow();
		$this->customer_branches_list_cells($label, $customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
	}

	// ------------------------------------------------------------------------------------------------
	function locations_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $fixed_asset = false)
	{
		$sql = "SELECT loc_code, location_name, inactive FROM ".TB_PREF."locations WHERE fixed_asset=".(int)$fixed_asset;

		return combo_input($name, $selected_id, $sql, 'loc_code', 'location_name',
			array(
			'spec_option' => $all_option === true ? _("All Locations") : $all_option,
				'spec_id' => ALL_TEXT,
			'select_submit' => $submit_on_change
		));
	}

	function locations_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $fixed_asset = false)
	{
		$controlAsString = locations_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_COMBO, $label, $controlAsString));
	}

	function locations_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $fixed_asset = false)
	{
		View::get()->layoutHintRow();
		$this->locations_list_cells($label, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	}

	// -----------------------------------------------------------------------------------------------
	function currencies_list($name, $selected_id = null, $submit_on_change = false, $exclude_home_curr = false)
	{
		$sql = "SELECT curr_abrev, currency, inactive FROM " . TB_PREF . "currencies";
		if ($exclude_home_curr)
			$sql .= " WHERE curr_abrev!='".get_company_currency()."'";

		// default to the company currency
		return combo_input($name, $selected_id, $sql, 'curr_abrev', 'currency', array(
			'select_submit' => $submit_on_change,
			'default' => get_company_currency(),
			'async' => false
		));
	}

	function currencies_list_cells($label, $name, $selected_id = null, $submit_on_change = false)
	{
		$controls = currencies_list($name, $selected_id, $submit_on_change);
		View::get()->addComboControls($label, $controls);
	}

	function currencies_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->currencies_list_cells($label, $name, $selected_id, $submit_on_change);
	}

	// ---------------------------------------------------------------------------------------------------
	function fiscalyears_list($name, $selected_id = null, $submit_on_change = false)
	{
		$sql = "SELECT * FROM " . TB_PREF . "fiscal_year";

		// default to the company current fiscal year
		return combo_input($name, $selected_id, $sql, 'id', '', array(
			'order' => 'begin',
			'default' => get_company_pref('f_year'),
			'format' => '_format_fiscalyears',
			'select_submit' => $submit_on_change,
			'async' => false
		));
	}

	function _format_fiscalyears($row)
	{
		return sql2date($row[1]) . "&nbsp;-&nbsp;" . sql2date($row[2]) . "&nbsp;&nbsp;" . ($row[3] ? _('Closed') : _('Active')) . "</option>\n";
	}

	function fiscalyears_list_cells($label, $name, $selected_id = null)
	{
		$controls = fiscalyears_list($name, $selected_id);
		View::get()->addComboControls($label, $controls);
	}

	function fiscalyears_list_row($label, $name, $selected_id = null)
	{
		View::get()->layoutHintRow();
		$this->fiscalyears_list_cells($label, $name, $selected_id);
	}
	// ------------------------------------------------------------------------------------
	function dimensions_list($name, $selected_id = null, $no_option = false, $showname = ' ', $submit_on_change = false, $showclosed = false, $showtype = 1)
	{
		$sql = "SELECT id, CONCAT(reference,'  ',name) as ref FROM " . TB_PREF . "dimensions";

		$options = array(
			'order' => 'reference',
			'spec_option' => $no_option ? $showname : false,
			'spec_id' => 0,
			'select_submit' => $submit_on_change,
			'async' => false
		);

		if (! $showclosed)
			$options['where'][] = "closed=0";
		if ($showtype)
			$options['where'][] = "type_=" . db_escape($showtype);

		return combo_input($name, $selected_id, $sql, 'id', 'ref', $options);
	}

	function dimensions_list_cells($label, $name, $selected_id = null, $no_option = false, $showname = null, $showclosed = false, $showtype = 0, $submit_on_change = false)
	{
		$controls = dimensions_list($name, $selected_id, $no_option, $showname, $submit_on_change, $showclosed, $showtype);
		View::get()->addComboControls($label, $controls);
	}

	function dimensions_list_row($label, $name, $selected_id = null, $no_option = false, $showname = null, $showclosed = false, $showtype = 0, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->dimensions_list_cells($label, $name, $selected_id, $no_option, $showname, $showclosed, $showtype, $submit_on_change);
	}

	// ---------------------------------------------------------------------------------------------------
	function stock_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $opts = array(), $editkey = false, $type = "stock")
	{
		global $all_items;

		$sql = "SELECT stock_id, s.description, c.description, s.inactive, s.editable
			FROM " . TB_PREF . "stock_master s," . TB_PREF . "stock_category c WHERE s.category_id=c.category_id";
		if (isset($opts['fixed_asset']) && $opts['fixed_asset'])
			$sql .= " AND mb_flag='F'";
		else
			$sql .= " AND mb_flag!='F'";

		if ($editkey)
			set_editor('item', $name, $editkey);

		$ret = combo_input($name, $selected_id, $sql, 'stock_id', 's.description', array_merge(array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option === true ? _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array(
				"stock_id",
				"c.description",
				"s.description"
			),
			'search_submit' => get_company_pref('no_item_list') != 0,
			'size' => 10,
			'select_submit' => $submit_on_change,
			'category' => 2,
			'order' => array(
				'c.description',
				'stock_id'
			),
			'editlink' => $editkey ? add_edit_combo('item') : false,
			'editable' => false,
			'max' => 255
		), $opts), $type);
		return $ret;
	}

	function _format_stock_items($row)
	{
		return (user_show_codes() ? ($row[0] . "&nbsp;-&nbsp;") : "") . $row[1];
	}

	function stock_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false, $opts= array())
	{
		if (isset($opts['fixed_asset']) && $opts['fixed_asset'])
			$editor_item = 'fa_item';
		else
			$editor_item = 'item';
	
		$controls = stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'cells' => true,
			'show_inactive' => $all
		), $editkey);
		View::get()->addComboControls($label, $controls);
	}
	/*
	 * function stock_items_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false) {
	 * echo "<tr>\n"; stock_items_list_cells($label, $name, $selected_id, $all_option, $submit_on_change); echo
	 * "</tr>\n"; }
	 */
	// ---------------------------------------------------------------------------------------------------
	//
	// Select item via foreign code.
	//
	function sales_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $type = '', $opts = array())
	{
		// all sales codes
		$sql = "SELECT i.item_code, i.description, c.description, count(*)>1 as kit,
			 i.inactive, if(count(*)>1, '0', s.editable) as editable
			FROM
			" . TB_PREF . "stock_master s,
			" . TB_PREF . "item_codes i
			LEFT JOIN
			" . TB_PREF . "stock_category c
			ON i.category_id=c.category_id
			WHERE i.stock_id=s.stock_id
			AND mb_flag != 'F'";

		if ($type == 'local') { // exclude foreign codes
			$sql .= " AND !i.is_foreign";
		} elseif ($type == 'kits') { // sales kits
			$sql .= " AND !i.is_foreign AND i.item_code!=i.stock_id";
		}
		$sql .= " AND !i.inactive AND !s.inactive AND !s.no_sale";
		$sql .= " GROUP BY i.item_code";

		return $this->combo_input($name, $selected_id, $sql, 'i.item_code', 'c.description', array_merge(array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option === true ? _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array(
				"i.item_code",
				"c.description",
				"i.description"
			),
			'search_submit' => get_company_pref('no_item_list') != 0,
			'size' => 15,
			'select_submit' => $submit_on_change,
			'category' => 2,
			'order' => array(
				'c.description',
				'i.item_code'
			),
			'editable' => 30,
			'max' => 255
		), $opts));
	}

	function sales_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
	{
		if ($editkey)
			set_editor('item', $name, $editkey);

		$controls = $this->sales_items_list($name, $selected_id, $all_option, $submit_on_change, '', array(
			'cells' => true
		));
		View::get()->addComboControls($label, $controls);
	}

	function sales_kits_list($name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		return sales_items_list($name, $selected_id, $all_option, $submit_on_change, 'kits', array(
			'cells' => false,
			'editable' => false
		));
	}

	function sales_local_items_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$controls = sales_items_list($name, $selected_id, $all_option, $submit_on_change, 'local', array(
			'cells' => false,
			'editable' => false
		));
		View::get()->addComboControls($label, $controls);
	}
	// ------------------------------------------------------------------------------------
	function stock_manufactured_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => array(
				"mb_flag= 'M'"
			)
		), false, "stock_manufactured");
	}

	function stock_manufactured_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		$controls = stock_manufactured_items_list($name, $selected_id, $all_option, $submit_on_change);
		View::get()->addComboControls($label, $controls);
	}

	function stock_manufactured_items_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->stock_manufactured_items_list_cells($label, $name, $selected_id, $all_option, $submit_on_change);
	}
	// ------------------------------------------------------------------------------------
	function stock_component_items_list($name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
	{
		$parent = db_escape($parent_stock_id);
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => array(
				"stock_id != $parent"
			),
			'parent' => $parent_stock_id
		), $editkey, "component");
	}

	function stock_component_items_list_cells($label, $name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
	{
		if ($label != null)
			echo "<td>$label</td>\n";
		$parent = db_escape($parent_stock_id);
		$controls = stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => array(
				"stock_id != '$parent_stock_id'"
			),
			'cells' => true,
			'parent'=> $parent_stock_id
		), $editkey, "component");
	}
	// ------------------------------------------------------------------------------------
	function stock_costable_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => array(
				"mb_flag!='D'"
			)
		), false, "stock_costable");
	}

	function stock_costable_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		$controls = stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => array(
				"mb_flag!='D'"
			),
			'cells' => true
		), false, "stock_costable");
	}

	// ------------------------------------------------------------------------------------
	function stock_purchasable_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false)
	{
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => array(
				"NOT no_purchase"
			),
			'show_inactive' => $all
		), $editkey, "stock_purchased");
	}
	//
	// This helper is used in PO/GRN/PI entry and supports editable descriptions.
	//
	function stock_purchasable_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
	{
		$controls = stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => array(
				"NOT no_purchase"
			),
			'editable' => 30,
			'cells' => true
		), $editkey, "stock_purchased"); // REVIEW: "stock_purchased" not in unstable CP 2016-01 
	}
	// ------------------------------------------------------------------------------------
	function stock_item_types_list_row($label, $name, $selected_id = null, $enabled = true)
	{
		global $stock_types;

		View::get()->layoutHintRow();
		$controlAsString = $this->array_selector($name, $selected_id, $stock_types, array(
			'select_submit' => true,
			'disabled' => ! $enabled
		));
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function stock_units_list_row($label, $name, $value = null, $enabled = true)
	{
		View::get()->layoutHintRow();

		$result = get_all_item_units();
		while ($unit = db_fetch($result))
			$units[$unit['abbr']] = $unit['name'];

		$controls = array_selector($name, $value, $units, array(
			'disabled' => ! $enabled
		));
		View::get()->addComboControls($label, $controls);
	}

	function stock_purchasable_fa_list_cells($label, $name, $selected_id=null, $all_option=false,
		$submit_on_change=false, $all=false, $editkey = false, $exclude_items = array())
	{
		// TODO !!!
		// Check if a fixed asset has been bought.
		$where_opts[] = "stock_id NOT IN
		( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE type=".ST_SUPPRECEIVE." AND qty!=0 )";
	
		// exclude items currently on the order.
		foreach($exclude_items as $item) {
			$where_opts[] = "stock_id != ".db_escape($item->stock_id);
		}
		$where_opts[] = "mb_flag='F'";
	
		echo stock_items_list_cells($label, $name, $selected_id, $all_option, $submit_on_change, $all, $editkey,
			array('fixed_asset' => true, 'where' => $where_opts));
	}
	
	function stock_disposable_fa_list($name, $selected_id=null,
		$all_option=false, $submit_on_change=false)
	{
		// TODO !!!
		// Check if a fixed asset has been bought....
		$where_opts[] = "stock_id IN
		( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE type=".ST_SUPPRECEIVE." AND qty!=0 )";
		// ...but has not been disposed or sold already.
		$where_opts[] = "stock_id NOT IN
		( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_CUSTDELIVERY." OR type=".ST_INVADJUST.") AND qty!=0 )";
	
		$where_opts[] = "mb_flag='F'";
	
		echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
			array('fixed_asset' => true, 'where' => $where_opts));
	}
	
	function stock_disposable_fa_list_cells($label, $name, $selected_id=null,
		$all_option=false, $submit_on_change=false, $exclude_items = array())
	{
		// TODO !!!
		// Check if a fixed asset has been bought....
		$where_opts[] = "stock_id IN
		( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE type=".ST_SUPPRECEIVE." AND qty!=0 )";
		// ...but has not been disposed or sold already.
		$where_opts[] = "stock_id NOT IN
		( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_CUSTDELIVERY." OR type=".ST_INVADJUST.") AND qty!=0 )";
	
		$where_opts[] = "mb_flag='F'";
	
		foreach($exclude_items as $item) {
			$where_opts[] = "stock_id != ".db_escape($item->stock_id);
		}
	
		if ($label != null)
			echo "<td>$label</td>\n";
			echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
				array('fixed_asset' => true, 'cells'=>true, 'where' => $where_opts));
	}
	
	function stock_depreciable_fa_list_cells($label, $name, $selected_id=null,
		$all_option=false, $submit_on_change=false)
	{
		// TODO !!!
	
		// Check if a fixed asset has been bought....
		$where_opts[] = "stock_id IN
		( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE type=".ST_SUPPRECEIVE." AND qty!=0 )";
		// ...but has not been disposed or sold already.
		$where_opts[] = "stock_id NOT IN
		( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_CUSTDELIVERY." OR type=".ST_INVADJUST.") AND qty!=0 )";
	
		$year = get_current_fiscalyear();
		$y = date('Y', strtotime($year['end']));
	
		// check if current fiscal year
		$where_opts[] = "depreciation_date < '".$y."-12-01'";
		$where_opts[] = "depreciation_date >= '".($y-1)."-12-01'";
	
		$where_opts[] = "material_cost > 0";
		$where_opts[] = "mb_flag='F'";
	
		if ($label != null)
			echo "<td>$label</td>\n";
			echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
			 array('fixed_asset' => true, 'where' => $where_opts, 'cells'=>true));
	}

	// ------------------------------------------------------------------------------------
	function tax_types_list($name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		$sql = "SELECT id, CONCAT(name, ' (',rate,'%)') as name FROM " . TB_PREF . "tax_types";

		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'spec_option' => $none_option,
			'spec_id' => ALL_NUMERIC,
			'select_submit' => $submit_on_change,
			'async' => false
		));
	}

	function tax_types_list_cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		$controlAsString = tax_types_list($name, $selected_id, $none_option, $submit_on_change);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function tax_types_list_row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->tax_types_list_cells($label, $name, $selected_id, $none_option, $submit_on_change);
	}

	// ------------------------------------------------------------------------------------
	function tax_groups_list($name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		$sql = "SELECT id, name FROM " . TB_PREF . "tax_groups";

		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'order' => 'id',
			'spec_option' => $none_option,
			'spec_id' => ALL_NUMERIC,
			'select_submit' => $submit_on_change,
			'async' => false
		));
	}

	function tax_groups_list_cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		$controlAsString = tax_groups_list($name, $selected_id, $none_option, $submit_on_change);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function tax_groups_list_row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->tax_groups_list_cells($label, $name, $selected_id, $none_option, $submit_on_change);
	}

	// ------------------------------------------------------------------------------------
	function item_tax_types_list($name, $selected_id = null)
	{
		$sql = "SELECT id, name FROM " . TB_PREF . "item_tax_types";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'order' => 'id'
		));
	}

	function item_tax_types_list_cells($label, $name, $selected_id = null)
	{
		$controls = item_tax_types_list($name, $selected_id);
		View::get()->addComboControls($label, $controls);
	}

	function item_tax_types_list_row($label, $name, $selected_id = null)
	{
		View::get()->layoutHintRow();
		$this->item_tax_types_list_cells($label, $name, $selected_id);
	}

	// ------------------------------------------------------------------------------------
	function shippers_list($name, $selected_id = null)
	{
		$sql = "SELECT shipper_id, shipper_name, inactive FROM " . TB_PREF . "shippers";
		return combo_input($name, $selected_id, $sql, 'shipper_id', 'shipper_name', array(
			'order' => array(
				'shipper_name'
			)
		));
	}

	function shippers_list_cells($label, $name, $selected_id = null)
	{
		$controls = shippers_list($name, $selected_id);
		View::get()->addComboControls($label, $controls);
	}

	function shippers_list_row($label, $name, $selected_id = null)
	{
		View::get()->layoutHintRow();
		$this->shippers_list_cells($label, $name, $selected_id);
	}

	// -------------------------------------------------------------------------------------
	function sales_persons_list($name, $selected_id = null, $spec_opt = false)
	{
		$sql = "SELECT salesman_code, salesman_name, inactive FROM " . TB_PREF . "salesman";
		return combo_input($name, $selected_id, $sql, 'salesman_code', 'salesman_name', array(
			'order' => array(
				'salesman_name'
			),
			'spec_option' => $spec_opt,
			'spec_id' => ALL_NUMERIC
		));
	}

	function sales_persons_list_cells($label, $name, $selected_id = null, $spec_opt = false)
	{
		$controls = sales_persons_list($name, $selected_id, $spec_opt);
		View::get()->addComboControls($label, $controls);
	}

	function sales_persons_list_row($label, $name, $selected_id = null, $spec_opt = false)
	{
		View::get()->layoutHintRow();
		$this->sales_persons_list_cells($label, $name, $selected_id, $spec_opt);
	}

	// ------------------------------------------------------------------------------------
	function sales_areas_list($name, $selected_id = null)
	{
		$sql = "SELECT area_code, description, inactive FROM " . TB_PREF . "areas";
		return combo_input($name, $selected_id, $sql, 'area_code', 'description', array());
	}

	function sales_areas_list_cells($label, $name, $selected_id = null)
	{
		$controls = sales_areas_list($name, $selected_id);
		View::get()->addComboControls($label, $controls);
	}

	function sales_areas_list_row($label, $name, $selected_id = null)
	{
		View::get()->layoutHintRow();
		$this->sales_areas_list_cells($label, $name, $selected_id);
	}

	// ------------------------------------------------------------------------------------
	function sales_groups_list($name, $selected_id = null, $special_option = false)
	{
		$sql = "SELECT id, description, inactive FROM " . TB_PREF . "groups";
		return combo_input($name, $selected_id, $sql, 'id', 'description', array(
			'spec_option' => $special_option === true ? ' ' : $special_option,
			'order' => 'description',
			'spec_id' => 0
		));
	}

	function sales_groups_list_cells($label, $name, $selected_id = null, $special_option = false)
	{
		$controls = sales_groups_list($name, $selected_id, $special_option);
		View::get()->addComboControls($label, $controls);
	}

	function sales_groups_list_row($label, $name, $selected_id = null, $special_option = false)
	{
		View::get()->layoutHintRow();
		$this->sales_groups_list_cells($label, $name, $selected_id, $special_option);
	}

	// ------------------------------------------------------------------------------------
	function _format_template_items($row)
	{
		return ($row[0] . "&nbsp;- &nbsp;" . _("Amount") . "&nbsp;" . $row[1]);
	}

	function templates_list($name, $selected_id = null, $special_option = false)
	{
		$sql = "SELECT sorder.order_no,	Sum(line.unit_price*line.quantity*(1-line.discount_percent)) AS OrderValue
		FROM " . TB_PREF . "sales_orders as sorder, " . TB_PREF . "sales_order_details as line
		WHERE sorder.order_no = line.order_no AND sorder.type = 1 GROUP BY line.order_no";
		return combo_input($name, $selected_id, $sql, 'order_no', 'OrderValue', array(
			'format' => '_format_template_items',
			'spec_option' => $special_option === true ? ' ' : $special_option,
			'order' => 'order_no',
			'spec_id' => 0
		));
	}

	function templates_list_cells($label, $name, $selected_id = null, $special_option = false)
	{
		$controls = templates_list($name, $selected_id, $special_option);
		View::get()->addComboControls($label, $controls);
	}

	function templates_list_row($label, $name, $selected_id = null, $special_option = false)
	{
		$controlAsString = templates_list_cells($label, $name, $selected_id, $special_option);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_COMBO, $label, $controlAsString));
	}

	// ------------------------------------------------------------------------------------
	function workorders_list($name, $selected_id = null)
	{
		$sql = "SELECT id, wo_ref FROM " . TB_PREF . "workorders WHERE closed=0";
		return combo_input($name, $selected_id, $sql, 'id', 'wo_ref', array());
	}

	function workorders_list_cells($label, $name, $selected_id = null)
	{
		$controls = workorders_list($name, $selected_id);
		View::get()->addComboControls($label, $controls);
	}

	function workorders_list_row($label, $name, $selected_id = null)
	{
		View::get()->layoutHintRow();
		$this->workorders_list_cells($label, $name, $selected_id);
	}

	// ------------------------------------------------------------------------------------
	function payment_terms_list($name, $selected_id = null)
	{
		$sql = "SELECT terms_indicator, terms, inactive FROM " . TB_PREF . "payment_terms";
		return combo_input($name, $selected_id, $sql, 'terms_indicator', 'terms', array());
	}

	function payment_terms_list_cells($label, $name, $selected_id = null)
	{
		$controls = payment_terms_list($name, $selected_id);
		View::get()->addComboControls($label, $controls);
	}

	function payment_terms_list_row($label, $name, $selected_id = null)
	{
		View::get()->layoutHintRow();
		$this->payment_terms_list_cells($label, $name, $selected_id);
	}

	// ------------------------------------------------------------------------------------
	function credit_status_list($name, $selected_id = null)
	{
		$sql = "SELECT id, reason_description, inactive FROM " . TB_PREF . "credit_status";
		return combo_input($name, $selected_id, $sql, 'id', 'reason_description', array());
	}

	function credit_status_list_cells($label, $name, $selected_id = null)
	{
		$controls = credit_status_list($name, $selected_id);
		View::get()->addComboControls($label, $controls);
	}

	function credit_status_list_row($label, $name, $selected_id = null)
	{
		View::get()->layoutHintRow();
		$this->credit_status_list_cells($label, $name, $selected_id);
	}

	// -----------------------------------------------------------------------------------------------
	function sales_types_list($name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		$sql = "SELECT id, sales_type, inactive FROM " . TB_PREF . "sales_types";
		return combo_input($name, $selected_id, $sql, 'id', 'sales_type', array(
			'spec_option' => $special_option === true ? _("All Sales Types") : $special_option,
			'spec_id' => 0,
			'select_submit' => $submit_on_change
		));
	}

	function sales_types_list_cells($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		$controls = sales_types_list($name, $selected_id, $submit_on_change, $special_option);
		View::get()->addComboControls($label, $controls);
	}

	function sales_types_list_row($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		View::get()->layoutHintRow();
		$this->sales_types_list_cells($label, $name, $selected_id, $submit_on_change, $special_option);
	}

	// -----------------------------------------------------------------------------------------------

	function _format_date($row)
	{
		return sql2date($row['reconciled']);
	}

	function bank_reconciliation_list($account, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		$sql = "SELECT reconciled, reconciled FROM " . TB_PREF . "bank_trans
		WHERE bank_act=" . db_escape($account) . " AND reconciled IS NOT NULL
		GROUP BY reconciled";
		return combo_input($name, $selected_id, $sql, 'id', 'reconciled', array(
			'spec_option' => $special_option,
			'format' => '_format_date',
			'spec_id' => '',
			'select_submit' => $submit_on_change
		));
	}

	function bank_reconciliation_list_cells($label, $account, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		$controls = bank_reconciliation_list($account, $name, $selected_id, $submit_on_change, $special_option);
		View::get()->addComboControls($label, $controls);
	}
	/*
	function bank_reconciliation_list_row($label, $account, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
	{
		echo "<tr>\n";
		bank_reconciliation_list_cells($label, $account, $name, $selected_id, $submit_on_change, $special_option);
		echo "</tr>\n";
	}
	 */
	// -----------------------------------------------------------------------------------------------
	function workcenter_list($name, $selected_id = null, $all_option = false)
	{
		$sql = "SELECT id, name, inactive FROM " . TB_PREF . "workcentres";

		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'spec_option' => $all_option === true ? _("All Suppliers") : $all_option,
			'spec_id' => ALL_TEXT
		));
	}

	function workcenter_list_cells($label, $name, $selected_id = null, $all_option = false)
	{
		default_focus($name);
		$controls = workcenter_list($name, $selected_id, $all_option);
		View::get()->addComboControls($label, $controls);
	}

	function workcenter_list_row($label, $name, $selected_id = null, $all_option = false)
	{
		View::get()->layoutHintRow();
		$this->workcenter_list_cells($label, $name, $selected_id, $all_option);
	}

	// -----------------------------------------------------------------------------------------------
	function bank_accounts_list($name, $selected_id = null, $submit_on_change = false, $spec_option = false)
	{
		$sql = "SELECT " . TB_PREF . "bank_accounts.id, bank_account_name, bank_curr_code, inactive FROM " . TB_PREF . "bank_accounts";
		return combo_input($name, $selected_id, $sql, 'id', 'bank_account_name', array(
			'format' => '_format_add_curr',
			'select_submit' => $submit_on_change,
			'spec_option' => $spec_option,
			'spec_id' => '',
			'async' => false
		));
	}

	function bank_accounts_list_cells($label, $name, $selected_id = null, $submit_on_change = false)
	{
		$controls = bank_accounts_list($name, $selected_id, $submit_on_change);
		View::get()->addComboControls($label, $controls);
	}

	function bank_accounts_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->bank_accounts_list_cells($label, $name, $selected_id, $submit_on_change);
	}
	// -----------------------------------------------------------------------------------------------
	function cash_accounts_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$sql = "SELECT id, bank_account_name, bank_curr_code, inactive
			FROM " . TB_PREF . "bank_accounts
			WHERE account_type=".BT_CASH;

		$controls = combo_input($name, $selected_id, $sql, 'id', 'bank_account_name', array(
			'spec_option' => $all_option,
			'format' => '_format_add_curr',
			'select_submit' => $submit_on_change,
			'async' => true
		));
		View::get()->addComboControls($label, $controls);
	}
	// -----------------------------------------------------------------------------------------------
	function pos_list_row($label, $name, $selected_id = null, $spec_option = false, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$sql = "SELECT id, pos_name, inactive FROM " . TB_PREF . "sales_pos";

		default_focus($name);

		$controls = combo_input($name, $selected_id, $sql, 'id', 'pos_name', array(
			'select_submit' => $submit_on_change,
			'async' => true,
			'spec_option' => $spec_option,
			'spec_id' => - 1,
			'order' => array(
				'pos_name'
			)
		));
		View::get()->addComboControls($label, $controls);
	}
	// -----------------------------------------------------------------------------------------------
	// Payment type selector for current user.
	//
	function sale_payment_list($name, $category, $selected_id = null, $submit_on_change = true, $prepayments = true)
	{
		$sql = "SELECT terms_indicator, terms, inactive FROM " . TB_PREF . "payment_terms";

		if ($category == PM_CASH) // only cash
			$sql .= " WHERE days_before_due=0 AND day_in_following_month=0";
		elseif ($category == PM_CREDIT) // only delayed payments
			$sql .= " WHERE days_before_due".($prepayments ? '!=': '>')."0 OR day_in_following_month!=0";
		elseif (!$prepayments)
			$sql .= " WHERE days_before_due>=0";

		return combo_input($name, $selected_id, $sql, 'terms_indicator', 'terms', array(
			'select_submit' => $submit_on_change,
			'async' => true
		));
	}

	function sale_payment_list_cells($label, $name, $category, $selected_id = null, $submit_on_change = true, $prepayments = true)
	{
		$controls = sale_payment_list($name, $category, $selected_id, $submit_on_change, $prepayments);
		View::get()->addComboControls($label, $controls);
	}
	// -----------------------------------------------------------------------------------------------
	function class_list($name, $selected_id = null, $submit_on_change = false)
	{
		$sql = "SELECT cid, class_name FROM " . TB_PREF . "chart_class";

		return combo_input($name, $selected_id, $sql, 'cid', 'class_name', array(
			'select_submit' => $submit_on_change,
			'async' => false
		));
	}

	function class_list_cells($label, $name, $selected_id = null, $submit_on_change = false)
	{
		$controls = class_list($name, $selected_id, $submit_on_change);
		View::get()->addComboControls($label, $controls);
	}

	function class_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->class_list_cells($label, $name, $selected_id, $submit_on_change);
	}

	// -----------------------------------------------------------------------------------------------
	function stock_categories_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $fixed_asset = false)
	{
		$where_opts = array();
		if ($fixed_asset)
			$where_opts[0] = "dflt_mb_flag='F'";
		else
			$where_opts[0] = "dflt_mb_flag!='F'";
	
		$sql = "SELECT category_id, description, inactive FROM " . TB_PREF . "stock_category";
		return combo_input($name, $selected_id, $sql, 'category_id', 'description', array(
			'order' => 'category_id',
			'spec_option' => $spec_opt,
			'spec_id' => - 1,
			'select_submit' => $submit_on_change,
	 			'async' => true,
				'where' => $where_opts,
	 		)
		);
	}

	function stock_categories_list_cells($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false, $fixed_asset = false)
	{
		$controls = stock_categories_list($name, $selected_id, $spec_opt, $submit_on_change, $fixed_asset);
		View::get()->addComboControls($label, $controls);
	}

	function stock_categories_list_row($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->stock_categories_list_cells($label, $name, $selected_id, $spec_opt, $submit_on_change);
	}

	// -----------------------------------------------------------------------------------------------
	function fixed_asset_classes_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
	{
		$sql = "SELECT c.fa_class_id, CONCAT(c.fa_class_id,' - ',c.description) `desc`, CONCAT(p.fa_class_id,' - ',p.description) `class`, c.inactive FROM "
			.TB_PREF."stock_fa_class c LEFT JOIN ".TB_PREF."stock_fa_class p ON c.parent_id=p.fa_class_id";
	
		return combo_input($name, $selected_id, $sql, 'c.fa_class_id', 'desc',
			array('order'=>'c.fa_class_id',
				'spec_option' => $spec_opt,
				'spec_id' => '-1',
				'select_submit'=> $submit_on_change,
				'async' => true,
				'search_box' => true,
				'search' => array("c.fa_class_id"),
				'search_submit' => false,
				'spec_id' => '',
				'size' => 3,
				'max' => 3,
				'category' => 'class',
			));
	}
	
	function fixed_asset_classes_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
	{
		// TODO !!!
		echo "<tr><td class='label'>$label</td>";
		echo "<td>";
		echo fixed_asset_classes_list($name, $selected_id, $spec_opt, $submit_on_change);
		echo "</td></tr>\n";
	}

	// -----------------------------------------------------------------------------------------------
	function gl_account_types_list($name, $selected_id = null, $all_option = false, $all = true)
	{
		$sql = "SELECT id, name FROM " . TB_PREF . "chart_types";

		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'format' => '_format_account',
			'order' => array(
				'class_id',
				'id',
				'parent'
			),
			'spec_option' => $all_option,
			'spec_id' => ALL_TEXT
		));
	}

	function gl_account_types_list_cells($label, $name, $selected_id = null, $all_option = false, $all = false)
	{
		$controls = gl_account_types_list($name, $selected_id, $all_option, $all);
		View::get()->addComboControls($label, $controls);
	}

	function gl_account_types_list_row($label, $name, $selected_id = null, $all_option = false, $all = false)
	{
		View::get()->layoutHintRow();
		$this->gl_account_types_list_cells($label, $name, $selected_id, $all_option, $all);
	}

	// -----------------------------------------------------------------------------------------------
	function gl_all_accounts_list($name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false, $submit_on_change = false, $all = false)
	{
		if ($skip_bank_accounts)
			$sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
			FROM (" . TB_PREF . "chart_master chart," . TB_PREF . "chart_types type) " . "LEFT JOIN " . TB_PREF . "bank_accounts acc " . "ON chart.account_code=acc.account_code
				WHERE acc.account_code  IS NULL
			AND chart.account_type=type.id";
		else
			$sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
			FROM " . TB_PREF . "chart_master chart," . TB_PREF . "chart_types type
			WHERE chart.account_type=type.id";

		return combo_input($name, $selected_id, $sql, 'chart.account_code', 'chart.account_name', array(
			'format' => '_format_account',
			'spec_option' => $all_option === true ? _("Use Item Sales Accounts") : $all_option,
			'spec_id' => '',
			'type' => 2,
			'order' => array(
				'type.class_id',
				'type.id',
				'account_code'
			),
			'search_box' => $cells,
			'search_submit' => false,
			'size' => 12,
			'max' => 10,
			'cells' => true,
			'select_submit' => $submit_on_change,
			'async' => false,
			'category' => 2,
			'show_inactive' => $all
		), "account" );
	}

	function _format_account($row)
	{
		return $row[0] . "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[1];
	}

	function gl_all_accounts_list_cells($label, $name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false, $submit_on_change = false, $all = false)
	{
		$controls = gl_all_accounts_list($name, $selected_id, $skip_bank_accounts, $cells, $all_option, $submit_on_change, $all);
		View::get()->addComboControls($label, $controls);
	}

	function gl_all_accounts_list_row($label, $name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false)
	{
		View::get()->layoutHintRow();
		$this->gl_all_accounts_list_cells($label, $name, $selected_id, $skip_bank_accounts, $cells, $all_option);
	}

	function yesno_list($name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false)
	{
		$items = array();
		$items['0'] = strlen($name_no) ? $name_no : _("No");
		$items['1'] = strlen($name_yes) ? $name_yes : _("Yes");

		return array_selector($name, $selected_id, $items, array(
			'select_submit' => $submit_on_change,
			'async' => false
		)); // FIX?
	}

	function yesno_list_cells($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false)
	{
		$controlAsString = yesno_list($name, $selected_id, $name_yes, $name_no, $submit_on_change);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function yesno_list_row($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->yesno_list_cells($label, $name, $selected_id, $name_yes, $name_no, $submit_on_change);
	}

	// ------------------------------------------------------------------------------------------------
	function languages_list($name, $selected_id = null, $all_option = false)
	{
		global $installed_languages;

		$items = array();
		if ($all_option)
			$items[''] = $all_option;
		foreach ($installed_languages as $lang)
			$items[$lang['code']] = $lang['name'];
		return array_selector($name, $selected_id, $items);
	}

	function languages_list_cells($label, $name, $selected_id = null, $all_option = false)
	{
		$controlAsString = languages_list($name, $selected_id, $all_option);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function languages_list_row($label, $name, $selected_id = null, $all_option = false)
	{
		View::get()->layoutHintRow();
		$this->languages_list_cells($label, $name, $selected_id, $all_option);
	}

	// ------------------------------------------------------------------------------------------------
	function bank_account_types_list($name, $selected_id = null)
	{
		global $bank_account_types;

		return array_selector($name, $selected_id, $bank_account_types);
	}

	function bank_account_types_list_cells($label, $name, $selected_id = null)
	{
		$controlAsString = bank_account_types_list($name, $selected_id);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function bank_account_types_list_row($label, $name, $selected_id = null)
	{
		View::get()->layoutHintRow();
		$this->bank_account_types_list_cells($label, $name, $selected_id);
	}

	// ------------------------------------------------------------------------------------------------
	function payment_person_types_list($name, $selected_id = null, $submit_on_change = false)
	{
		global $payment_person_types;

		$items = array();
		foreach ($payment_person_types as $key => $type) {
			if ($key != PT_WORKORDER)
				$items[$key] = $type;
		}
		return array_selector($name, $selected_id, $items, array(
			'select_submit' => $submit_on_change
		));
	}

	function payment_person_types_list_cells($label, $name, $selected_id = null, $related = null)
	{
		$controlAsString = payment_person_types_list($name, $selected_id, $related);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function payment_person_types_list_row($label, $name, $selected_id = null, $related = null)
	{
		View::get()->layoutHintRow();
		$this->payment_person_types_list_cells($label, $name, $selected_id, $related);
	}

	// ------------------------------------------------------------------------------------------------
	function wo_types_list($name, $selected_id = null)
	{
		global $wo_types_array;

		return array_selector($name, $selected_id, $wo_types_array, array(
			'select_submit' => true,
			'async' => true
		));
	}

	function wo_types_list_row($label, $name, $selected_id = null)
	{
		View::get()->layoutHintRow();
		$controlAsString = wo_types_list($name, $selected_id);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	// ------------------------------------------------------------------------------------------------
	function dateformats_list_row($label, $name, $value = null)
	{
		global $SysPrefs;

		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $value, $SysPrefs->dateformats);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function dateseps_list_row($label, $name, $value = null)
	{
		global $SysPrefs;

		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $value, $SysPrefs->dateseps);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function thoseps_list_row($label, $name, $value = null)
	{
		global $SysPrefs;

		View::get()->layoutHintRow();
		$controls = array_selector($name, $value, $SysPrefs->thoseps);
		View::get()->addComboControls($label, $controls);
	}

	function decseps_list_row($label, $name, $value = null)
	{
		global $SysPrefs;

		View::get()->layoutHintRow();
		$controls = array_selector($name, $value, $SysPrefs->decseps);
		View::get()->addComboControls($label, $controls);
	}

	function themes_list_row($label, $name, $value = null)
	{
		global $path_to_root;

		// TODO Move the non view logic elsewhere CP 2014-11
		$path = $path_to_root . '/themes/';
		$themes = array();
		$themedir = opendir($path);
		while (false !== ($fname = readdir($themedir))) {
			if ($fname != '.' && $fname != '..' && $fname != 'CVS' && is_dir($path . $fname)) {
				$themes[$fname] = $fname;
			}
		}
		ksort($themes);

		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $value, $themes);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function pagesizes_list_row($label, $name, $value = null)
	{
		global $SysPrefs;

		// TODO Move the non view logic elsewhere CP 2014-11
		$items = array();
		foreach ($SysPrefs->pagesizes as $pz)
			$items[$pz] = $pz;

		View::get()->layoutHintRow();
		$controls = array_selector($name, $value, $items);
		View::get()->addComboControls($label, $controls);
	}

	function systypes_list($name, $value = null, $spec_opt = false, $submit_on_change = false, $exclude = array())
	{
		global $systypes_array;

		// Remove non-voidable transactions if needed
		$systypes = array_diff_key($systypes_array, array_flip($exclude));
		return array_selector($name, $value, $systypes, array(
			'spec_option' => $spec_opt,
			'spec_id' => ALL_NUMERIC,
			'select_submit' => $submit_on_change,
			'async' => false
		));
	}

	function systypes_list_cells($label, $name, $value = null, $submit_on_change = false, $exclude = array())
	{
		$controlAsString = systypes_list($name, $value, false, $submit_on_change, $exclude);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function systypes_list_row($label, $name, $value = null, $submit_on_change = false, $exclude = array())
	{
		View::get()->layoutHintRow();
		$this->systypes_list_cells($label, $name, $value, $submit_on_change, $exclude);
	}

	function journal_types_list_cells($label, $name, $value = null, $submit_on_change = false)
	{
		global $systypes_array;

		$items = $systypes_array;

		// exclude quotes, orders and dimensions
		foreach (array(
			ST_PURCHORDER,
			ST_SALESORDER,
			ST_DIMENSION,
			ST_SALESQUOTE,
			ST_LOCTRANSFER
		) as $excl)
			unset($items[$excl]);

		$controlAsString = array_selector($name, $value, $items, array(
			'spec_option' => _("All"),
			'spec_id' => - 1,
			'select_submit' => $submit_on_change,
			'async' => false
		));
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function cust_allocations_list_cells($label, $name, $selected = null)
	{

		$allocs = array(
			ALL_TEXT => _("All Types"),
			'1' => _("Sales Invoices"),
			'2' => _("Overdue Invoices"),
			'3' => _("Payments"),
			'4' => _("Credit Notes"),
			'5' => _("Delivery Notes")
		);
		$controlAsString = array_selector($name, $selected, $allocs);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function supp_allocations_list_cell($name, $selected = null)
	{

		$allocs = array(
			ALL_TEXT => _("All Types"),
			'1' => _("Invoices"),
			'2' => _("Overdue Invoices"),
			'3' => _("Payments"),
			'4' => _("Credit Notes"),
			'5' => _("Overdue Credit Notes")
		);
		$controlAsString = array_selector($name, $selected, $allocs);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function supp_transactions_list_cell($name, $selected = null)
	{
		$allocs = array(
			ALL_TEXT => _("All Types"),
			'6' => _("GRNs"),
			'1' => _("Invoices"),
			'2' => _("Overdue Invoices"),
			'3' => _("Payments"),
			'4' => _("Credit Notes"),
			'5' => _("Overdue Credit Notes")
		);

		$controlAsString = array_selector($name, $selected, $allocs);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function policy_list_cells($label, $name, $selected = null)
	{
		$controlAsString = array_selector($name, $selected, array(
			'' => _("Automatically put balance on back order"),
			'CAN' => _("Cancel any quantites not delivered")
		));
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function policy_list_row($label, $name, $selected = null)
	{
		View::get()->layoutHintRow();
		$this->policy_list_cells($label, $name, $selected);
	}

	function credit_type_list_cells($label, $name, $selected = null, $submit_on_change = false)
	{
		$controlAsString = array_selector($name, $selected, array(
			'Return' => _("Items Returned to Inventory Location"),
			'WriteOff' => _("Items Written Off")
		), array(
			'select_submit' => $submit_on_change
		));
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function credit_type_list_row($label, $name, $selected = null, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->credit_type_list_cells($label, $name, $selected, $submit_on_change);
	}

	function number_list($name, $selected, $from, $to, $no_option = false)
	{
		$items = array();
		for ($i = $from; $i <= $to; $i ++)
			$items[$i] = "$i";

		return array_selector($name, $selected, $items, array(
			'spec_option' => $no_option,
			'spec_id' => ALL_NUMERIC
		));
	}

	function number_list_cells($label, $name, $selected, $from, $to, $no_option = false)
	{
		$controlAsString = number_list($name, $selected, $from, $to, $no_option);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function number_list_row($label, $name, $selected, $from, $to, $no_option = false)
	{
		View::get()->layoutHintRow();
		$this->number_list_cells($label, $name, $selected, $from, $to, $no_option);
	}

	function print_profiles_list_row($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = true)
	{
		$sql = "SELECT profile FROM " . TB_PREF . "print_profiles" . " GROUP BY profile";
		$result = db_query($sql, 'cannot get all profile names');
		$profiles = array();
		while ($myrow = db_fetch($result)) {
			$profiles[$myrow['profile']] = $myrow['profile'];
		}

		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $selected_id, $profiles, array(
			'select_submit' => $submit_on_change,
			'spec_option' => $spec_opt,
			'spec_id' => ''
		));
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function printers_list($name, $selected_id = null, $spec_opt = false, $submit_on_change = false)
	{
		static $printers; // query only once for page display

		if (! $printers) {
			$sql = "SELECT id, name, description FROM " . TB_PREF . "printers";
			$result = db_query($sql, 'cannot get all printers');
			$printers = array();
			while ($myrow = db_fetch($result)) {
				$printers[$myrow['id']] = $myrow['name'] . '&nbsp;-&nbsp;' . $myrow['description'];
			}
		}
		return array_selector($name, $selected_id, $printers, array(
			'select_submit' => $submit_on_change,
			'spec_option' => $spec_opt,
			'spec_id' => ''
		));
	}

	// ------------------------------------------------------------------------------------------------
	function quick_entries_list($name, $selected_id = null, $type = null, $submit_on_change = false)
	{
		$where = false;
		$sql = "SELECT id, description FROM " . TB_PREF . "quick_entries";
		if ($type != null)
			$sql .= " WHERE type=$type";

		return combo_input($name, $selected_id, $sql, 'id', 'description', array(
			'spec_id' => '',
			'order' => 'description',
			'select_submit' => $submit_on_change,
			'async' => false
		));
	}

	function quick_entries_list_cells($label, $name, $selected_id = null, $type, $submit_on_change = false)
	{
		$controls = quick_entries_list($name, $selected_id, $type, $submit_on_change);
		View::get()->addComboControls($label, $controls);
	}

	function quick_entries_list_row($label, $name, $selected_id = null, $type, $submit_on_change = false)
	{
		View::get()->layoutHintRow();
		$this->quick_entries_list_cells($label, $name, $selected_id, $type, $submit_on_change);
	}

	function quick_actions_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		global $quick_actions;

		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $selected_id, $quick_actions, array(
			'select_submit' => $submit_on_change
		));
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function quick_entry_types_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		global $quick_entry_types;

		View::get()->layoutHintRow();
		$controls = array_selector($name, $selected_id, $quick_entry_types, array(
			'select_submit' => $submit_on_change
		));
		View::get()->addComboControls($label, $controls);
	}

	function record_status_list_row($label, $name)
	{
		return yesno_list_row($label, $name, null, _('Inactive'), _('Active'));
	}

	function class_types_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		global $class_types;

		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $selected_id, $class_types, array(
			'select_submit' => $submit_on_change
		));
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	// ------------------------------------------------------------------------------------------------
	function security_roles_list($name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false)
	{
		$sql = "SELECT id, role, inactive FROM " . TB_PREF . "security_roles";

		return combo_input($name, $selected_id, $sql, 'id', 'description', array(
			'spec_option' => $new_item ? _("New role") : false,
			'spec_id' => '',
			'select_submit' => $submit_on_change,
			'show_inactive' => $show_inactive
		));
	}

	function security_roles_list_cells($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false)
	{
		$controls = security_roles_list($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
		View::get()->addComboControls($label, $controls);
	}

	function security_roles_list_row($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false)
	{
		View::get()->layoutHintRow();
		$this->security_roles_list_cells($label, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
	}

	function tab_list_row($label, $name, $selected_id = null)
	{
		global $installed_extensions;

		$tabs = array();
		foreach ($_SESSION['App']->applications as $app) {
			$tabs[$app->id] = access_string($app->name, true);
		}
		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $selected_id, $tabs);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	// -----------------------------------------------------------------------------------------------
	function tag_list($name, $height, $type, $multi = false, $all = false, $spec_opt = false)
	{
		// Get tags
		global $path_to_root;
		include_once ($path_to_root . "/admin/db/tags_db.inc");
		$results = get_tags($type, $all);

		while ($tag = db_fetch($results))
			$tags[$tag['id']] = $tag['name'];

		if (! isset($tags)) {
			$tags[''] = $all ? _("No tags defined.") : _("No active tags defined.");
			$spec_opt = false;
		}
		return array_selector($name, null, $tags, array(
			'multi' => $multi,
			'height' => $height,
			'spec_option' => $spec_opt,
			'spec_id' => - 1
		));
	}

	function tag_list_cells($label, $name, $height, $type, $mult = false, $all = false, $spec_opt = false)
	{
		$controlAsString = tag_list($name, $height, $type, $mult, $all, $spec_opt);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function tag_list_row($label, $name, $height, $type, $mult = false, $all = false, $spec_opt = false)
	{
		View::get()->layoutHintRow();
		$this->tag_list_cells($label, $name, $height, $type, $mult, $all, $spec_opt);
	}

	// ---------------------------------------------------------------------------------------------
	// List of sets of active extensions
	//
	function extset_list($name, $value = null, $submit_on_change = false)
	{
		global $db_connections;

		$items = array();
		foreach ($db_connections as $comp)
			$items[] = sprintf(_("Activated for '%s'"), $comp['name']);
		return array_selector($name, $value, $items, array(
			'spec_option' => _("Available and/or installed"),
			'spec_id' => - 1,
			'select_submit' => $submit_on_change,
			'async' => true
		));
	}

	function crm_category_types_list($name, $selected_id = null, $filter = array(), $submit_on_change = true)
	{
		$sql = "SELECT id, name, type, inactive FROM " . TB_PREF . "crm_categories";

		$multi = false;
		$groups = false;
		$where = array();
		if (@$filter['class']) {
			$where[] = 'type=' . db_escape($filter['class']);
		} else
			$groups = 'type';
		if (@$filter['subclass'])
			$where[] = 'action=' . db_escape($filter['subclass']);
		if (@$filter['entity'])
			$where[] = 'entity_id=' . db_escape($filter['entity']);
		if (@$filter['multi']) { // contact category selector for person
			$multi = true;
		}

		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'multi' => $multi,
			'height' => $multi ? 5 : 1,
			'category' => $groups,
			'select_submit' => $submit_on_change,
			'async' => true,
			'where' => $where
		));
	}

	function crm_category_types_list_row($label, $name, $selected_id = null, $filter = array(), $submit_on_change = true)
	{
		View::get()->layoutHintRow();
		$controls = crm_category_types_list($name, $selected_id, $filter, $submit_on_change);
		View::get()->addComboControls($label, $controls);
	}

	function payment_type_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		global $pterm_types;

		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $selected_id, $pterm_types, array(
			'select_submit' => $submit_on_change
		));
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function coa_list_row($label, $name, $value = null)
	{
		global $path_to_root, $installed_extensions;

		$path = $path_to_root . '/sql/';
		$coas = array();
		$sqldir = opendir($path);
		while (false !== ($fname = readdir($sqldir))) {
			if (is_file($path . $fname) && substr($fname, - 4) == '.sql' && @($fname[2] == '_')) {
				$ext = array_search_value($fname, $installed_extensions, 'sql');
				if ($ext != null) {
					$descr = $ext['name'];
				} elseif ($fname == 'en_US-new.sql') { // two standard COAs
					$descr = _("Standard new company American COA (4 digit)");
				} elseif ($fname == 'en_US-demo.sql') {
					$descr = _("Standard American COA (4 digit) with demo data");
				} else
					$descr = $fname;

				$coas[$fname] = $descr;
			}
		}
		ksort($coas);

		View::get()->layoutHintRow();
		$controlAsString = array_selector($name, $value, $coas);
		View::get()->addControl(View::controlFromRenderedString(View::CONTROL_ARRAY, $label, $controlAsString));
	}

	function payment_services($name)
	{
		global $payment_services;

		$services = array_combine(array_keys($payment_services), array_keys($payment_services));

		return array_selector($name, null, $services, array(
			'spec_option' => _("No payment Link"),
			'spec_id' => ''
		));
	}
	
	function tax_algorithm_list($name, $value=null, $submit_on_change = false)
	{
		// TODO !!!
		global $tax_algorithms;
	
		return array_selector($name, $value, $tax_algorithms,
			array(
				'select_submit'=> $submit_on_change,
				'async' => true,
			)
			);
	}
	
	function tax_algorithm_list_cells($label, $name, $value=null, $submit_on_change=false)
	{
		// TODO !!!
			echo "<td>$label</td>\n";
			echo "<td>";
			echo tax_algorithm_list($name, $value, $submit_on_change);
			echo "</td>\n";
	}
	
	function tax_algorithm_list_row($label, $name, $value=null, $submit_on_change=false)
	{
		// TODO !!!
		tax_algorithm_list_cells(null, $name, $value, $submit_on_change);
		echo "</tr>\n";
	}
	
	function refline_list($name, $type, $value=null, $spec_option=false)
	{
		// TODO !!!
	
		$where = array();
	
		if (isset($type))
			$where = array('`trans_type`='.db_escape($type));
	
			return combo_input($name, $value, $sql, 'id', 'prefix',
				array(
					'order'=>array('prefix'),
					'spec_option' => $spec_option,
					'spec_id' => '',
					'type' => 2,
					'where' => $where,
					'select_submit' => true,
				)
				);
	}
	
	function refline_list_row($label, $name, $type, $selected_id=null, $spec_option=false)
	{
		// TODO !!!
		if ($label != null)
			echo "<td class='label'>$label</td>\n";
			echo "<td>";
	
			echo refline_list($name, $type, $selected_id, $spec_option);
			echo "</td></tr>\n";
	}
	
	
	//----------------------------------------------------------------------------------------------
	
	function subledger_list($name, $account, $selected_id=null)
	{
		// TODO !!!
		$type = is_subledger_account($account);
		if (!$type)
			return '';
	
		if($type > 0)
			$sql = "SELECT DISTINCT d.debtor_no as id, debtor_ref as name
					FROM "
					.TB_PREF."debtors_master d,"
					.TB_PREF."cust_branch c
					WHERE d.debtor_no=c.debtor_no AND c.receivables_account=".db_escape($account);
		else
			$sql = "SELECT supplier_id as id, supp_ref as name
					FROM "
					.TB_PREF."suppliers s
					WHERE s.payable_account=".db_escape($account);

		$mode = get_company_pref('no_customer_list');

		return combo_input($name, $selected_id, $sql, 'id', 'name',
			array(
				'type' => 1,
				'size' => 20,
				'async' => false,
			));
	}
	
	function subledger_list_cells($label, $name, $account, $selected_id=null)
	{
		// TODO !!!
			echo "<td>$label</td>\n";
			echo "<td nowrap>";
			echo subledger_list($name, $account, $selected_id);
			echo "</td>\n";
	}
	
	function subledger_list_row($label, $name, $selected_id=null, $all_option = false,
		$submit_on_change=false, $show_inactive=false, $editkey = false)
	{
		// TODO !!!
		echo subledger_list($name, $account, $selected_id);
		echo "</td>\n</tr>\n";
	}
	
	function accounts_type_list_row($label, $name, $selected_id=null)
	{
		// TODO !!!
		if ($label != null)
			echo "<td class='label'>$label</td>\n";
		echo "<td>";
		$sel = array(_("Numeric"), _("Alpha Numeric"), _("ALPHA NUMERIC"));
		echo array_selector($name, $selected_id, $sel);
		echo "</td></tr>\n";
	}
	
	function users_list_cells($label, $name, $selected_id=null, $submit_on_change=false, $spec_opt=true)
	{
		// TODO !!!
		$sql = " SELECT user_id, real_name FROM ".TB_PREF."users";
	
		if ($label != null)
			echo "<td>$label</td>\n";
		echo "<td>";
	
		echo combo_input($name, $selected_id, $sql, 'user_id', 'real_name',
			array(
				'spec_option' => $spec_opt===true ?_("All users") : $spec_opt,
				'spec_id' => '',
				'order' => 'real_name',
				'select_submit'=> $submit_on_change,
				'async' => false
			) );
		echo "</td>";
	
	}
	
	function collations_list_row($label, $name, $selected_id=null)
	{
		// TODO !!!
	
		echo "<tr>";
		if ($label != null)
			echo "<td class='label'>$label</td>\n";
		echo "<td>";
	
		echo array_selector($name, $selected_id, $supported_collations,
			array('select_submit'=> false) );
		echo "</td></tr>\n";
	}
	
	
}

?>
