<?php
namespace Builder;

/**
 * Form building class
 * @author Radek Brůha
 * @version 1.0
 */
class Form {
	private $columns;
	private $template;

	public function __construct() {
		$this->template = new \Nette\Templating\FileTemplate(__DIR__ . '\..\Templates\Form.latte');
		$this->template->registerFilter(new \Nette\Latte\Engine);
		$this->template->php = '<?php';
	}

	/**
	 * Build and return form
	 * @param string $columns Table columns
	 */
	public function build($columns) {
		$this->columns = $columns;
		$this->template->inputs = array();
		foreach ($this->columns as $column) {		
			if (is_object($column->key) && $column->key->name === 'primary') {
				$this->template->primary = $column->name;
				continue;
			}
			$this->template->inputs[] = $this->generateInputTypes($column) .
					$this->generatePlaceholders($column) .
					$this->generateHTML5Validations($column) .
					$this->generateRequiredValidations($column) .
					$this->generateRangeValidations($column) .
					$this->generateMaxLengthValidations($column) . ';';
		}
		return (string)$this->template;
	}
	
	/**
	 * @param \stdClass $column
	 * @return string Input placeholder
	 */
	private function generatePlaceholders(\stdClass $column) {
		$columnName = $column->comment ? $column->comment : $column->name;
		return "->setAttribute('placeholder', '$columnName')";
	}

	/**
	 * @param \stdClass $column Table column
	 * @return string Input type
	 */
	private function generateInputTypes(\stdClass $column) {
		if (in_array($column->type->name, array('boolean', 'enum', 'set'))) {
			return "->addSelect('$column->name', NULL, {$this->generateEnumSetValues($column)})";
		} else if (is_object($column->key) && $column->key->name === 'foreign') {
			$table = implode('', array_map(function($value) { return ucfirst($value); }, explode('_', $column->key->table)));
			if ($column->nullable) {
				return "->addText('$column->name', '$column->name')->setAttribute('readonly', 'readonly')->setAttribute('data-table', '$table:list')";
			} else { return "->addText('$column->name', '$column->name')->setAttribute('readonly', 'readonly')->setAttribute('data-table', '$table:list')"; }
		} else if (in_array($column->type->name, array('text', 'mediumtext', 'longtext', 'blob', 'mediumblob', 'longblob'))) {
			return "->addTextArea('$column->name')";
		} else {
			return "->addText('$column->name')";
		}
	}

	/**
	 * @param \stdClass $column Table column
	 * @return string Input values for BOOLean & ENUM & SET columns
	 */
	private function generateEnumSetValues(\stdClass $column) {
		if ($column->type->name === 'boolean') {
			if ($column->nullable) {
				return "array('NULL' => '', 0 => 'FALSE', 1 => 'TRUE')";
			} else {
				return "array(0 => 'FALSE', 1 => 'TRUE')";
			}
		} else {
			$values = 'array(';
			if ($column->nullable)
				$values .= "'NULL' => ''";
			foreach ($column->type->extra as $value)
				$values .= "'$value' => '$value', ";
			$values .= ')';
			return str_replace(', )', ')', $values);
		}
	}

	/**
	 * @param \stdClass $column Table column
	 * @return string Input HTML5 type
	 */
	private function generateHTML5Validations(\stdClass $column) {
		if (!(in_array($column->type->name, array('boolean', 'enum', 'set')) || (is_object($column->key) && $column->key->name === 'foreign'))) {
			if (in_array($column->type->name, array('tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'year'))) {
				return "->setType('number')";
			} else if ($column->type->name === 'date') {
				return "->setType('date')";
			} else if ($column->type->name === 'time') {
				return "->setType('time')";
			} else if (in_array($column->type->name, array('datetime', 'timestamp'))) {
				return "->setType('datetime-local')";
			}
		}
	}

	/**
	 * @param \stdClass $column Table column
	 * @return string Input required validation
	 */
	private function generateRequiredValidations(\stdClass $column) {
		if (!$column->nullable && ($column->extra !== 'on update CURRENT_TIMESTAMP' && (is_object($column->key) && ($column->key->name !== 'primary') || $column->extra !== 'auto_increment'))) {
			return "->addRule(Form::FILLED)";
		}
	}

	/**
	 * @param \stdClass $column Table column
	 * @return string Input range validation
	 */
	private function generateRangeValidations(\stdClass $column) {
		$range = NULL;
		if ($column->nullable || $column->extra === 'on update CURRENT_TIMESTAMP' || ((is_object($column->key) && $column->key->name === 'primary') && $column->extra === 'auto_increment')) {
			$range = '->addCondition(Form::FILLED)';
		}
		if (!(is_object($column->key) && $column->key->name === 'foreign')) {
			if (in_array($column->type->name, array('tinyint', 'smallint', 'mediumint', 'int', 'bigint'))) {
				$range .= "->addRule(Form::INTEGER)";
				switch ($column->type->name) {
					case 'tinyint':
						if ($column->type->extra) {
							$range .= "->addRule(Form::RANGE, NULL, array(0, 255))";
						} else {
							$range .= "->addRule(Form::RANGE, NULL, array(-128, 127))";
						}
						break;
					case 'smallint':
						if ($column->type->extra) {
							$range .= "->addRule(Form::RANGE, NULL, array(0, 65535))";
						} else {
							$range .= "->addRule(Form::RANGE, NULL, array(-32768, 32767))";
						}
						break;
					case 'mediumint':
						if ($column->type->extra) {
							$range .= "->addRule(Form::RANGE, NULL, array(0, 16777215))";
						} else {
							$range .= "->addRule(Form::RANGE, NULL, array(-8388608, 8388607))";
						}
						break;
					case 'int':
						if ($column->type->extra) {
							$range .= "->addRule(Form::RANGE, NULL, array(0, 4294967295))";
						} else {
							$range .= "->addRule(Form::RANGE, NULL, array(-2147483648, 2147483647))";
						}
						break;
					case 'bigint':
						if ($column->type->extra) {
							$range .= "->addRule(Form::RANGE, NULL, array(0, 18446744073709551615))";
						} else {
							$range .= "->addRule(Form::RANGE, NULL, array(-9223372036854775808, 9223372036854775807))";
						}
						break;
					case 'year':
						if ((int) $column->type->length === 4) {
							$range .= "->addRule(Form::RANGE, NULL, array(1901, 2155))";
						} else {
							$range .= "->addRule(Form::RANGE, NULL, array(0, 99))";
						}
						break;
				}
			} else if (in_array($column->type->name, array('float', 'double', 'decimal'))) {
				$range .= "->addRule(Form::FLOAT)";
			}
		}
		return $range;
	}

	/**
	 * @param \stdClass $column Table column
	 * @return string Input max legth validation
	 */
	private function generateMaxLengthValidations(\stdClass $column) {
		switch ($column->type->name) {
			case 'char': return "->addRule(Form::MAX_LENGTH, NULL, {$column->type->length})";
			case 'varchar': return "->addRule(Form::MAX_LENGTH, NULL, {$column->type->length})";
			case 'tinytext': return "->addRule(Form::MAX_LENGTH, NULL, 255)";
			case 'text': return "->addRule(Form::MAX_LENGTH, NULL, 65535)";
			case 'mediumtext': return "->addRule(Form::MAX_LENGTH, NULL, 16777215)";
			case 'longtext': return "->addRule(Form::MAX_LENGTH, NULL, 4294967295)";
			case 'binary': return "->addRule(Form::MAX_LENGTH, NULL, {$column->type->length})";
			case 'varbinary': return "->addRule(Form::MAX_LENGTH, NULL, {$column->type->length})";
			case 'tinyblob': return "->addRule(Form::MAX_LENGTH, NULL, 255)";
			case 'blob': return "->addRule(Form::MAX_LENGTH, NULL, 65535)";
			case 'mediumblob': return "->addRule(Form::MAX_LENGTH, NULL, 16777215)";
			case 'longblob': return "->addRule(Form::MAX_LENGTH, NULL, 4294967295)";
		}
	}
}