<?php
namespace FA\Theme\Bootstrap;

class ThemeBootstrap
{
	private $twig;

	const THEME_PATH = __DIR__;

	public function __construct()
	{
		$loader = new \Twig_Loader_Filesystem(__DIR__ . '/views');
		$this->twig = new \Twig_Environment($loader, array(
// 			'cache' => __DIR__ . '/cache'
			'cache' => false
		));
	}

	public static function get()
	{
		static $instance = null;
		if ($instance == null) {
			$instance = new ThemeBootstrap();
		}
		return $instance;
	}

	public function render($template, $context)
	{
		return $this->twig->render($template, $context);
	}

	public function renderBlock($template, $block, $context)
	{
		$template = $this->twig->loadTemplate($template);
		return $template->renderBlock($block, $context);
	}
}