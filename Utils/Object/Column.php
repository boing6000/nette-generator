<?php namespace Utils\Object;
/**
 * Store table column structure
 * @author Radek Brůha
 * @version 1.1
 */
class Column {
	public $name;
	public $type;
	public $nullable;
	public $key;
	public $default;
	public $extra;
	public $comment;
	public $foreignName;
	
	public function __construct($name = FALSE, $type = FALSE, $nullable = FALSE, $key = FALSE, $default = FALSE, $extra = FALSE, $comment = FALSE, $foreignName = FALSE) {
		$this->name = $name;
		$this->type = $type;
		$this->nullable = $nullable;
		$this->key = $key;
		$this->default = $default;
		$this->extra = $extra;
		$this->comment = $comment;
		$this->foreignName = $foreignName;
	}
}