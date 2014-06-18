<?php namespace Builder;
/**
 * Base model building class
 * @author Radek Brůha
 * @version 1.1
 */
class BaseModel extends Base {
	/**
	 * Build and save base model
	 * @param \stdClass $settings
	 * @throws \FileException
	 */
	public function build(\stdClass $settings) {
		$this->sourcePath = $settings->what === 1 ? "\\..\\Templates\\$settings->templateName\\Model\\BaseNetteDatabaseTable.latte" : "\\..\\Templates\\$settings->templateName\\Model\\BaseDoctrine2.latte";
		$this->destinationPath = $settings->moduleName ? __DIR__ . "$this->projectPath\\{$settings->moduleName}Module\\models\\BaseRepository.php" : __DIR__ . "$this->projectPath\\models\\BaseRepository.php";
		if (!is_dir(dirname($this->destinationPath))) if (!mkdir(dirname($this->destinationPath), 0777, TRUE)) throw new \FileException("Cannot create path $this->destinationPath.");
		$this->params['moduleName'] = $settings->moduleName ? "\\{$settings->moduleName}Module" : NULL;
		$this->saveTemplate();
	}
}