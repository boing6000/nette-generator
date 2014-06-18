<?php namespace Builder;
/**
 * Template building class
 * @author Radek Brůha
 * @version 1.1
 */
class Template extends Base {
	/**
	 * Build and save template
	 * @param \Utils\Object\Table $table
	 * @param \stdClass $settings
	 * @throws \FileException
	 */
	public function build(\Utils\Object\Table $table, \stdClass $settings) {
		$this->sourcePath = "\\..\\Templates\\$settings->templateName\\Template\\Template.latte";
		$this->destinationPath = $settings->moduleName ? __DIR__ . "\\$this->projectPath\\{$settings->moduleName}Module\\templates\\{$table->sanitizedName}\\list.latte" : __DIR__ . "\\$this->projectPath\\templates\\{$table->sanitizedName}\\list.latte";
		if (!is_dir(dirname($this->destinationPath))) if (!mkdir(dirname($this->destinationPath), 0777, TRUE)) throw new \FileException("Cannot create path $this->destinationPath.");
		foreach ($table->columns as $column) {
			if ($column->key instanceof \Utils\Object\Key\Primary) $this->params['primaryKey'] = $column->name;
			if ($column->key instanceof \Utils\Object\Key\Foreign) {
				//Possible bug if column is NULL: {if $i->user->fetch()}{$i->user->name}{else}{$i->user}{/if}
				$column->foreignName = $column->key->table . '->' . $column->key->value;
			}
		}
		$this->params['table'] = $table;
		$this->saveTemplate();
	}
}