<?php
require_once __DIR__ . '\Examiner\Database.php';
require_once __DIR__ . '\Examiner\Table.php';
require_once __DIR__ . '\Builder\Form.php';
require_once __DIR__ . '\Builder\Presenter.php';
require_once __DIR__ . '\Builder\BasePresenter.php';
require_once __DIR__ . '\Builder\Model.php';
require_once __DIR__ . '\Builder\BaseModel.php';
require_once __DIR__ . '\Builder\Template.php';
require_once __DIR__ . '\Builder\FormTemplate.php';
require_once __DIR__ . '\Builder\BaseTemplate.php';
require_once __DIR__ . '\Builder\Router.php';
require_once __DIR__ . '\Builder\Config.php';
require_once __DIR__ . '\Utils\Exceptions.php';
require_once __DIR__ . '\Utils\Config.php';
require_once __DIR__ . '\Utils\Neon.php';
require_once __DIR__ . '\Utils\Database.php';
require_once __DIR__ . '\Utils\File.php';
require_once __DIR__ . '\Utils\Doctrine\Common\ClassLoader.php';
require_once __DIR__ . '\Utils\tracy.phar';
require_once __DIR__ . '\Utils\nette.phar';

\Tracy\Debugger::enable();
\Tracy\Debugger::$maxDepth = 5;
\Tracy\Debugger::$maxLen = 10000;

echo 'Welcome to Nette CRUD generator.' . PHP_EOL . 'What do you want to use for CRUD building?' . PHP_EOL;
echo ' => Press 1 for MySQL InnoDB engine tables (with foreign keys support).' . PHP_EOL;
echo ' => Press 2 for MySQL MyISAM engine tables (without foreign keys support).' . PHP_EOL;
echo ' => Press 3 for Doctrine2 entities (with creating MySQL InnoDB engine tables).' . PHP_EOL;
while ($generateFrom = trim(fgets(STDIN))) if (in_array($generateFrom, array(1, 2, 3))) break;
if (empty($generateFrom)) $generateFrom = 1;

echo 'Do you want to build only few tables?' . PHP_EOL;
echo ' => Yes? Write their names and use , as separator.' . PHP_EOL;
echo ' => No? Press enter.' . PHP_EOL;
$tablesToGenerate = trim(fgets(STDIN));
$tablesToGenerate = empty($tablesToGenerate) ? NULL : explode(', ', $tablesToGenerate);

echo 'Do you want to build into module?' . PHP_EOL;
echo ' => Yes? Write his name.' . PHP_EOL;
echo ' => No? Press enter.' . PHP_EOL;
$module = trim(fgets(STDIN));
$module = empty($module) ? NULL : $module;

echo 'What models do you want to build?' . PHP_EOL;
echo ' => Press 1 for Nette\Database models.' . PHP_EOL;
#echo ' => Press 2 for Doctrine2 models.' . PHP_EOL;
while ($generateWhat = trim(fgets(STDIN))) if (in_array($generateWhat, array(1, 2))) break;
if (empty($generateWhat)) $generateWhat = 1;

$start = microtime(TRUE);
try {
	echo PHP_EOL . 'Processing initial configuration: ' . PHP_EOL . ' => Loading config.neon file: ';
	$config = \Utils\Config::load(__DIR__ . '\..\..\..\app\config\config.neon');
	echo 'DONE' . PHP_EOL . ' => Connecting to database: ';
	$database = new \Utils\Database($config['hostname'], $config['username'], $config['password'], $config['database']);
	if ($generateFrom === 3) {
		echo 'DONE' . PHP_EOL . ' => Creating MySQL InnoDB engine tables: ';
		$database->buildFromEntities();
	}
	echo 'DONE' . PHP_EOL . ' => Analysing database tables: ';
	$tables = (new Examiner\Database($database))->getTables();
	echo 'DONE' . PHP_EOL . PHP_EOL . 'Processing tables: ' . PHP_EOL;

	$netteAppFolder = __DIR__ . '\..\..\..\app';
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
		if ($tablesToGenerate) if (!in_array($table->name, $tablesToGenerate)) continue;
		$originalTable = $table;
		$table = implode('', array_map(function($value) { return ucfirst($value); }, explode('_', $table->name)));
		echo " => Table $originalTable->name: " . PHP_EOL . '     => Analysing columns: ';
		$columns = (new \Examiner\Table($database, $originalTable->name))->getColumns();
		echo 'DONE' . PHP_EOL . '     => Building presenter: ';
		$presenterBuilder->build($table, $module, $formBuilder->build($columns));
		echo 'DONE' . PHP_EOL . '     => Building model: ';
		$modelBuilder->build($table, $module);
		echo 'DONE' . PHP_EOL . '     => Building template: ';
		$templateBuilder->build($table, $module, $columns, $originalTable);
		$formTemplateBuilder->build($table, $module);
		echo 'DONE' . PHP_EOL; 
	}
	
	echo PHP_EOL . 'Procesing final configuration: ' . PHP_EOL . ' => Building common presenter: ';
	$basePresenterBuilder->build($module);
	echo 'DONE' . PHP_EOL . ' => Building common model: ';
	$baseModelBuilder->build($module);
	echo 'DONE' . PHP_EOL . ' => Building common template: ';
	$baseTemplateBuilder->build($tables);
	echo 'DONE' . PHP_EOL . ' => Building router: ';
	$routerBuilder->build($tables[0]->name, $module);
	echo 'DONE' . PHP_EOL . ' => Building config: ';
	$configBuilder->build($module, $tablesToGenerate ? $tablesToGenerate : $tables);
	\Utils\File::write("$netteAppFolder\config\config.local.neon", '# This config is not used :)');
	echo 'DONE' . PHP_EOL . ' => Building images: ';
	Utils\File::copy(__DIR__ . '\\Templates\images', $netteAppFolder . '\..\www\images');
	echo PHP_EOL . ' => Building Cascading Style Sheet: ';
	Utils\File::copy(__DIR__ . '\\Templates\css', $netteAppFolder . '\..\www\css');
	echo PHP_EOL . ' => Building Javascript: ';
	Utils\File::copy(__DIR__ . '\\Templates\js', $netteAppFolder . '\..\www\js');
	echo PHP_EOL . ' => Cleaning Nette Cache: ';
	Utils\File::cacheClean($netteAppFolder . '\..\temp\cache');
	echo 'DONE' . PHP_EOL;
	echo PHP_EOL . 'Application successfully built in ' . number_format(microtime(TRUE) - $start, 2, '.', ' ') . ' seconds.' . PHP_EOL;
} catch (\Exception $e) { echo 'ERROR' . PHP_EOL . $e->getMessage(); }