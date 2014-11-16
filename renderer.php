<?php

use FA\Theme\Bootstrap\ThemeBootstrap;
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

require_once (__DIR__ . '/theme.php');

class renderer
{

	function get_icon($category)
	{
		global $path_to_root, $show_menu_category_icons;

		if ($show_menu_category_icons)
			$img = $category == '' ? 'right.gif' : $category . '.png';
		else
			$img = 'right.gif';
		return "<img src='$path_to_root/themes/default/images/$img' style='vertical-align:middle;' border='0'>&nbsp;&nbsp;";
	}

	function wa_header()
	{
		page(_($help_context = "Main Menu"), false, true);
	}

	function wa_footer()
	{
		end_page(false, true);
	}

	function menu_header($title, $no_menu, $is_index)
	{
		global $path_to_root, $help_base_url, $db_connections;
		if (! $no_menu) {
			$context = array();
			$context['themePath'] = $path_to_root . '/themes/' . user_theme();
			$context['selected_application'] = $_SESSION['sel_app'];
			$context['applications'] = array();
			$local_path_to_root = $path_to_root;
			$applications = $_SESSION['App']->applications;
			foreach ($applications as $app) {
				if ($_SESSION["wa_current_user"]->check_application_access($app)) {
					$acc = access_string($app->name);
					$context['applications'][] = array(
						'id' => $app->id,
						'link' => $local_path_to_root . '/index.php?application=' . $app->id,
						'name' => $acc[0],
						'class' => ($app->id == $_SESSION['sel_app']) ? 'active' : '',
						'accessKey' => $acc[1],
					);
				}
			}

			$navBar = ThemeBootstrap::get()->render('nav.twig.html', $context);
			echo $navBar;

			// top status bar
			$context = array(
				'rootPath' => $local_path_to_root,
				'companyName' => $db_connections[$_SESSION["wa_current_user"]->company]["name"],
				'serverName' => $_SERVER['SERVER_NAME'],
				'userDisplayName' => $_SESSION["wa_current_user"]->name,
				'userName' => $_SESSION["wa_current_user"]->username,
				'ajaxIndicator' => "$path_to_root/themes/" . user_theme() . "/images/ajax-loader.gif",

			);
			$statusBar = ThemeBootstrap::get()->renderBlock('page.twig.html', 'status', $context);
			echo $statusBar;
		}
// 		echo "</td></tr></table>";

// 		if ($no_menu)
// 			echo "<br>";
// 		elseif ($title && ! $is_index) {
// 			echo "<center><table id='title'><tr><td width='100%' class='titletext'>$title</td>" . "<td align=right>" . (user_hints() ? "<span id='hints'></span>" : '') . "</td>" . "</tr></table></center>";
// 		}
	}

	function menu_footer($no_menu, $is_index)
	{
		global $version, $allow_demo_mode, $app_title, $power_url, $power_by, $path_to_root, $Pagehelp, $Ajax;
		include_once ($path_to_root . "/includes/date_functions.inc");

		$context = array(
			'isIndex' => $is_index,
			'date' => Today(),
			'time' => Now(),
		);

		if ($no_menu == false) {
			$footer = ThemeBootstrap::get()->renderBlock('page.twig.html', 'footer', $context);
			echo $footer;
		}
	}

	function display_applications(&$waapp)
	{
		global $path_to_root;

		$selected_app = $waapp->get_selected_application();
		if (! $_SESSION["wa_current_user"]->check_application_access($selected_app))
			return;

		if (method_exists($selected_app, 'render_index')) {
			$selected_app->render_index();
			return;
		}

		echo "<table width=100% cellpadding='0' cellspacing='0'>";
		foreach ($selected_app->modules as $module) {
			if (! $_SESSION["wa_current_user"]->check_module_access($module))
				continue;
				// image
			echo "<tr>";
			// values
			echo "<td valign='top' class='menu_group'>";
			echo "<table border=0 width='100%'>";
			echo "<tr><td class='menu_group'>";
			echo $module->name;
			echo "</td></tr><tr>";
			echo "<td class='menu_group_items'>";

			foreach ($module->lappfunctions as $appfunction) {
				$img = $this->get_icon($appfunction->category);
				if ($appfunction->label == "")
					echo "&nbsp;<br>";
				elseif ($_SESSION["wa_current_user"]->can_access_page($appfunction->access)) {
					echo $img . menu_link($appfunction->link, $appfunction->label) . "<br>\n";
				} elseif (! $_SESSION["wa_current_user"]->hide_inaccessible_menu_items()) {
					echo $img . '<span class="inactive">' . access_string($appfunction->label, true) . "</span><br>\n";
				}
			}
			echo "</td>";
			if (sizeof($module->rappfunctions) > 0) {
				echo "<td width='50%' class='menu_group_items'>";
				foreach ($module->rappfunctions as $appfunction) {
					$img = $this->get_icon($appfunction->category);
					if ($appfunction->label == "")
						echo "&nbsp;<br>";
					elseif ($_SESSION["wa_current_user"]->can_access_page($appfunction->access)) {
						echo $img . menu_link($appfunction->link, $appfunction->label) . "<br>\n";
					} elseif (! $_SESSION["wa_current_user"]->hide_inaccessible_menu_items()) {
						echo $img . '<span class="inactive">' . access_string($appfunction->label, true) . "</span><br>\n";
					}
				}
				echo "</td>";
			}

			echo "</tr></table></td></tr>";
		}
		echo "</table>";
	}
}
?>