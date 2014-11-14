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

class TableElement
{
	const TE_HEADER = 'header';
	const TE_BODY = 'body';
	const TE_ROW = 'row';

	public $type;

	public $content;

	public function __construct($type)
	{
		$this->type = $type;
		$this->content = array();
	}
}

class TableCell
{
	public $content;

	public function __construct($content) {
		$this->content = $content;
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
	const CONTROL_FILE = 'file';

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
		if ($this->layout == self::LAYOUT_FORM2COL ||
			$this->layout == self::LAYOUT_TABLE
		) {
			return;
		}
		$this->rowCount++;
		if ($this->rowCount == 1) {
			$this->layout = self::LAYOUT_INLINE;
		} elseif ($this->rowCount >= 2) {
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

	/**
	 * @param Control $control
	 */
	public function addControl($control)
	{
		if ($this->layout == self::LAYOUT_TABLE) {
			$this->tableAddCell($control->controlAsString);
			return;
		}
		$control->column = $this->column;
		$this->controls[] = $control;
	}

	public function tableAddHeader($cells)
	{
		$this->layout = self::LAYOUT_TABLE;
		$tableElement = new TableElement(TableElement::TE_HEADER);
		$tableElement->content = $cells;
		$this->controls[] = $tableElement;
	}

	public function tableRowStart()
	{
		$this->layout = self::LAYOUT_TABLE;
		$this->currentTableRow = new TableElement(TableElement::TE_ROW);
	}

	public function tableRowEnd()
	{
		if (!isset($this->currentTableBody)) {
			$this->currentTableBody = new TableElement(TableElement::TE_BODY);
			$this->controls[] = $this->currentTableBody;
		}
		$this->currentTableBody->content[] = $this->currentTableRow;
		$this->currentTableRow = null;
	}

	public function tableAddCell($cellAsString)
	{
		$this->currentTableRow->content[] = new TableCell($cellAsString);
	}

	public function tableEnd()
	{
		if ($this->layout != self::LAYOUT_TABLE) {
			return;
		}
		// Currently nothing to do.
	}


	public function render()
	{
		if (count($this->controls) == 0) {
			return;
		}
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

			case self::LAYOUT_TABLE:
				$context = array(
					'controls' => $this->controls,
					'layout' => $this->layout
				);
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view_table', $context);
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