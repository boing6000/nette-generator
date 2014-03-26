<?php
namespace Utils;

/**
 * File operation class
 * @author Radek Brůha
 * @version 1.0
 */
class File {
	/**
	 * Read content of file
	 * @param string $source File source
	 * @return string File content
	 * @throws \FileException
	 * @static
	 */
	public static function read($source) {
		if (file_exists($source)) {
			if ($file = fopen($source, 'r')) {
				if ($content = fread($file, filesize($source))) {
					if (fclose($file)) {
						return $content;
					} else { throw new \FileException("Cannot close file $source."); }
				} else { throw new \FileException("Cannot read file $source."); }
			} else { throw new \FileException("Cannot open file $source."); }
		} else { throw new \FileException("Cannot find file $source."); }
	}

	/**
	 * Write content to file
	 * @param string $destination File destination
	 * @param string $content File content
	 * @return boolean
	 * @throws \FileException
	 * @static
	 */
	public static function write($destination, $content) {
		if (!is_dir(dirname($destination))) if (!mkdir(dirname($destination), 0777, TRUE)) throw new \FileException("Cannot create path $destination.");
		if ($file = fopen($destination, 'w')) {
			if (fwrite($file, $content)) {
				if (fclose($file)) {
					return TRUE;
				} else { throw new \FileException("Cannot close file $destination."); }
			} else { throw new \FileException("Cannot write file $destination."); }
		} else { throw new \FileException("Cannot find file $destination."); }
	}
	
	/**
	 * Copy all files and directories within directory to another directory
	 * @param string $source Source directory path
	 * @param string $destination Destinaion directory path
	 * @throws \FileException
	 * @static
	 */
	public static function copy($source, $destination) {
		if (!is_dir($destination)) if (!mkdir($destination, 0777, TRUE)) throw new \FileException("    => Cannot create directory $destination.");
		foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
			if ($item->isDir()) {
				$path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
				if (!mkdir($path)) throw new \FileException("    => Cannot create directory $path.");
				echo PHP_EOL . '    => ' . realpath($path);
			} else {
				$path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
				if (!copy($item, $path)) throw new \FileException("    => Cannot create file $path.");		
				echo PHP_EOL . '    => ' . realpath($path);
			}
		}
	}
	
	/**
	 * Clean Nette Framework cache directories and files
	 * @param string $path Cache location
	 * @throws \FileException
	 * @static
	 */
	public static function cacheClean($path) {
		$path = realpath($path) !== FALSE ? realpath($path) : $path;
		if (is_dir($path)) {
			foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
				if ($file->isDir()) { if (!rmdir($file->getRealPath())) throw new \FileException('    => Cannot remove directory ' . $file->getRealPath() . '.');	
				} else { if (!unlink($file->getRealPath())) throw new \FileException('    => Cannot remove file ' . $file->getRealPath() . '.'); }
			}
			if (!rmdir($path)) throw new \FileException("    => Cannot remove directory $path.");
		}
	}
}