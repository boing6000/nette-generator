<?php
namespace Builder;

/**
 * Form template building class
 * @author Radek Brůha
 * @version 1.0
 */
class FormTemplate {
    private $template;
	private $path;
    
    /** @param string $path Path to Nette app folder */
	public function __construct($path = '\..\..\..\..\app') {
		$this->template = new \Nette\Templating\FileTemplate(__DIR__ . '\..\Templates\FormTemplate.latte');
		$this->template->registerFilter(new \Nette\Latte\Engine);
		$this->path = __DIR__ . $path;
	}

	/**
	 * Build and save form template
	 * @param string $templateName Template name
	 * @param string $moduleName Module name
	 */
	public function build($templateName, $moduleName) {
		$path = $moduleName ? "$this->path\\{$moduleName}Module\\templates\\{$templateName}\\form.latte" : "$this->path\\templates\\{$templateName}\\form.latte";
		if (!is_dir(dirname($path))) if (!mkdir(dirname($path), 0777, TRUE)) throw new \FileException("Cannot create path $path.");
		$this->template->save($path);
	}
}