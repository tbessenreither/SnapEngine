<?php

namespace SnapEngine\Filter;

class SnapFilter {
	private ?string $name = null;
	private $properties = [];
	private $callback = null;

	public function __construct(string $name, callable $callback, $properties = []) {
		$this->name = $name;
		$this->callback = $callback;
		$this->setProperties($properties);
	}

	public function setProperties($properties) {
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

	private function callbackProperties($props = []): array {
		return $props;
	}

	public function run($props = []) {
		return call_user_func($this->callback, $this->callbackProperties($props));
	}
}
