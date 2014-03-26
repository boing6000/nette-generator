<?php
namespace Builder;

/**
 * Config building class
 * @author Radek Brůha
 * @version 1.0
 */
class Config {
	private $template;

	/** @param array $tables Database tables */
	public function __construct() {
		$this->template = \Utils\File::read(__DIR__ . '\..\..\..\..\app\config\config.neon');
	}

	/** @return string Config */
	public function build($moduleName, $tables) {
		$services = 'router: @App\Routers\RouterFactory::createRouter' . PHP_EOL;
		foreach ($tables as $table) {
			$service = $moduleName ?
				"	- \App\\{$moduleName}Module\Models\\" . implode('', array_map(function($value) { return ucfirst($value); }, explode('_', $table->name))) . "Repository('$table->name', %application.itemsPerPage%)" :
				"	- \App\Models\\" . implode('', array_map(function($value) { return ucfirst($value); }, explode('_', $table->name))) . "Repository('$table->name', %application.itemsPerPage%)";	
				if (strpos($this->template, $service) === FALSE)
				$services .= $service . PHP_EOL;
		}
		\Utils\File::write(__DIR__ . '\..\..\..\..\app\config\config.neon', str_replace('router: @App\Routers\RouterFactory::createRouter', $services, $this->template));
	}
}