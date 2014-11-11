<?php

use FA\Theme\Bootstrap\ThemeBootstrap;
use FA\Theme\Bootstrap\ControlRendererBootstrap;
use FA\Theme\Bootstrap\InputRendererBootstrap;
use FA\Theme\Bootstrap\ListRendererBootstrap;

require_once (__DIR__ . '/vendor/autoload.php');

include_once($path_to_root . "/includes/ui/ListRenderer.inc");
include_once($path_to_root . "/includes/ui/InputRenderer.inc");

/*
 * One day this will be called by the core, but not at this time CP 2014-11
 */
function theme_init()
{
	global $path_to_root;

	\ControlRenderer::get(new ControlRendererBootstrap());
	\InputRenderer::get(new InputRendererBootstrap());
	\ListRenderer::get(new ListRendererBootstrap());
}

theme_init();