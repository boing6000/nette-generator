<?php namespace Builder;
/**
 * Base building class
 * @author Radek Brůha
 * @version 1.1
 */
class Base {
	protected $projectPath = '\..\..\..\..\app';
	protected $sourcePath;
	protected $destinationPath;
	protected $params = ['phpTag' => '<?php'];

	/** @return string Rendered template */
	protected function buildTemplate() {
		$template = (new \Latte\Engine)->renderToString(__DIR__ . $this->sourcePath, $this->params);
		unset($this->params['inputs']);
		return $template;
	}

	protected function saveTemplate() {
		\Utils\File::write($this->destinationPath, str_replace('l:', 'n:', $this->buildTemplate()));
		$this->filePath = $this->projectPath;
		$this->params = ['phpTag' => '<?php'];
	}
}