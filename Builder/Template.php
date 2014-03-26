<?php
namespace Builder;

/**
 * Template building class
 * @author Radek Brůha
 * @version 1.0
 */
class Template {
    private $template;
	private $path;
    
    /** @param string $path Path to Nette app folder */
	public function __construct($path = '\..\..\..\..\app') {
		$this->template = new \Nette\Templating\FileTemplate(__DIR__ . '\..\Templates\Template.latte');
		$this->template->registerFilter(new \Nette\Latte\Engine);
		$this->path = __DIR__ . $path;
	}

	/**
	 * Build and save template
	 * @param string $templateName Template name
	 * @param string $moduleName Module name
	 * @param string $columns Table columns
	 * @param array $table Table name
	 */
	public function build($templateName, $moduleName, $columns, $table) {
		$path = $moduleName ? "$this->path\\{$moduleName}Module\\templates\\{$templateName}\list.latte" : "$this->path\\templates\\{$templateName}\list.latte";
		if (!is_dir(dirname($path))) if (!mkdir(dirname($path), 0777, TRUE)) throw new \FileException("Cannot create path $path.");
		$this->template->name = $table->comment ? $table->comment : $table->name;
		$this->template->columns = array();
		$this->template->count = count($columns) + 1;
		
		foreach ($columns as $column) {
			$newColumn = new \stdClass();
			if (is_object($column->key) && $column->key->name === 'primary') $this->template->primary = $column->name;
			if (is_object($column->key) && $column->key->name === 'foreign') {
				$newColumn->title = $column->comment ? $column->comment : $column->name;
				$newColumn->originalName = $column->name;
				//Possible bug if column is NULL: {if $i->user->fetch()}{$i->user->name}{else}{$i->user}{/if}
				$newColumn->name = $column->key->table . '->' . $column->key->column_value;
            } else {
				$newColumn->title = $column->comment ? $column->comment : $column->name;
				$newColumn->name = $newColumn->originalName = $column->name;
			}
			$this->template->columns[] = $newColumn;
		}
		$this->template->save($path);
	}
}