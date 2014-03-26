<?php
namespace Utils;

/**
 * Config loading class
 * @author Radek Brůha
 * @version 1.0
 */
class Config {
    /**
     * Load configuration from config.neon file which is part of Nette Framework
     * @param string $path Config.neon file location
     * @return string Loaded configuration
     * @throws \ConfigException
     * @static
     */
    public static function load($path) {
		$path = realpath($path) !== FALSE ? realpath($path) : $path;
		$config = (new \Utils\Neon())->decode(\Utils\File::read($path));
		if (array_key_exists('parameters', $config)) {
			if (array_key_exists('database', $config['parameters'])) {
				return $config['parameters']['database'];
			} else { throw new \ConfigException("Config file $path does not contain parameters.database part."); }
		} else { throw new \ConfigException("Config $path does not contain parameters part."); }
	}
}