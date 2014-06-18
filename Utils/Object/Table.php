<?php namespace Utils\Object;
/**
 * Store database table structure
 * @author Radek Brůha
 * @version 1.1
 */
class Table {
	public $name;
	public $sanitizedName;
	public $comment;
	/** @var \Utils\Object\Column */
	public $columns;
	
	public function __construct($name = FALSE, $comment = FALSE, array $colums = []) {
		$this->name = $name;
		$this->sanitizedName = implode('', array_map(function($value) { return ucfirst($value); }, explode('_', $name)));
		$this->comment = $comment;
		$this->columns = $colums;
	}
}