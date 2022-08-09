<?php

class SnapEngineRegister {
	private $register = [];

	public function add(string $key, $element) {
		$this->register[$key] = $element;
	}

	/**
	 * Getter for registered element by key
	 *
	 * @param string $key
	 * @return element or null if not found
	 */
	public function get(string $key) {
		if (isset($this->register[$key])) {
			return $this->register[$key];
		} else {
			return null;
		}
	}
}
