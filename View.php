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

class ColumnDefinition
{
	/**
	 * @var string
	 */
	public $columnClass;

	/**
	 * @var string
	 */
	public $labelClass;

	/**
	 * @var string
	 */
	public $controlClass;

	/**
	 * @param string $columnClass
	 * @param string $labelClass
	 * @param string $controlClass
	 */
	public function __construct($columnClass, $labelClass, $controlClass)
	{
		$this->columnClass = $columnClass;
		$this->labelClass = $labelClass;
		$this->controlClass = $controlClass;
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
	const CONTROL_DATE     = 'date';
	const CONTROL_BUTTON   = 'button';
	const CONTROL_ARRAY    = 'array'; // @see array_selector
	const CONTROL_HIDDEN   = 'hidden';
	const CONTROL_LABEL    = 'label';
	const CONTROL_FILE     = 'file';
	const CONTROL_LINK     = 'link';

	private $controls;
	private $rowCount;

	/**
	 * @see LAYOUT_ constants
	 * @var string
	 */
	private $layout;

	/**
	 * @var int
	 */
	private $column;

	/**
	 * @var array<ColumnDefinition>
	 */
	private $columnDefinition;

	private function __construct()
	{
		$this->columnDefinition = array(
			new ColumnDefinition('col-sm-12', 'col-sm-2', 'col-sm-5'),
			new ColumnDefinition('col-sm-6',  'col-sm-5', 'col-sm-7'),
			new ColumnDefinition('col-sm-4',  'col-sm-4', 'col-sm-8'),
			new ColumnDefinition('col-sm-3',  'col-sm-4', 'col-sm-8'),
		);
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
		if ($this->layout != self::LAYOUT_TABLE) {
			return;
		}
		$this->tableEnsureHasBody();
		$this->currentTableRow = new TableElement(TableElement::TE_ROW);
	}

	public function tableRowEnd()
	{
		if ($this->layout != self::LAYOUT_TABLE) {
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
		if ($this->layout != self::LAYOUT_TABLE) {
			return;
		}
		if (! $this->currentTableBody) {
			$this->currentTableBody = new TableElement(TableElement::TE_BODY);
			$this->controls[] = $this->currentTableBody;
		}
	}

	private function tableEnsureHasRow()
	{
		if ($this->layout != self::LAYOUT_TABLE) {
			return;
		}
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
			case self::LAYOUT_FORM1COL:
			case self::LAYOUT_FORM2COL:
			case self::LAYOUT_FORM3COL:
				$columns = array();
				for ($i = 0; $i < $this->column; $i++) {
					$columns[$i] = array(
						'controls' => array(),
						'columnClass' => $this->columnDefinition[$this->column - 1]->columnClass,
						'labelClass' => $this->columnDefinition[$this->column - 1]->labelClass,
						'controlClass' => $this->columnDefinition[$this->column - 1]->controlClass,
					);
				}
				foreach ($this->controls as $control) {
					$columns[$control->column - 1]['controls'][] = $control;
				}
				$context = array(
					'columns' => $columns,
					'layout' => $this->layout
				);
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view_Ncol', $context);
				break;

			case self::LAYOUT_TABLE:
				$context = array(
					'controls' => $this->controls,
					'layout' => $this->layout
				);
				echo ThemeBootstrap::get()->renderBlock('controls.twig.html', 'view_table', $context);
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