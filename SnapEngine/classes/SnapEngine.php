<?php
require_once(__DIR__ . '/SnapTag.php');
require_once(__DIR__ . '/SnapFilter.php');
require_once(__DIR__ . '/SnapEngineRegister.php');
require_once(__DIR__ . '/SnapEngineParser.php');


class SnapEngineException extends Exception {
}

class SnapEngine {
	private ?SnapEngineRegister $registeredTags = null;
	private ?SnapEngineRegister $registeredFilters = null;


	public function __construct() {
		$this->registeredTags = new SnapEngineRegister();
		$this->registeredFilters = new SnapEngineRegister();

		SnapEngineParser::setTagRegister($this->registeredTags);
		SnapEngineParser::setFilterRegister($this->registeredFilters);

		$this->registerDefaultTags();
	}

	private function registerDefaultTags() {
		$tags = include(__DIR__ . '/../coreTags.php');
		foreach ($tags as $tag) {
			$this->registerTag($tag);
		}
	}

	public function registerTag(SnapTag $tag) {
		$this->registeredTags->add($tag->name(), $tag);
	}

	public function registerFilter(SnapFilter $filter) {
		$this->registeredFilters->add($filter->name(), $filter);
	}

	public function render($file, $data = null) {
		$template = $this->readTemplateFile($file);

		return SnapEngineParser::parse($template, $data);

		return $template;
	}

	private function readTemplateFile($file) {
		if (!file_exists($file)) {
			throw new SnapEngineException('Template file not found: ' . $file, 404);
		}

		return file_get_contents($file);
	}
}
