<?php
namespace Builder;

/**
 * Model building class
 * @author Radek Brůha
 * @version 1.0
 */
class Model {
	private $template;
	private $path;

	/** @param string $path Path to Nette app folder */
	public function __construct($path = '\..\..\..\..\app') {
		$this->template = new \Nette\Templating\FileTemplate(__DIR__ . '\..\Templates\Model.latte');
		$this->template->registerFilter(new \Nette\Latte\Engine);
		$this->path = __DIR__ . $path;
		$this->template->php = '<?php';
	}

	/**
	 * Build and save model
	 * @param string $modelName Model name
	 * @param string $moduleName Module name
	 */
	public function build($modelName, $moduleName) {
		$path = $moduleName ? "$this->path\\{$moduleName}Module\models\\{$modelName}Repository.php" : "$this->path\model\\{$modelName}Repository.php";
		if (!is_dir(dirname($path))) if (!mkdir(dirname($path), 0777, TRUE)) throw new \FileException("Cannot create path $path.");
		$this->template->modelName = $modelName;
		$this->template->moduleName = $moduleName ? "\\{$moduleName}Module" : NULL;
		$this->template->save($path);
	}
}