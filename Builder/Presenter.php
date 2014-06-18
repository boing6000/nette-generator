<?php namespace Builder;
/**
 * Presenter building class
 * @author Radek Brůha
 * @version 1.1
 */
class Presenter extends Base {
	/**
	 * Build and save presenter
	 * @param \Utils\Object\Table $table
	 * @param \stdClass $settings
	 * @param string $form
	 * @throws \FileException
	 */
	public function build(\Utils\Object\Table $table, \stdClass $settings, $form) {
		$this->sourcePath = "\\..\\Templates\\$settings->templateName\\Presenter\\Presenter.latte";
		$this->destinationPath = $settings->moduleName ? __DIR__ . "\\$this->projectPath\\{$settings->moduleName}Module\\presenters\\{$table->sanitizedName}Presenter.php" : __DIR__ . "\\$this->projectPath\\presenters\\{$table->sanitizedName}Presenter.php";
		if (!is_dir(dirname($this->destinationPath))) if (!mkdir(dirname($this->destinationPath), 0777, TRUE)) throw new \FileException("Cannot create path $this->destinationPath.");
		$this->params['presenterName'] = $table->sanitizedName;
		$this->params['moduleName'] = $settings->moduleName ? "\\{$settings->moduleName}Module" : NULL;
		$this->params['formString'] = $form;
		$this->saveTemplate();
	}
}