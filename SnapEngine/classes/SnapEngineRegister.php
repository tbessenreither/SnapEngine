<?php

class SnapEngineRegisterException extends Exception {
}

class SnapEngineRegister {
	private bool $rescan = true;
	private $register = [];

	public function add(string $key, $element) {
		$this->rescan = true;

		if (isset($this->register[$key])) {
			throw new SnapEngineRegisterException("A tag with the name '$key' is already registered");
		}
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

	/**
	 * Getter for all registered elements
	 *
	 * @return array
	 */
	public function getAll() {
		return $this->register;
	}

	/**
	 * getter to show if rescan is needed
	 * @return boolean
	 */
	public function needsRescan() {
		return $this->rescan;
	}

	/**
	 * resets the rescan flag
	 */
	public function resetRescan() {
		$this->rescan = false;
	}

	/**
	 * getter for rescann and reset in one
	 * @return boolean
	 */
	public function rescan() {
		$rescan = $this->needsRescan();
		$this->resetRescan();
		return $rescan;
	}
}
