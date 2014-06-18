<?php namespace Builder;
/**
 * Model building class
 * @author Radek Brůha
 * @version 1.1
 */
class Model extends Base {
	/**
	 * Build and save model
	 * @param \Utils\Object\Table $table
	 * @param \stdClass $settings
	 * @throws \FileException
	 */
	public function build(\Utils\Object\Table $table, \stdClass $settings) {
		$this->sourcePath = $settings->what === 1 ? "\\..\\Templates\\$settings->templateName\\Model\\NetteDatabaseTable.latte" : "\\..\\Templates\\$settings->templateName\\Model\\Doctrine2.latte";
		$this->destinationPath = $settings->moduleName ? __DIR__ . "$this->projectPath\\{$settings->moduleName}Module\\models\\{$table->sanitizedName}Repository.php" : __DIR__ . "$this->projectPath\\models\\{$table->sanitizedName}Repository.php";
		if (!is_dir(dirname($this->destinationPath))) if (!mkdir(dirname($this->destinationPath), 0777, TRUE)) throw new \FileException("Cannot create path $this->destinationPath.");
		$this->params['modelName'] = $table->sanitizedName;
		$this->params['moduleName'] = $settings->moduleName ? "\\{$settings->moduleName}Module" : NULL;
		$this->saveTemplate();
	}
}