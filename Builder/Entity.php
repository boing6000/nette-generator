<?php namespace Builder;
/**
 * Doctrine2 entity building class
 * @author Radek Brůha
 * @version 1.1
 */
class Entity extends Base {
	/**
	 * Build and save Doctrine2 entities from database
	 * @param \stdClass $settings
	 */
	public function build(\stdClass $settings) {
		$database = ['dbname' => $settings->database->database, 'user' => $settings->database->username, 'password' => $settings->database->password, 'host' => $settings->database->hostname, 'driver' => 'pdo_mysql'];
		$folder = $settings->moduleName ? __DIR__ . "\\$this->projectPath\\{$settings->moduleName}Module\\models\\Entities" : __DIR__ . "\\$this->projectPath\\models\\Entities";
		$metadata = [];

		$config = new \Doctrine\ORM\Configuration;
		$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(__DIR__ . '\Utils\Entity'));
		$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
		$config->setProxyDir(__DIR__ . '\Utils\Proxy');
		$config->setProxyNamespace('Proxy');

		$entityGenerator = new \Doctrine\ORM\Tools\EntityGenerator();
		$entityGenerator->setClassToExtend('\Nette\Object');
		$entityGenerator->setAnnotationPrefix('ORM\\');
		$entityGenerator->setUpdateEntityIfExists(TRUE);
		$entityGenerator->setRegenerateEntityIfExists(TRUE);
		$entityGenerator->setGenerateStubMethods(TRUE);
		$entityGenerator->setGenerateAnnotations(TRUE);

		$entityManager = \Doctrine\ORM\EntityManager::create($database, $config);
		$entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('set', 'string');
		$entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
		$driver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver($entityManager->getConnection()->getSchemaManager());
		$entityManager->getConfiguration()->setMetadataDriverImpl($driver);
		$metadataFactory = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
		$metadataFactory->setEntityManager($entityManager);
		foreach ($driver->getAllClassNames() as $class) $metadata[] = $metadataFactory->getMetadataFor($class);
		$entityGenerator->generate($metadata, $folder);

		foreach (\Utils\File::getDirectoryFiles($folder) as $file) {
			$content = str_replace('<?php', '<?php namespace Kdyby\Doctrine;', \Utils\File::read("$folder/$file"));
			$lastVariablePosition = strrpos($content, 'private ');
			$i = 0;
			$newVariables = [];
			while ($i < $lastVariablePosition) {
				$start = strpos($content, 'private ', $i) + 9;
				$end = strpos($content, ';', $start);
				$name = substr($content, $start, $end - $start);
				$trueName = substr($name, 0, strpos($name, ' '));
				$newVariables[] = empty($trueName) ? $name : $trueName;
				$i = $end;
			}
			$oldVariables = $newVariables;
			foreach ($oldVariables as $key => $value) {
				$newVariable = implode('_', array_map('lcfirst', preg_split('/(?=[A-Z])/', $value)));
				$newVariables[$key] = $newVariable;
				$oldGetters[$key] = 'get' . ucfirst($value);
				$newGetters[$key] = 'get' . ucfirst($newVariable);
				$oldSetters[$key] = 'set' . ucfirst($value);
				$newSetters[$key] = 'set' . ucfirst($newVariable);
				$content = str_replace($oldSetters[$key] . '(\\', $oldSetters[$key] . '(\Kdyby\Doctrine\\', $content);
			}
			\Utils\File::write("$folder/$file", str_replace($oldSetters, $newSetters, str_replace($oldGetters, $newGetters, str_replace($oldVariables, $newVariables, $content))));
		}
	}
}