<?php
namespace Utils;

/**
 * Database wrapper for MySQLi connection with basic exceptions handling
 * @author Radek Brůha
 * @version 1.0
 */
class Database extends \MySQLi {
	public $hostname;
	public $username;
	public $password;
	public $database;

	/**
	 * Database connect
	 * @param string $hostname MySQL host
	 * @param string $username MySQL username
	 * @param string $password MySQL password
	 * @param string $database MySQL database
	 * @throws \DatabaseException
	 */
	public function __construct($hostname, $username, $password, $database) {
		parent::__construct($hostname, $username, $password, $database);
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
		if ($this->connect_error) throw new \DatabaseException("[$this->connect_errno] $this->connect_error");
	}

	/**
	 * Execute SQL query with exception handling
	 * @param string $query SQL query
	 * @return MySQLi_result
	 * @throws \DatabaseException
	 */
	public function query($query) {
		$result = parent::query($query);
		if ($this->error) throw new \DatabaseException("[$this->errno] $this->error");
		return $result;
	}

	/**
	 * Build MySQL database tables from Doctrine2 entities
	 * @throws \DatabaseException
	 */
	public function buildFromEntities($settings) {
		$database = ['dbname' => $this->database, 'user' => $this->username, 'password' => $this->password, 'host' => $this->hostname, 'driver' => 'pdo_mysql'];
		$metadata = [];

		$config = new \Doctrine\ORM\Configuration;
		$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(__DIR__ . '\..\..\..\..\app\models\Entities'));
		$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
		$config->setProxyDir(__DIR__ . '\Utils\Proxy');
		$config->setProxyNamespace('Proxy');


		$entityManager = \Doctrine\ORM\EntityManager::create($database, $config);
		$entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('set', 'string');
		$entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
		$metadataFactory = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
		$metadataFactory->setEntityManager($entityManager);
		foreach (\Utils\File::getDirectoryFiles(__DIR__ . '\..\..\..\..\app\models\Entities\\') as $class)
				$metadata[] = $entityManager->getClassMetadata(str_replace('.php', '', "$class"));
				//$metadata[] = $metadataFactory->getMetadataFor(str_replace('.php' , '', $class));
		

		$databaseBuilder = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
		try {
		$databaseBuilder->createSchema($metadata);
		} catch (\Doctrine\ORM\Tools\ToolsException $e) {
					//throw new \DatabaseException($e->getMessage());
					throw new \DatabaseException($e->getPrevious()->getPrevious()->getMessage(), $e->getPrevious()->getPrevious()->errorInfo[1], $e);
				}
		exit;
		
		
		
		
		
		
		$config = new \Doctrine\ORM\Configuration;
		$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([__DIR__ . '\..\..\..\..\app\models\Entities']));
		$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
		$config->setProxyDir(__DIR__ . '\Utils\Proxy');
		$config->setProxyNamespace('Proxy');
	
		
		$entityManager = \Doctrine\ORM\EntityManager::create($database, $config);
		$databaseBuilder = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
		

		
		$files = scandir(__DIR__ . '\..\..\..\..\app\models\Entities\\');
		foreach ($files as $file) {
			if ($file !== '.' && $file !== '..') {
				require_once __DIR__ . '\..\..\..\..\app\models\Entities\\' . $file;
				dump(realpath(__DIR__ . '\..\..\..\..\app\models\Entities\\' . $file)); 
				try {
					$databaseBuilder->createSchema([$entityManager->getClassMetadata(str_replace('.php', '', "\\Kdyby\Doctrine\\$file"))]);
				} catch (\Doctrine\ORM\Tools\ToolsException $e) {
					//throw new \DatabaseException($e->getMessage());
					throw new \DatabaseException($e->getPrevious()->getPrevious()->getMessage(), $e->getPrevious()->getPrevious()->errorInfo[1], $e);
				}
			}
		}
	}
}