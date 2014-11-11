<?php
namespace FA\Theme\Bootstrap;

class Control
{
	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var string
	 */
	public $label;

	/**
	 * @var string
	 */
	public $controlAsString;

	/**
	 * @var int
	 */
	public $column;

	public function __construct($type)
	{
		$this->type = $type;
	}

}

class View
{

	const LAYOUT_UNKNOWN = 'unknown';
	const LAYOUT_INLINE = 'form-inline';
	const LAYOUT_FORM1COL = 'form1';
	const LAYOUT_FORM2COL = 'form2';
	const LAYOUT_TABLE = 'table';

	const CONTROL_HEADING = 'heading';
	const CONTROL_TEXT = 'text';
	const CONTROL_TEXTAREA = 'textarea';
	const CONTROL_CHECK = 'check';
	const CONTROL_RADIO = 'radio';
	const CONTROL_COMBO = 'combo';
	const CONTROL_CALENDAR = 'calendar';
	const CONTROL_BUTTON = 'button';
	const CONTROL_ARRAY = 'array'; // @see array_selector
	const CONTROL_HIDDEN = 'hidden';
	const CONTROL_LABEL = 'label';

	private $controls;
	private $rowCount;
	private $layout;
	private $column;

	private function __construct()
	{
		$this->reset();
	}

	private function reset()
	{
		$this->controls = array();
		$this->rowCount = 0;
		$this->layout = self::LAYOUT_UNKNOWN;
		$this->column = 1;
	}

	/**
	 * @return \FA\Theme\Bootstrap\View
	 */
	public static function get()
	{
		static $instance = null;
		if ($instance == null) {
			$instance = new View();
		}
		return $instance;
	}

	/**
	 * @param string $label
	 * @param string $controlAsString
	 * @return \FA\Theme\Bootstrap\Control
	 */
	public static function controlFromRenderedString($type, $label, $controlAsString)
	{
		$control = new Control($type);
		$control->label = self::stripColon($label);
		$control->controlAsString = $controlAsString;
		return $control;
	}

	/**
	 * @param string $heading
	 * @return \FA\Theme\Bootstrap\Control
	 */
	public static function controlHeading($heading)
	{
		$control = new Control(View::CONTROL_HEADING);
		$control->label = self::stripColon($heading);
		return $control;
	}

	private static function stripColon($s)
	{
		return rtrim($s, ':');
	}

	public function layoutHintRow()
	{
		$this->rowCount++;
		if ($this->rowCount == 1) {
			$this->layout = self::LAYOUT_INLINE;
		} elseif ($this->rowCount >= 2 && $this->layout != self::LAYOUT_FORM2COL) {
			$this->layout = self::LAYOUT_FORM1COL;
		}

	}

	public function layoutHintColumn($columnNumber)
	{
		if ($columnNumber == 2) {
			$this->layout = self::LAYOUT_FORM2COL;
			$this->column = 2;
		}
	}

	public function addControl($control)
	{
		$control->column = $this->column;
		$this->controls[] = $control;
	}

	public function render()
	{
		switch ($this->layout) {
			case self::LAYOUT_FORM2COL:
				$columns = array();
				$columns[0] = array(
					'controls' => array()
				);
				$columns[1] = array(
					'controls' => array()
				);
				foreach ($this->controls as $control) {
					if ($control->column == 1) {
						$columns[0]['controls'][] = $control;
					} else {
						$columns[1]['controls'][] = $control;
					}
				}
				$context = array(
					'columns' => $columns,
					'layout' => $this->layout
				);
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view2col', $context);
				break;
			default:
				$context = array(
					'controls' => $this->controls,
					'layout' => $this->layout
				);
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view', $context);
		}
		$this->reset();
	}

}