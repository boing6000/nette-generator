<?php namespace Examiner;
/**
 * Table examination
 * @author Radek Brůha
 * @version 1.1
 */
class Table {
	/** @var \Utils\Database */
	private $database;
	private $table;

	/**
	 * @param \Utils\Database $database Database connection
	 * @param string $table Table name
	 */
	public function __construct(\Utils\Database $database, $table) {
		$this->database = $database;
		$this->table = $table;
	}

	/** @return \stdClass List of table columns */
	public function getColumns() {
		$query = $this->database->query("SHOW FULL COLUMNS FROM $this->table;");
		while ($row = $query->fetch_object()) $columns[] = new \Utils\Object\Column($row->Field, $this->getColumnType($row->Type), $row->Null === 'NO' ? FALSE : TRUE, $this->getColumnKey($row), empty($row->Default) ? FALSE : $row->Default, empty($row->Extra) ? FALSE : $row->Extra, empty($row->Comment) ? FALSE : $row->Comment);
		return $columns;
	}

	/**
	 * Get column type
	 * @param string $columnType Column type
	 * @return \Utils\Object\Type Column type
	 */
	private function getColumnType($columnType) {
		$type = new \Utils\Object\Type;
		$type->extra = FALSE;
		$type->length = FALSE;

		if (($position = strpos($columnType, '(')) !== FALSE) {
			$type->name = substr($columnType, 0, $position);
			$type->length = (int)\Nette\Utils\Strings::match($columnType, '~\d+~')[0];
			if (strpos($columnType, 'unsigned') !== FALSE) $type->extra[] = 'unsigned';
			if (strpos($columnType, 'zerofill') !== FALSE) $type->extra[] = 'zerofill';
		} else $type->name = $columnType;

		if ($type->name === 'tinyint' && $type->length === 1) {
			$type->name = 'boolean';
			$type->length = 1;
		}
		
		if ($type->name === 'enum' || $type->name === 'set') {
			$type->extra = explode(',', str_replace(['enum(', 'set(', "'", ')'], '', $columnType));
			$type->length = count($type->extra);
		}
		return $type;
	}

	/**
	 * Get possible column key
	 * @param \stdClass $column Column
	 * @return \stdClass|FALSE Column key
	 */
	private function getColumnKey(\stdClass $column) {
		$foreign = $this->getForeign($column->Field);
		switch ($column->Key) {
			case 'PRI': return new \Utils\Object\Key\Primary;
			case 'UNI': return $foreign ? $foreign : new \Utils\Object\Key\Unique;
			case 'MUL': return $foreign ? $foreign : new \Utils\Object\Key\Index;
			default: return FALSE;
		}
	}

	/**
	 * Get possible column foreign key
	 * @param string $column Column name
	 * @return \stdClass|FALSE Foreign key
	 */
	private function getForeign($column) {
		$query = $this->database->query("SELECT REFERENCED_TABLE_NAME 'table', REFERENCED_COLUMN_NAME 'key' FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = '$this->table' AND COLUMN_NAME = '$column' AND REFERENCED_TABLE_NAME IS NOT NULL;");
		if ($row = $query->fetch_object()) return new \Utils\Object\Key\Foreign($row->table, $row->key, $this->getForeignValue($row->table));
		if (($position = strrpos($column, '_')) !== FALSE && (int)$this->database->query("SELECT COUNT(*) count FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$this->database->database}' AND TABLE_NAME = '" . substr($column, 0, $position) . "';")->fetch_object()->count === 1) return new \Utils\Object\Key\Foreign(($table = substr($column, 0, $position)), substr($column, $position + 1), $this->getForeignValue($table));
		return FALSE;
	}

	/**
	 * Get the best possible referenced table column for show instead of ID
	 * @param string $table Table name
	 * @return string Column name
	 */
	private function getForeignValue($table) {
		$query = $this->database->query("SHOW COLUMNS FROM $table;");
		while ($row = $query->fetch_object()) $columns[] = new \Utils\Object\Column($row->Field, $this->getColumnType($row->Type));
		foreach ($columns as $column) if (in_array($column->name, array('name', 'title'))) return $column->name;
		foreach ($columns as $column) if (in_array($column->type->name, array('varchar', 'char'))) return $column->name;
		return count($columns) >= 2 ? $columns[1]->name : $columns[0]->name;
	}
}