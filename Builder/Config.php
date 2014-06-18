<?php namespace Builder;
/**
 * Nette Framework 2.2.X config building class
 * @author Radek Brůha
 * @version 1.1
 */
class Config extends Base {
	/**
	 * Build and save Nette Framework 2.2.X config
	 * @param array of \Utils\Object\Table $tables
	 * @param \stdClass $settings
	 */
	public function build(array $tables, \stdClass $settings) {
		$config = (new \Nette\Neon\Decoder())->decode(\Utils\File::read(__DIR__ . "\\$this->projectPath\\config\config.neon"));
		$services = $config['services'];
		foreach ($tables as $table) foreach ($services as $serviceKey => $serviceValue) if (is_object($serviceValue)) if (strpos($serviceValue->value, $table->sanitizedName)) unset($services[$serviceKey]);
		foreach ($tables as $table) {
			if ($settings->what === 1) {
				$serviceName = $settings->moduleName ? "\App\\{$settings->moduleName}Module\Models\\{$table->sanitizedName}Repository" : "\App\Models\\{$table->sanitizedName}Repository";
				$services[$table->sanitizedName] = new \Nette\Neon\Entity($serviceName, [$table->name, '%application.itemsPerPage%']);
			} else {
				$serviceName = $settings->moduleName ? "\App\\{$settings->moduleName}Module\Models\\{$table->sanitizedName}Repository" : "\App\Models\\{$table->sanitizedName}Repository";
				$services[$table->sanitizedName] = new \Nette\Neon\Entity($serviceName, ['%application.itemsPerPage%']);
			}
		}
		$config['services'] = $services;
		\Utils\File::write(__DIR__ . "\\$this->projectPath\\config\config.neon", (new \Nette\Neon\Encoder)->encode($config, \Nette\Neon\Encoder::BLOCK));
	}
}