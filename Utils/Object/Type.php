<?php namespace Utils\Object;
/**
 * Store column type
 * @author Radek Brůha
 * @version 1.1
 */
class Type {
	public $name;
	public $length;
	public $extra;

	function __construct($name = FALSE, $length = FALSE, $extra = FALSE) {
		$this->name = $name;
		$this->length = $length;
		$this->extra = $extra;
	}
}