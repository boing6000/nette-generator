<?php namespace Utils\Object\Key;
/**
 * Store column foreign key
 * @author Radek Brůha
 * @version 1.1
 */
class Foreign {
	public $table;
	public $key;
	public $value;

	function __construct($table, $key, $value) {
		$this->table = $table;
		$this->key = $key;
		$this->value = $value;
	}
}