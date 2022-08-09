<?php

class SnapTagException extends Exception {
}

class SnapTag {
	private ?string $name = null;
	private $properties = [];
	private $propertyPositionMap = [];
	private $callback = null;

	public function __construct(string $name, callable $callback, $properties = []) {
		$this->name = $name;
		$this->callback = $callback;
		$this->setProperties($properties);
	}

	public function setProperties($properties) {
		if (!isset($properties['description'])) {
			throw new SnapTagException('Tag "' . $this->name . '" has no description');
		}
		if (!isset($properties['arguments'])) {
			$properties['arguments'] = [];
		}
		foreach ($properties['arguments'] as $paramKey => &$paramValue) {
			if (!isset($paramValue['type'])) {
				throw new SnapTagException('Tag argument "' . $paramKey . '" must have a type');
			}
			if (!isset($paramValue['description'])) {
				throw new SnapTagException('Tag argument "' . $paramKey . '" must have a description');
			}
			if (!isset($paramValue['position'])) {
				$paramValue['position'] = null;
			}
			if (!isset($paramValue['required'])) {
				$paramValue['required'] = false;
			}
			if (!isset($paramValue['isContentTag'])) {
				$paramValue['isContentTag'] = false;
			}
			if (isset($paramValue['default'])) {
				$paramValue['required'] = false;
			} else {
				$paramValue['default'] = null;
			}

			if ($paramValue['position'] !== null) {
				$this->propertyPositionMap[$paramValue['position'] - 1] = $paramKey;
			}
		}
		unset($paramValue);

		$this->properties = $properties;
	}

	public function name() {
		return $this->name;
	}

	public function property(string $property) {
		if (isset($this->properties[$property])) {
			return $this->properties[$property];
		} else {
			return null;
		}
	}

	private function setCallProps(&$props) {
		if (!isset($props['params'])) {
			$props['params'] = [];
		}
		if (!isset($props['paramsIndex'])) {
			$props['paramsIndex'] = '';
		}
		$props['paramsIndex'] = trim($props['paramsIndex'], ':');
		$props['paramsIndex'] = explode('::', $props['paramsIndex']);

		foreach ($props['paramsIndex'] as $paramIndex => $paramsValue) {
			if (isset($this->propertyPositionMap[$paramIndex])) {
				$props['params'][$this->propertyPositionMap[$paramIndex]] = $paramsValue;
			}
		}
	}

	private function setCallPropsQuery(&$props) {
		if (isset($props['paramsQuery']) && $props['paramsQuery'] !== '') {
			$parsed = [];
			parse_str($props['paramsQuery'], $parsed);
			foreach ($parsed as $key => $value) {
				if (!isset($this->properties['arguments'][$key])) {
					throw new SnapEngineParserInvalidParameter('Tag "' . $this->name . '" has no parameter "' . $key . '" that is passed in via get query');
				}
				$props['params'][$key] = $value;
			}
			unset($value);
		}
	}

	private function setCallPropsDefaults(&$props) {
		foreach ($this->properties['arguments'] as $paramKey => $paramSetting) {
			if (!isset($props['params'][$paramKey]) && isset($paramSetting['default'])) {
				$props['params'][$paramKey] = $paramSetting['default'];
			}

			if (!isset($props['params'][$paramKey]) && $paramSetting['required'] === true) {
				throw new SnapEngineParserInvalidParameter('Missing required parameter: ' . $paramKey);
			}
		}
	}

	private function setCallPropsTypecasting(&$props) {
		foreach ($props['params'] as $paramKey => &$paramValue) {
			$settings = $this->properties['arguments'][$paramKey];
			if ($settings['type'] === 'bool') {
				$paramValue = SnapEngineParser::castToBool($paramValue);
			} else if ($settings['type'] === 'int') {
				$paramValue = intval($paramValue);
			} else if ($settings['type'] === 'float') {
				$paramValue = floatval($paramValue);
			} else if ($settings['type'] === 'string') {
				$paramValue = strval($paramValue);
			} else if ($settings['type'] === 'data') {
				$paramValue = SnapEngineParser::lookupVariable($paramValue);
			}
		}
		unset($paramValue);
	}

	private function callbackProperties($props = []): array {
		$this->setCallProps($props);
		$this->setCallPropsQuery($props);
		$this->setCallPropsDefaults($props);
		$this->setCallPropsTypecasting($props);

		//pre($this->properties);
		//pre($props);
		return $props;
	}

	public function run($props = []) {
		return call_user_func($this->callback, $this->callbackProperties($props));
	}
}
