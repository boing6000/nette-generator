<?php
namespace Utils;

/**
 * Database wrapper for MySQLi connection with basic exceptions handling
 * @author Radek Brůha
 * @version 1.0
 */
class Database extends \MySQLi {
	private $hostname;
	private $username;
	private $password;
	private $database;

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
	public function buildFromEntities() {
		(new \Doctrine\Common\ClassLoader('Doctrine', __DIR__))->register();
		$config = new \Doctrine\ORM\Configuration();
		$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());
		$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
		$config->setProxyDir(__DIR__ . '/Proxies');
		$config->setProxyNamespace('Proxies');

		$entityManager = \Doctrine\ORM\EntityManager::create(array(
					'host' => $this->hostname, 'user' => $this->username,
					'password' => $this->password, 'dbname' => $this->database,
					'driver' => 'pdo_mysql'), $config);
		$databaseBuilder = new \Doctrine\ORM\Tools\SchemaTool($entityManager);

		$files = scandir(__DIR__ . '\..\..\..\..\app\model\Entities\\');
		foreach ($files as $file) {
			if ($file !== '.' && $file !== '..') {
				require_once __DIR__ . '\..\..\..\..\app\model\Entities\\' . $file;
				try {
					$databaseBuilder->createSchema(array($entityManager->getClassMetadata(str_replace('.php', '', $file))));
				} catch (\Doctrine\ORM\Tools\ToolsException $e) {
					throw new \DatabaseException($e->getPrevious()->getPrevious()->getMessage(), $e->getPrevious()->getPrevious()->errorInfo[1], $e);
				}
			}
		}
	}
}