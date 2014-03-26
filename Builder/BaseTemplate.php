<?php
namespace Builder;

/**
 * BaseTemplate building class
 * @author Radek Brůha
 * @version 1.0
 */
class BaseTemplate {
    private $template;
	private $path;
    
    /** @param string $path Path to Nette app folder */
	public function __construct($path = '\..\..\..\..\app') {
		$this->template = new \Nette\Templating\FileTemplate(__DIR__ . '\..\Templates\BaseTemplate.latte');
		$this->template->registerFilter(new \Nette\Latte\Engine);
		$this->path = __DIR__ . $path;
	}

	/**
	 * Build and save base template
	 * @param string $tables Database tables
	 */
	public function build($tables) {
		$this->template->tables = array();
		foreach ($tables as $table) {
			$newTable = new \stdClass();
			$newTable->presenter = implode('', array_map(function($value) { return ucfirst($value); }, explode('_', $table->name)));
			$newTable->name = $table->comment ? $table->comment : $table->name;
			$this->template->tables[] = $newTable;
		}
		$this->template->save("$this->path\\templates\@layout.latte");
	}
}