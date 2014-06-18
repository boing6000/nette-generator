<?php namespace Builder;
/**
 * Base presenter building class
 * @author Radek Brůha
 * @version 1.1
 */
class BasePresenter extends Base {
	/**
	 * Build and save base presenter
	 * @param \stdClass $settings
	 * @throws \FileException
	 */
	public function build(\stdClass $settings) {
		$this->sourcePath = "\\..\\Templates\\$settings->templateName\\Presenter\\BasePresenter.latte";
		$this->destinationPath = $settings->moduleName ? __DIR__ . "\\$this->projectPath\\{$settings->moduleName}Module\\presenters\\BasePresenter.php" : __DIR__ . "\\$this->projectPath\\presenters\\BasePresenter.php";
		if (!is_dir(dirname($this->destinationPath))) if (!mkdir(dirname($this->destinationPath), 0777, TRUE)) throw new \FileException("Cannot create path $this->destinationPath.");
		$this->params['moduleName'] = $settings->moduleName ? "\\{$settings->moduleName}Module" : NULL;
		$this->saveTemplate();
	}
}