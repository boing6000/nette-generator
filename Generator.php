<?php namespace Bruha\Generator;
/**
 * Main startup generator class
 * @author Radeb Brůha
 * @version 1.1
 */
class Application {
	private static $netteDirectory;
	private static $startTime;
	private static $settings = [
		'from' => 1,
		'tables' => FALSE,
		'moduleName' => FALSE,
		'what' => 1,
		'templateName' => 'default'
	];
	
	public function run() {
		static::$netteDirectory = __DIR__ . '\..\..\..\app';
		static::$settings = (object)static::$settings;
		
		$netteContainer = $this->getNetteFrameworkContainer();
		\Tracy\Debugger::$maxDepth = 5;
		\Tracy\Debugger::$maxLen = 10000;
		static::$settings->database = $this->getDatabaseConnectionParameters($netteContainer);
		static::$settings->entityManager = $netteContainer->getByType('Kdyby\\Doctrine\\Entitymanager');

		echo 'Welcome to Nette Framework 2.2.X CRUD generator.' . PHP_EOL . 'What do you want to use for CRUD building?' . PHP_EOL;
		echo ' => Press 1 for MySQL InnoDB engine tables (with foreign keys support).' . PHP_EOL;
		echo ' => Press 2 for MySQL MyISAM engine tables (without foreign keys support).' . PHP_EOL;
		echo ' => Press 3 for Doctrine2 entities (with creating MySQL InnoDB engine tables).' . PHP_EOL;
		while (static::$settings->from = (int)trim(fgets(STDIN))) if (in_array(static::$settings->from, [1, 2, 3], TRUE)) break;
		if (empty(static::$settings->from)) static::$settings->from = 1;
		
		echo 'Do you want to build only few tables?' . PHP_EOL;
		echo ' => Yes? Write their names and use , as separator.' . PHP_EOL;
		echo ' => No? Press enter.' . PHP_EOL;
		static::$settings->tables = trim(fgets(STDIN));
		static::$settings->tables = empty(static::$settings->tables) ? FALSE : array_map('trim', explode(',', static::$settings->tables));
		
		echo 'What models do you want to build?' . PHP_EOL;
		echo ' => Press 1 for \Nette\Database models.' . PHP_EOL;
		echo ' => Press 2 for Doctrine2 models.' . PHP_EOL;
		while (static::$settings->what = (int)trim(fgets(STDIN))) if (in_array(static::$settings->what, [1, 2], TRUE)) break;
		if (empty(static::$settings->what)) static::$settings->what = 1;

		echo 'Do you want to build into module?' . PHP_EOL;
		echo ' => Yes? Write his name.' . PHP_EOL;
		echo ' => No? Press enter.' . PHP_EOL;
		static::$settings->moduleName = trim(fgets(STDIN));
		static::$settings->moduleName = empty(static::$settings->moduleName) ? FALSE : static::$settings->moduleName;

		echo 'What templates do you want to use?' . PHP_EOL;
		echo ' => Write name or press enter for default.' . PHP_EOL;
		static::$settings->templateName = trim(fgets(STDIN));
		static::$settings->templateName = empty(static::$settings->templateName) ? 'default' : static::$settings->templateName;
		if (!is_dir(__DIR__ . '\\Templates\\' . static::$settings->templateName)) die("Cannot find '" . static::$settings->templateName . "' templates.");
	
		static::$startTime = microtime(TRUE);
		$this->generate();
	}

	private function generate() { 
		try {
			echo PHP_EOL . 'Processing initial configuration: ' . PHP_EOL . ' => Connecting to database: ';
			$database = new \Utils\Database(static::$settings->database->hostname, static::$settings->database->username, static::$settings->database->password, static::$settings->database->database);
			
			// Not working now :(
			if (static::$settings->from === 3) {
				echo 'DONE' . PHP_EOL . ' => Creating MySQL InnoDB engine tables: ';
				$database->buildFromEntities(static::$settings);
			}
			
			if (static::$settings->what === 2 && static::$settings->from !== 3) {
				$entityBuilder = new \Builder\Entity;
				$entityBuilder->build(static::$settings);
			} else
					
			echo 'DONE' . PHP_EOL . ' => Analysing database tables: ';
			$tables = (new \Examiner\Database($database))->getTables();
			echo 'DONE' . PHP_EOL . PHP_EOL . 'Processing tables: ' . PHP_EOL;
			
			$formBuilder = new \Builder\Form;
			$presenterBuilder = new \Builder\Presenter;
			$modelBuilder = new \Builder\Model;
			$templateBuilder = new \Builder\Template;
			$formTemplateBuilder = new \Builder\FormTemplate;
			$basePresenterBuilder = new \Builder\BasePresenter;
			$baseModelBuilder = new \Builder\BaseModel;
			$baseTemplateBuilder = new \Builder\BaseTemplate;
			$routerBuilder = new \Builder\Router;
			$configBuilder = new \Builder\Config;

			foreach ($tables as $table) {
				if (static::$settings->tables) if (!in_array($table->name, static::$settings->tables)) continue;
				$generatedTables[] = $table;
				echo " => Table $table->name: " . PHP_EOL . '     => Building presenter: ';
				$presenterBuilder->build($table, static::$settings, $formBuilder->build($table->columns, static::$settings));
				echo 'DONE ' . PHP_EOL . '     => Building model: ';
				$modelBuilder->build($table, static::$settings);
				echo 'DONE' . PHP_EOL . '     => Building template: ';
				$templateBuilder->build($table, static::$settings);
				$formTemplateBuilder->build($table, static::$settings);
				echo 'DONE' . PHP_EOL;
			}
			echo PHP_EOL . 'Procesing final configuration: ' . PHP_EOL . ' => Building common presenter: ';
			$basePresenterBuilder->build(static::$settings);
			echo 'DONE' . PHP_EOL . ' => Building common model: ';
			$baseModelBuilder->build(static::$settings);
			echo 'DONE' . PHP_EOL . ' => Building common template: ';
			$baseTemplateBuilder->build($generatedTables, static::$settings);
			echo 'DONE' . PHP_EOL . ' => Building router: ';
			$routerBuilder->build($tables, static::$settings);
			echo 'DONE' . PHP_EOL . ' => Building config: ';
			$configBuilder->build($tables, static::$settings);
			\Utils\File::write(static::$netteDirectory. '\\config\\config.local.neon', '# This config is not used :)');
			echo 'DONE' . PHP_EOL . ' => Building images: ';
			\Utils\File::copy(__DIR__ . '\\Templates\\' . static::$settings->templateName . '\\images', static::$netteDirectory . '\\..\\www\\images');
			echo PHP_EOL . ' => Building Cascading Style Sheet: ';
			\Utils\File::copy(__DIR__ . '\\Templates\\' . static::$settings->templateName . '\\css', static::$netteDirectory . '\\..\\www\\css');
			echo PHP_EOL . ' => Building Javascript: ';
			\Utils\File::copy(__DIR__ . '\\Templates\\' . static::$settings->templateName . '\\js', static::$netteDirectory . '\\..\\www\\js');
			echo PHP_EOL . ' => Cleaning Nette Cache: ';
			\Utils\File::cacheClean(static::$netteDirectory . '\..\temp\cache');
			echo 'DONE' . PHP_EOL;
			echo PHP_EOL . 'Application successfully built in ' . number_format(microtime(TRUE) - static::$startTime, 2, '.', ' ') . ' seconds.' . PHP_EOL;
		} catch (\Exception $e) {
			echo 'ERROR' . PHP_EOL . $e->getMessage();
		}
	}

	/**
	 * Load Nette Framework 2.2.X, Doctrine2, project classes and return its container 
	 * @return \SystemContainer Nette Framework 2.2.X container
	 */
	private function getNetteFrameworkContainer() {
		$netteContainer = require_once __DIR__ . '\\..\\..\\..\\app\\bootstrap.php';
		$loader = new \Nette\Loaders\RobotLoader;
		$loader->setCacheStorage(new \Nette\Caching\Storages\DevNullStorage());
		$loader->addDirectory(__DIR__);
		$loader->addDirectory(__DIR__ . '\\..\\..\\doctrine\\annotations\\lib');
		$loader->addDirectory(__DIR__ . '\\..\\..\\doctrine\\cache\\lib');
		$loader->addDirectory(__DIR__ . '\\..\\..\\doctrine\\common\\lib');
		$loader->addDirectory(__DIR__ . '\\..\\..\\doctrine\\dbal\\lib');
		$loader->addDirectory(__DIR__ . '\\..\\..\\doctrine\\inflector\\lib');
		$loader->addDirectory(__DIR__ . '\\..\\..\\doctrine\\lexer\\lib');
		$loader->addDirectory(__DIR__ . '\\..\\..\\doctrine\\orm\\lib');
		$loader->register();
		return $netteContainer;
	}

	/**
	 * Return Nette Framework 2.2.X database connection parameters
	 * @param \SystemContainer $netteContainer Nette Framewok 2.2.X container
	 * @return \stdClass Database connection parameters
	 */
	private function getDatabaseConnectionParameters($netteContainer) {
		$databaseConnection = $netteContainer->getByType('\\Nette\\Database\\Connection');
		$databaseReflectionProperty = (new \ReflectionClass($databaseConnection))->getProperty('params');
		$databaseReflectionProperty->setAccessible(TRUE);
		$databaseConncetionParameters = $databaseReflectionProperty->getValue($databaseConnection);

		$hostnameStart = strpos($databaseConncetionParameters[0], ':host=') + 6;
		$hostnameEnd = strpos($databaseConncetionParameters[0], ';', $hostnameStart);
		$hostname = substr($databaseConncetionParameters[0], $hostnameStart, $hostnameEnd - $hostnameStart);
		$databaseStart = strpos($databaseConncetionParameters[0], ';dbname=') + 8;
		$database = substr($databaseConncetionParameters[0], $databaseStart);

		return (object)array('hostname' => $hostname, 'username' => $databaseConncetionParameters[1], 'password' => $databaseConncetionParameters[2], 'database' => $database);
	}
}