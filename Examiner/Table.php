<?php

namespace Examiner;
/**
 * Table information class
 * @author Radek Brůha
 * @version 1.0
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
		while ($row = $query->fetch_object()) {
			$column = new \stdClass();
			$column->name = $row->Field;
			$column->type = $this->getColumnType($row);
			$column->nullable = $row->Null === 'NO' ? FALSE : TRUE;
			$column->key = $this->getColumnIndex($row);
			$column->default = empty($row->Default) ? NULL : $row->Default;
			$column->extra = empty($row->Extra) ? NULL : $row->Extra;
			$column->comment = empty($row->Comment) ? NULL : $row->Comment;
			$columns[] = $column;
		}
		return $columns;
	}

	/**
	 * @param \stdClass $column Table column
	 * @return \stdClass Column type
	 */
	private function getColumnType(\stdClass $column) {
		$columnType = new \stdClass();
		if (($position = strpos($column->Type, '(')) !== FALSE) {
			$columnType->name = substr($column->Type, 0, $position);
			if (preg_match('/\d+/', $column->Type, $matches)) {
				$columnType->length = (int) $matches[0];
			} else { $columnType->length = NULL; }
			if (strpos($column->Type, 'unsigned') !== FALSE) {
				$columnType->extra = 'unsigned';
			} else { $columnType->extra = NULL; }
		} else {
			$columnType->name = $column->Type;
			$columnType->length = NULL;
			$columnType->extra = NULL;
		}
		// MySQL BOOLen column
		if ($columnType->name === 'tinyint' && $columnType->length === 1) {
			$columnType->name = 'boolean';
			$columnType->length = 1;
			$columnType->extra = NULL;
		}
		// MySQL ENUM & SET column
		if ($columnType->name === 'enum' || $columnType->name === 'set') {
			$columnType->extra = explode(',', str_replace(array('enum(', 'set(', "'", ')'), '', $column->Type));
			$columnType->length = count($columnType->extra);
		}
		return $columnType;
	}

	/**
	 * @param \stdClass $column Table column
	 * @return \stdClass|NULL Column index
	 */
	private function getColumnIndex(\stdClass $column) {
		$foreignKey = $this->getForeignKey($column->Field);
		$columnKey = new \stdClass();
		if ($column->Key === 'PRI') {
			$columnKey->name = 'primary';
		} else if ($column->Key === 'UNI') {
			if ($foreignKey) {
				$columnKey->name = 'foreign';
				$columnKey->table = $foreignKey->table;
				$columnKey->column_key = $foreignKey->column_key;
				$columnKey->column_value = $foreignKey->column_value;
			} else { $columnKey->name = 'unique'; }
		} else if ($column->Key === 'MUL') {
			if ($foreignKey) {
				$columnKey->name = 'foreign';
				$columnKey->table = $foreignKey->table;
				$columnKey->column_key = $foreignKey->column_key;
				$columnKey->column_value = $foreignKey->column_value;
			} else { $columnKey->name = 'index'; }
		} else { return NULL; }
		return $columnKey;
	}

	/**
	 * @param string $column Table column name
	 * @return \stdClass|NULL Foreign key information
	 */
	private function getForeignKey($column) {
		// InnoDB
		$query = $this->database->query("SELECT REFERENCED_TABLE_NAME 'table', REFERENCED_COLUMN_NAME column_key FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = '$this->table' AND COLUMN_NAME = '$column' AND REFERENCED_TABLE_NAME IS NOT NULL;");
		if ($row = $query->fetch_object()) {
			$row->column_value = $this->getForeignTableColumn($row->table);
			return $row;
		}
		// MyISAM
		if (($position = strrpos($column, '_')) !== FALSE) {
			if ((int) $this->database->query("SELECT COUNT(*) count FROM information_schema.TABLES WHERE TABLE_NAME = '" . substr($column, 0, $position) . "';")->fetch_object()->count >= 1) {
				$row = new \stdClass();
				$row->table = substr($column, 0, $position);
				$row->column_key = substr($column, $position + 1);
				$row->column_value = $this->getForeignTableColumn($row->table);
				return $row;
			}
		}
	}

	/**
	 * @param string $table Table name
	 * @return string Column name
	 */
	private function getForeignTableColumn($table) {
		$query = $this->database->query("SHOW COLUMNS FROM $table;");
		while ($row = $query->fetch_object()) {
			$column = new \stdClass();
			$column->name = $row->Field;
			$column->type = $this->getColumnType($row);
			$columns[] = $column;
		}
		foreach ($columns as $column) if (in_array($column->name, array('name', 'title'))) return $column->name;
		foreach ($columns as $column) if (in_array($column->type->name, array('varchar', 'char'))) return $column->name;
		return count($columns) >= 2 ? $columns[1]->name : $columns[0]->name;
	}
}