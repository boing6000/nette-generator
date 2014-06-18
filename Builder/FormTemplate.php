<?php namespace Builder;
/**
 * Form template building class
 * @author Radek Brůha
 * @version 1.1
 */
class FormTemplate extends Base {
	/**
	 * Build and save form template
	 * @param \Utils\Object\Table $table
	 * @param \stdClass $settings
	 * @throws \FileException
	 */
	public function build(\Utils\Object\Table $table, \stdClass $settings) {
		$this->sourcePath = "\\..\\Templates\\$settings->templateName\\Template\\FormTemplate.latte";
		$this->destinationPath = $settings->moduleName ? __DIR__ . "\\$this->projectPath\\{$settings->moduleName}Module\\templates\\{$table->sanitizedName}\\form.latte" : __DIR__ . "\\$this->projectPath\\templates\\{$table->sanitizedName}\\form.latte";
		if (!is_dir(dirname($this->destinationPath))) if (!mkdir(dirname($this->destinationPath), 0777, TRUE)) throw new \FileException("Cannot create path $this->destinationPath.");
		$this->saveTemplate();
	}
}