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

	public $columnSpan;

	public function __construct($content) {
		$this->content = $content;
	}
}

class View
{

	const LAYOUT_UNKNOWN  = 'unknown';
	const LAYOUT_HIDDEN   = 'hidden';
	const LAYOUT_INLINE   = 'inline';
	const LAYOUT_FORM1COL = 'form1';
	const LAYOUT_FORM2COL = 'form2';
	const LAYOUT_FORM3COL = 'form3';
	const LAYOUT_TABLE    = 'table';

	const CONTROL_HEADING  = 'heading';
	const CONTROL_TEXT     = 'text';
	const CONTROL_TEXTAREA = 'textarea';
	const CONTROL_CHECK    = 'check';
	const CONTROL_RADIO    = 'radio';
	const CONTROL_COMBO    = 'combo';
	const CONTROL_CALENDAR = 'calendar';
	const CONTROL_BUTTON   = 'button';
	const CONTROL_ARRAY    = 'array'; // @see array_selector
	const CONTROL_HIDDEN   = 'hidden';
	const CONTROL_LABEL    = 'label';
	const CONTROL_FILE     = 'file';
	const CONTROL_LINK     = 'link';

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
		$this->currentTableRow = null;
		$this->currentTableBody = null;
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
			$this->layout == self::LAYOUT_FORM3COL ||
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
		} elseif ($columnNumber == 3) {
			$this->layout = self::LAYOUT_FORM3COL;
			$this->column = 3;
		} elseif ($columnNumber > 3) {
			throw new \Exception('More than 3 columns is not supported');
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
		} elseif ($this->layout == self::LAYOUT_UNKNOWN) {
			if ($control->type != self::CONTROL_HIDDEN) {
				$this->layout = self::LAYOUT_INLINE;
			}
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
		$this->currentTableBody = null;
		$this->currentTableRow = null;
	}

	public function tableRowStart()
	{
		if (!$this->layout == self::LAYOUT_TABLE) {
			return;
		}
		$this->tableEnsureHasBody();
		$this->currentTableRow = new TableElement(TableElement::TE_ROW);
	}

	public function tableRowEnd()
	{
		if (!$this->layout == self::LAYOUT_TABLE) {
			return;
		}
		if (!$this->currentTableBody) {
			$this->currentTableBody = new TableElement(TableElement::TE_BODY);
			$this->controls[] = $this->currentTableBody;
		}
		$this->currentTableBody->content[] = $this->currentTableRow;
		$this->currentTableRow = null;
	}

	private function tableEnsureHasBody()
	{
		if (! $this->currentTableBody) {
			$this->currentTableBody = new TableElement(TableElement::TE_BODY);
			$this->controls[] = $this->currentTableBody;
		}
	}

	private function tableEnsureHasRow()
	{
		if (!$this->currentTableRow) {
			$this->tableEnsureHasBody();
			$this->currentTableRow = new TableElement(TableElement::TE_ROW);
		}
	}

	public function tableAddCell($cellAsString)
	{
		$this->tableEnsureHasRow();
		$this->currentTableRow->content[] = new TableCell($cellAsString);
	}

	public function tableAddCellSpanningColumns($cellAsString, $columnSpan)
	{
		$tableCell = new TableCell($cellAsString);
		$tableCell->columnSpan = $columnSpan;
		$this->currentTableRow->content[] = $tableCell;
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
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view_2col', $context);
				break;

			case self::LAYOUT_FORM3COL:
				$columns = array();
				$columns[0] = array(
					'controls' => array(),
					'class' => 'col-sm-4'
				);
				$columns[1] = array(
					'controls' => array(),
					'class' => 'col-sm-4'
				);
				$columns[2] = array(
					'controls' => array(),
					'class' => 'col-sm-4'
				);
				foreach ($this->controls as $control) {
					$columns[$control->column - 1]['controls'][] = $control;
				}
				$context = array(
					'columns' => $columns,
					'layout' => $this->layout
				);
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view_3col', $context);
				break;

			case self::LAYOUT_TABLE:
				$context = array(
					'controls' => $this->controls,
					'layout' => $this->layout
				);
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view_table', $context);
				break;

			case self::LAYOUT_FORM1COL:
				$context = array(
					'controls' => $this->controls,
					'layout' => $this->layout
				);
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view_1col_horizontal', $context);
				break;

			case self::LAYOUT_INLINE:
				$context = array(
					'controls' => $this->controls,
					'layout' => $this->layout
				);
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view_inline', $context);
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