<?php namespace Examiner;
/**
 * Database examination
 * @author Radek Brůha
 * @version 1.1
 */
class Database {
	/** @var \Utils\Database */
	private $database;

	/** @param \Utils\Database $database */
	public function __construct(\Utils\Database $database) {
		$this->database = $database;
	}

	/** @return array of \Utils\Object\Table */
	public function getTables() {
		$query = $this->database->query('SHOW TABLE STATUS');
		$tables = [];
		while ($row = $query->fetch_object()) {
			$name = $row->Name;
			$comment = $row->Comment ? $row->Comment : FALSE;
			$columns = (new \Examiner\Table($this->database, $name))->getColumns();
			$tables[] = new \Utils\Object\Table($name, $comment, $columns);
		}
		return $tables;
	}
}