<?php
namespace Examiner;

/**
 * Database information class
 * @author Radek Brůha
 * @version 1.0
 */
class Database {
	/** @var \Utils\Database */
	private $database;
	
	/** @param \Utils\Database $database Database connection */
	public function __construct(\Utils\Database $database) {
		$this->database = $database;
	}

	/** @return array Database tables */
	public function getTables() {
		$query = $this->database->query('SHOW TABLE STATUS');
		while ($row = $query->fetch_object()) {
			$table = new \stdClass();
			$table->name = $row->Name;
			$table->comment = empty($row->Comment) ? NULL : $row->Comment;
			$tables[] = $table;
		}
		return $tables;
	}
}