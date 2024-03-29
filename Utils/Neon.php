<?php
namespace Utils;

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette
 */

/**
 * Simple parser & generator for Nette Object Notation.
 *
 * @author     David Grudl
 * @version    1.0
 */
class Neon {
	const BLOCK = 1;

	/** @var array */
	private static $patterns = array(
		'
			\'[^\'\n]*\' |
			"(?: \\\\. | [^"\\\\\n] )*"
		', // string
		'
			(?: [^#"\',:=[\]{}()\x00-\x20!`-] | [:-][^"\',\]})\s] )
			(?:
				[^,:=\]})(\x00-\x20]+ |
				:(?! [\s,\]})] | $ ) |
				[\ \t]+ [^#,:=\]})(\x00-\x20]
			)*
		', // literal / boolean / integer / float
		'
			[,:=[\]{}()-]
		', // symbol
		'?:\#.*', // comment
		'\n[\t\ ]*', // new line + indent
		'?:[\t\ ]+', // whitespace
	);

	/** @var string */
	private static $re;
	private static $brackets = array(
		'[' => ']',
		'{' => '}',
		'(' => ')',
	);

	/** @var string */
	private $input;

	/** @var array */
	public $tokens;

	/** @var int */
	private $n = 0;

	/** @var bool */
	private $indentTabs;

	/**
	 * Returns the NEON representation of a value.
	 * @param  mixed
	 * @param  int
	 * @return string
	 */
	public static function encode($var, $options = NULL) {
		if ($var instanceof DateTime) {
			return $var->format('Y-m-d H:i:s O');
		} elseif ($var instanceof NeonEntity) {
			return self::encode($var->value) . '(' . substr(self::encode($var->attributes), 1, -1) . ')';
		}

		if (is_object($var)) {
			$obj = $var;
			$var = array();
			foreach ($obj as $k => $v) {
				$var[$k] = $v;
			}
		}

		if (is_array($var)) {
			$isList = array_keys($var) === range(0, count($var) - 1);
			$s = '';
			if ($options & self::BLOCK) {
				if (count($var) === 0) {
					return "[]";
				}
				foreach ($var as $k => $v) {
					$v = self::encode($v, self::BLOCK);
					$s .= ($isList ? '-' : self::encode($k) . ':')
							. (strpos($v, "\n") === FALSE ? ' ' . $v : "\n\t" . str_replace("\n", "\n\t", $v))
							. "\n";
					continue;
				}
				return $s;
			} else {
				foreach ($var as $k => $v) {
					$s .= ($isList ? '' : self::encode($k) . ': ') . self::encode($v) . ', ';
				}
				return ($isList ? '[' : '{') . substr($s, 0, -2) . ($isList ? ']' : '}');
			}
		} elseif (is_string($var) && !is_numeric($var) && !preg_match('~[\x00-\x1F]|^\d{4}|^(true|false|yes|no|on|off|null)\z~i', $var) && preg_match('~^' . self::$patterns[1] . '\z~x', $var) // 1 = literals
		) {
			return $var;
		} elseif (is_float($var)) {
			$var = var_export($var, TRUE);
			return strpos($var, '.') === FALSE ? $var . '.0' : $var;
		} else {
			return json_encode($var);
		}
	}

	/**
	 * Decodes a NEON string.
	 * @param  string
	 * @return mixed
	 */
	public static function decode($input) {
		if (!is_string($input)) {
			throw new InvalidArgumentException("Argument must be a string, " . gettype($input) . " given.");
		}
		if (!self::$re) {
			self::$re = '~(' . implode(')|(', self::$patterns) . ')~Amix';
		}

		if (substr($input, 0, 3) === "\xEF\xBB\xBF") { // BOM
			$input = substr($input, 3);
		}

		$parser = new self;
		$parser->tokenize($input);
		$res = $parser->parse(0);

		while (isset($parser->tokens[$parser->n])) {
			if ($parser->tokens[$parser->n][0] === "\n") {
				$parser->n++;
			} else {
				$parser->error();
			}
		}
		return $res;
	}

	private function tokenize($input) {
		$this->input = str_replace("\r", '', $input);
		$this->tokens = preg_split(self::$re, $this->input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		if ($code = preg_last_error()) {
			throw new NeonException('PREG error', $code);
		}
		if ($this->tokens && !preg_match(self::$re, end($this->tokens))) {
			$this->n = count($this->tokens) - 1;
			$this->error();
		}
	}

	/**
	 * @param  int  indentation (for block-parser)
	 * @param  mixed
	 * @return array
	 */
	private function parse($indent = NULL, $result = NULL) {
		$inlineParser = $indent === NULL;
		$value = $key = $object = NULL;
		$hasValue = $hasKey = FALSE;
		$tokens = $this->tokens;
		$n = & $this->n;
		$count = count($tokens);

		for (; $n < $count; $n++) {
			$t = $tokens[$n];

			if ($t === ',') { // ArrayEntry separator
				if ((!$hasKey && !$hasValue) || !$inlineParser) {
					$this->error();
				}
				$this->addValue($result, $hasKey, $key, $hasValue ? $value : NULL);
				$hasKey = $hasValue = FALSE;
			} elseif ($t === ':' || $t === '=') { // KeyValuePair separator
				if ($hasKey || !$hasValue) {
					$this->error();
				}
				if (is_array($value) || is_object($value)) {
					$this->error('Unacceptable key');
				}
				$key = (string) $value;
				$hasKey = TRUE;
				$hasValue = FALSE;
			} elseif ($t === '-') { // BlockArray bullet
				if ($hasKey || $hasValue || $inlineParser) {
					$this->error();
				}
				$key = NULL;
				$hasKey = TRUE;
			} elseif (isset(self::$brackets[$t])) { // Opening bracket [ ( {
				if ($hasValue) {
					if ($t !== '(') {
						$this->error();
					}
					$n++;
					$entity = new NeonEntity;
					$entity->value = $value;
					$entity->attributes = $this->parse(NULL, array());
					$value = $entity;
				} else {
					$n++;
					$value = $this->parse(NULL, array());
				}
				$hasValue = TRUE;
				if (!isset($tokens[$n]) || $tokens[$n] !== self::$brackets[$t]) { // unexpected type of bracket or block-parser
					$this->error();
				}
			} elseif ($t === ']' || $t === '}' || $t === ')') { // Closing bracket ] ) }
				if (!$inlineParser) {
					$this->error();
				}
				break;
			} elseif ($t[0] === "\n") { // Indent
				if ($inlineParser) {
					if ($hasKey || $hasValue) {
						$this->addValue($result, $hasKey, $key, $hasValue ? $value : NULL);
						$hasKey = $hasValue = FALSE;
					}
				} else {
					while (isset($tokens[$n + 1]) && $tokens[$n + 1][0] === "\n")
						$n++; // skip to last indent
					if (!isset($tokens[$n + 1])) {
						break;
					}

					$newIndent = strlen($tokens[$n]) - 1;
					if ($indent === NULL) { // first iteration
						$indent = $newIndent;
					}
					if ($newIndent) {
						if ($this->indentTabs === NULL) {
							$this->indentTabs = $tokens[$n][1] === "\t";
						}
						if (strpos($tokens[$n], $this->indentTabs ? ' ' : "\t")) {
							$n++;
							$this->error('Either tabs or spaces may be used as indenting chars, but not both.');
						}
					}

					if ($newIndent > $indent) { // open new block-array or hash
						if ($hasValue || !$hasKey) {
							$n++;
							$this->error('Unexpected indentation.');
						} else {
							$this->addValue($result, $key !== NULL, $key, $this->parse($newIndent));
						}
						$newIndent = isset($tokens[$n]) ? strlen($tokens[$n]) - 1 : 0;
						$hasKey = FALSE;
					} else {
						if ($hasValue && !$hasKey) { // block items must have "key"; NULL key means list item
							break;
						} elseif ($hasKey) {
							$this->addValue($result, $key !== NULL, $key, $hasValue ? $value : NULL);
							$hasKey = $hasValue = FALSE;
						}
					}

					if ($newIndent < $indent) { // close block
						return $result; // block parser exit point
					}
				}
			} else { // Value
				if ($hasValue) {
					$this->error();
				}
				static $consts = array(
					'true' => TRUE, 'True' => TRUE, 'TRUE' => TRUE, 'yes' => TRUE, 'Yes' => TRUE, 'YES' => TRUE, 'on' => TRUE, 'On' => TRUE, 'ON' => TRUE,
					'false' => FALSE, 'False' => FALSE, 'FALSE' => FALSE, 'no' => FALSE, 'No' => FALSE, 'NO' => FALSE, 'off' => FALSE, 'Off' => FALSE, 'OFF' => FALSE,
				);
				if ($t[0] === '"') {
					$value = preg_replace_callback('#\\\\(?:u[0-9a-f]{4}|x[0-9a-f]{2}|.)#i', array($this, 'cbString'), substr($t, 1, -1));
				} elseif ($t[0] === "'") {
					$value = substr($t, 1, -1);
				} elseif (isset($consts[$t])) {
					$value = $consts[$t];
				} elseif ($t === 'null' || $t === 'Null' || $t === 'NULL') {
					$value = NULL;
				} elseif (is_numeric($t)) {
					$value = $t * 1;
				} elseif (preg_match('#\d\d\d\d-\d\d?-\d\d?(?:(?:[Tt]| +)\d\d?:\d\d:\d\d(?:\.\d*)? *(?:Z|[-+]\d\d?(?::\d\d)?)?)?\z#A', $t)) {
					$value = new DateTime($t);
				} else { // literal
					$value = $t;
				}
				$hasValue = TRUE;
			}
		}

		if ($inlineParser) {
			if ($hasKey || $hasValue) {
				$this->addValue($result, $hasKey, $key, $hasValue ? $value : NULL);
			}
		} else {
			if ($hasValue && !$hasKey) { // block items must have "key"
				if ($result === NULL) {
					$result = $value; // simple value parser
				} else {
					$this->error();
				}
			} elseif ($hasKey) {
				$this->addValue($result, $key !== NULL, $key, $hasValue ? $value : NULL);
			}
		}
		return $result;
	}

	private function addValue(&$result, $hasKey, $key, $value) {
		if ($hasKey) {
			if ($result && array_key_exists($key, $result)) {
				$this->error("Duplicated key '$key'");
			}
			$result[$key] = $value;
		} else {
			$result[] = $value;
		}
	}

	private function cbString($m) {
		static $mapping = array('t' => "\t", 'n' => "\n", 'r' => "\r", 'f' => "\x0C", 'b' => "\x08", '"' => '"', '\\' => '\\', '/' => '/', '_' => "\xc2\xa0");
		$sq = $m[0];
		if (isset($mapping[$sq[1]])) {
			return $mapping[$sq[1]];
		} elseif ($sq[1] === 'u' && strlen($sq) === 6) {
			return iconv('UTF-32BE', 'UTF-8//IGNORE', pack('N', hexdec(substr($sq, 2))));
		} elseif ($sq[1] === 'x' && strlen($sq) === 4) {
			return chr(hexdec(substr($sq, 2)));
		} else {
			$this->error("Invalid escaping sequence $sq");
		}
	}

	private function error($message = "Unexpected '%s'") {
		$tokens = preg_split(self::$re, $this->input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE);
		$offset = isset($tokens[$this->n]) ? $tokens[$this->n][1] : strlen($this->input);
		$line = $offset ? substr_count($this->input, "\n", 0, $offset) + 1 : 1;
		$col = $offset - strrpos(substr($this->input, 0, $offset), "\n");
		$token = isset($this->tokens[$this->n]) ? str_replace("\n", '<new line>', substr($this->tokens[$this->n], 0, 40)) : 'end';
		throw new NeonException(str_replace('%s', $token, $message) . " on line $line, column $col.");
	}

}

/**
 * Representation of 'foo(bar=1)' literal
 */
class NeonEntity extends \stdClass {

	public $value;
	public $attributes;

}

/**
 * The exception that indicates error of NEON decoding.
 */
class NeonException extends \Exception {
	
}
