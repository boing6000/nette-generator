<?php
namespace Builder;

/**
 * Presenter building class
 * @author Radek Brůha
 * @version 1.0
 */
class Presenter {
	private $template;
	private $path;
	
	/** @param string $path Path to Nette app folder */
	public function __construct($path = '\..\..\..\..\app') {
		$this->template = new \Nette\Templating\FileTemplate(__DIR__ . '\..\Templates\Presenter.latte');
		$this->template->registerFilter(new \Nette\Latte\Engine);
		$this->path = __DIR__ . $path;
		$this->template->php = '<?php';
	}
	
	/**
	 * Build and save presenter
	 * @param string $presenterName Presenter name
	 * @param string $moduleName Module name
	 * @param string $form Form
	 */
	public function build($presenterName, $moduleName, $form) {
		$path = $moduleName ? "$this->path\\{$moduleName}Module\presenters\\{$presenterName}Presenter.php" : "$this->path\presenters\\{$presenterName}Presenter.php";
		if (!is_dir(dirname($path))) if (!mkdir(dirname($path), 0777, TRUE)) throw new \FileException("Cannot create path $path.");
		$this->template->presenterName = $presenterName;
		$this->template->moduleName = $moduleName ? "\\{$moduleName}Module" : NULL;
		$this->template->form = $form;
		$this->template->save($path);
	}
}
