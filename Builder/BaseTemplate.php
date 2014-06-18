<?php namespace Builder;
/**
 * Base template building class
 * @author Radek Brůha
 * @version 1.1
 */
class BaseTemplate extends Base {
	/**
	 * Build and save base template
	 * @param array of \Utils\Object\Table $tables
	 * @throws \FileException
	 */
	public function build(array $tables, \stdClass $settings) {
		$this->sourcePath = "\\..\\Templates\\$settings->templateName\\Template\\BaseTemplate.latte";
		$this->destinationPath = __DIR__ . "\\$this->projectPath\\templates\@layout.latte";
		if (!is_dir(dirname($this->destinationPath))) if (!mkdir(dirname($this->destinationPath), 0777, TRUE)) throw new \FileException("Cannot create path $this->destinationPath.");
		$this->params['tables'] = $tables;
		$this->saveTemplate();
	}
}