<?php
namespace Builder;

/**
 * Router building class
 * @author Radek Brůha
 * @version 1.0
 */
class Router {
	private $template;
	private $path;

	/** @param string $path Path to Nette app folder */
	public function __construct($path = '\..\..\..\..\app') {
		$this->template = new \Nette\Templating\FileTemplate(__DIR__ . '\..\Templates\Router.latte');
		$this->template->registerFilter(new \Nette\Latte\Engine);
		$this->template->php = '<?php';
		$this->path = __DIR__ . $path;
	}
	
	/**
	 * Build and save router
	 * @param string $routerName Router name
	 * @param string $moduleName Module name
	 */
	public function build($routerName, $moduleName) {
		$path = "$this->path\\router\RouterFactory.php";
		$this->template->routerName = ucfirst($routerName);
		$this->template->moduleName = $moduleName ? $moduleName : NULL;
		$this->template->save($path);
	}
}