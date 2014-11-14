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

	public static function fontAwesomeIcon($icon)
	{
		switch ($icon) {
			case ICON_EDIT:
				return 'fa-edit';
			case ICON_DELETE:
				return 'fa-times';
			case ICON_ADD:
				return 'fa-check';
			case ICON_UPDATE:
				return 'fa-check';
			case ICON_OK:
				return 'fa-check';
			case ICON_CANCEL:
				return 'fa-times';
			case ICON_GL:
				return '';
			case ICON_PRINT:
				return 'fa-print';
			case ICON_PDF:
				return 'fa-file-pdf-o';
			case ICON_DOC:
				return 'fa-file-o';
			case ICON_CREDIT:
				return '';
			case ICON_RECEIVE:
				return '';
			case ICON_DOWN:
				return '';
			case ICON_MONEY:
				return 'fa-dollar';
			case ICON_REMOVE:
				return 'fa-times';
			case ICON_REPORT:
				return 'fa-file-text-o';
			case ICON_VIEW:
				return '';
			case ICON_SUBMIT:
				return 'fa-check';
			case ICON_ESCAPE:
				return 'fa-rotate-left';
			case ICON_ALLOC:
				return '';

		}
		return '';
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