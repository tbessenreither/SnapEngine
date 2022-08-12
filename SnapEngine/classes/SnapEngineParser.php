<?php

class SnapEngineParserException extends Exception {
}
class SnapEngineParserSkipExecution extends Exception {
}
class SnapEngineParserInvalidParameter extends Exception {
}
class SnapEngineParserVariableNotFound extends Exception {
}

class SnapEngineTemplateFromVar {
}

class SnapEngineParser {

	public static ?SnapEngine $engine = null;
	private static ?SnapEngineRegister $registeredTags = null;
	private static ?SnapEngineRegister $registeredFilters = null;

	private static int $replacedTags = 0;

	private static $templateData = [];
	private static $templateTagIds = [];
	private static $templateInnerTagMap = [];

	private static $dataStack = [];

	public static function setEngine(SnapEngine $engine) {
		self::$engine = $engine;
	}
	public static function setTagRegister(SnapEngineRegister $register) {
		self::$registeredTags = $register;
	}
	public static function setFilterRegister(SnapEngineRegister $register) {
		self::$registeredFilters = $register;
	}

	public static function pre($content) {
		if (isset($content['data'])) {
			$content['data'] = '--removed--';
		}
		echo '<pre>';
		var_dump($content);
		echo '</pre>';
	}

	private static function cleanMatchFromNumerics(&$match) {
		foreach ($match as $key => $value) {
			if (is_numeric($key)) {
				unset($match[$key]);
			}
		}
	}

	private static function getLatestData() {
		if (count(self::$dataStack) == 0) {
			return [];
		} else {
			return self::$dataStack[count(self::$dataStack) - 1];
		}
	}

	private static function getGlobalData() {
		if (count(self::$dataStack) == 0) {
			return [];
		} else {
			return self::$dataStack[0];
		}
	}

	/**
	 * Lookup wrapper for use in tags to lookup special vars like true, false or null by prefixing the var with $
	 *
	 * @param string $operator
	 * @param mixed $data
	 * @return mixed The value of the var
	 */
	public static function lookupSpecialOperator(string $variableName, &$data = null) {
		if ($variableName === '$null') {
			return null;
		} else if ($variableName === '$true') {
			return true;
		} else if ($variableName === '$false') {
			return false;
		} else {
			$opSource = substr($variableName, 1);
			return self::lookupVariable(trim($opSource), $data);
		}
	}

	/**
	 * Lookup of variables within scopes
	 * @param string $key the key to be searched
	 * @param array $data the array to be searched when scope is local
	 * @return mixed data or null
	 */
	public static function lookupVariable($key, &$data = null) {
		if ($data === null) {
			$data = self::getLatestData();
		}
		//map scope from $key syntax $key@$scope
		if (strpos($key, '@') !== false) {
			$key = explode('@', $key);
			$scope = array_pop($key);
			$key = implode('@', $key);
		}
		/** */

		if (strpos($key, '>') !== false) {
			$key = explode('>', $key);
			$filters = [];
			while (sizeof($key) > 1) {
				$filters[] = array_pop($key);
			}
			$filters = array_reverse($filters);
			$key = implode('>', $key);
		} else {
			$filters = false;
		}

		//Set defaults
		if (!isset($scope)) {
			$scope = 'local';
		}

		$returnValue = null;
		try {
			//scope switch
			if ($scope === 'local') {
				$returnValue = self::walkArray($key, $data);
			} else if ($scope === 'global') {
				$globalData = self::getGlobalData();
				$returnValue = self::walkArray($key, $globalData);
			} else if ($scope === 'system') {
				$returnValue = self::walkArray($key, $GLOBALS);
			} else if ($scope === 'last') {
				if ($data['template']['previous'] === null) {
					$returnValue = '';
				} else {
					$returnValue = self::walkArray($key, $data['template']['previous']);
				}
			} else if ($scope === 'session') {
				$returnValue = self::walkArray($key, $_SESSION);
			} else {
				return null;
			}
		} catch (SnapEngineParserVariableNotFound $ex) {
			$returnValue = null;
		}

		//todo: insert hook here

		if ($filters !== false) {
			foreach ($filters as $filter) {
				$returnValue = self::applyFilter($filter, $returnValue, $data);
			}
		}

		return $returnValue;
	}

	private static function applyFilter($filter, $value, $data) {
		return $value;
	}

	/**
	 * Walk through an array and get the wanted dataset or null if it is not found
	 * @param string $path the path separated by . or a single * for everything
	 * @param array $data the array to be walked
	 * @param string $completePath the path shown in the error message
	 * @return mixed data or null
	 */
	private static function walkArray($path, &$data, $completePath = null) {
		//fast exit when * operator is found
		if (!is_array($path) && $path === '*') {
			return $data;
		}

		//set the complete path if it is not set for error reporting
		if ($completePath === null) {
			$completePath = $path;
		}
		//explode $path to array if needed
		if (!is_array($path)) {
			$path = explode('.', $path);
		}

		//get the search key
		$searchKey = array_shift($path);
		//prepare target
		$target = null;

		//walk the array
		if (isset($data[$searchKey]) || in_array($searchKey, array_keys($data))) {
			$target = &$data[$searchKey];
		} else {
			throw new SnapEngineParserVariableNotFound($completePath);
		}

		//depending if path is left to walk continue or return the target
		if (sizeof($path) > 0) {
			return self::walkArray($path, $target, $completePath);
		} else {
			//when var is null return empty string. because it was found and would cause an error otherwise
			return $target;
		}
	}

	/**
	 * Function to cast a value to boolean. It detects yes, no, y, n, on, off, enabled, disabled, selected as boolean values
	 * Other values are detected via weak comparison ==
	 * @param mixed $value the value to be casted
	 * @param bool $strict = false; When true the function will return null when no matching type is found. Otherwise it will use php weak comparison (==) to cast
	 * @return bool true or false
	 * @return null if strict is true and no match was found
	 */
	public static function castToBool($value, $strict = false) {
		if ($value === true || $value === false) {
			//the default boolean values
			$output = $value;
		} else if ($value === 1 || $value === 0 || $value === '1' || $value === '0') {
			//extended booleans
			$output = ($value == true);
		} else if (in_array(mb_strtolower($value), ['true', 'yes', 'y', 'j', 'ja', 'on', 'enabled', 'selected', 'an'])) {
			//string alias for true
			$output = true;
		} else if (in_array(mb_strtolower($value), ['false', 'no', 'n', 'nein', 'off', 'disabled', '', 'aus'])) {
			//string alias for false
			$output = false;
		} else if ($strict === false) {
			$output = ($value == true);
		} else {
			$output = null;
		}
		return $output;
	}

	private static function maskBatlethTags(&$content) {
		$detectorRegex = '#{{(?P<batlethTag>[^\}\#]+)\}\}#Uis';
		$content = preg_replace_callback($detectorRegex, function ($match) {
			$tag = explode('::', $match['batlethTag']);
			$tag = array_shift($tag);
			if (substr($tag, 0, 1) === '/') {
				$isClosingTag = true;
				$tag = substr($tag, 1);
			} else {
				$isClosingTag = false;
			}

			if (!isset(self::$templateTagIds[$tag])) {
				self::$templateTagIds[$tag] = ['count' => 0, 'stack' => []];
			}

			if ($isClosingTag === true) {
				if (sizeof(self::$templateTagIds[$tag]['stack']) === 0) {
					throw new SnapEngineParserException('Unmatched closing tag: ' . $match[0]);
				}
				$tagId = array_pop(self::$templateTagIds[$tag]['stack']);
			} else {
				if (isset(self::$templateInnerTagMap[$tag])) {
					$mappedTag = self::$templateInnerTagMap[$tag];
					$tagId = end(self::$templateTagIds[$mappedTag]['stack']);
					reset(self::$templateTagIds[$mappedTag]['stack']);
				} else {
					$tagId = self::$templateTagIds[$tag]['count']++;
					array_push(self::$templateTagIds[$tag]['stack'], $tagId);
				}
			}

			return str_replace('}}', '#' . $tagId . '}}', $match[0]);
		}, $content);

		return $content;
	}

	public static function replaceTags(&$content, &$data) {
		array_push(self::$dataStack, $data);
		$tagRegex = '#{{(?P<batlethTag>[\w_]{2,})(?P<paramsIndex>(?:\:\:[^|\#]+)+)?(?:\?(?P<paramsQuery>.+))?(?<paramsFilter>\|(?:[\w\|]+))?(?:\#(?P<tagNum>[\d]+))?}}(?:(?<content>.+)\{\{/(?P=batlethTag)\#(?P=tagNum)\}\})??#Uis';

		$content = preg_replace_callback($tagRegex, function ($match) {
			self::cleanMatchFromNumerics($match);

			$match['data'] = self::getLatestData();
			if (!isset($match['paramsFilter'])) {
				$match['paramsFilter'] = [];
			}
			if (!is_array($match['paramsFilter'])) {
				$match['paramsFilter'] = trim($match['paramsFilter'], '|');
				$match['paramsFilter'] = explode('|', $match['paramsFilter']);
			}

			self::$replacedTags++;

			try {
				//prepare Lookup Tag
				$lookupTag = mb_strtolower($match['batlethTag']);

				//lookup matching tag
				if (self::$registeredTags->get($lookupTag) === null) {
					//quit if the tag is not defined
					throw new SnapEngineParserException('invalid Batleth-Tag >' . $match['batlethTag'] . '<');
				}
				$matchedTag = self::$registeredTags->get($lookupTag);

				//call function to replace
				$responseData = $matchedTag->run($match);

				//run template when tag has returned TemplateFromVar object
				if ($responseData instanceof SnapEngineTemplateFromVar) {
					/*
					if (isset($match['params']['template'])) {
						$templateToUse = $match['params']['template'];
					} else {
						$templateToUse = $responseData->get();
					}
					$responseData = self::templateRun($templateToUse, $responseData->data());
					/** */
				}

				//apply filters
				foreach ($match['paramsFilter'] as &$filter) {
					//$responseData = self::applyFilter($filter, $responseData, $match['params']);
				}

				return $responseData;
			} catch (SnapEngineParserSkipExecution $ex) {
				//push data back onto the stack for the next run

				return $ex->getMessage();
			} catch (SnapEngineParserInvalidParameter $ex) {
				//push data back onto the stack for the next run
				return 'Invalid Batleth-Tag >' . $match['batlethTag'] . '< // ' . $ex->getMessage() . ' // example: ';
			}
		}, $content);
		array_pop(self::$dataStack);
	}

	private static function rescanTags() {
		if (!self::$registeredTags->rescan()) {
			return false;
		}

		self::$templateInnerTagMap = [];
		$tags = self::$registeredTags->getAll();
		foreach ($tags as $tag) {
			$isContentTagValue = $tag->property('isContentTag');
			if ($isContentTagValue) {
				self::$templateInnerTagMap[$isContentTagValue] = $tag->name();
			}
		}

		return true;
	}

	public static function parse($content, $data = null) {
		self::rescanTags();


		//$tagPrefix = self::envVarGet('templateTagPrefix', true);
		//replace shorthand alias
		$content = strtr($content, []);

		//num all tags

		self::maskBatlethTags($content);

		self::replaceTags($content, $data);

		return $content;
	}

	public static function parseLoop($content, &$array) {
		$output = [];
		foreach ($array as &$value) {
			$output[] = self::parse($content, $value);
		}
		unset($value);

		return implode("\n", $output);
	}
}
