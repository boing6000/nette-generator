<?php
namespace Builder;

/**
 * BaseModel building class
 * @author Radek Brůha
 * @version 1.0
 */
class BaseModel {
	private $template;
	private $path;

	/** @param string $path Path to Nette app folder */
	public function __construct($path = '\..\..\..\..\app') {
		$this->template = new \Nette\Templating\FileTemplate(__DIR__ . '\..\Templates\BaseModel.latte');
		$this->template->registerFilter(new \Nette\Latte\Engine);
		$this->path = __DIR__ . $path;
		$this->template->php = '<?php';
	}

	/**
	 * Build and save base model
	 * @param string $moduleName Module name
	 */
	public function build($moduleName) {
		$path = $moduleName ? "$this->path\\{$moduleName}Module\models\BaseRepository.php" : "$this->path\model\BaseRepository.php";
		if (!is_dir(dirname($path))) if (!mkdir(dirname($path), 0777, TRUE)) throw new \FileException("Cannot create path $path.");
		$this->template->moduleName = $moduleName ? "\\{$moduleName}Module" : NULL;
		$this->template->save($path);
	}
}