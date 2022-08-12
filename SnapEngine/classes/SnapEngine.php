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

	private ?string $wrapperTemplate = null;
	private string $baseDirectory = __DIR__ . '/../templates/';


	public function __construct() {
		$this->registeredTags = new SnapEngineRegister();
		$this->registeredFilters = new SnapEngineRegister();

		SnapEngineParser::setEngine($this);
		SnapEngineParser::setTagRegister($this->registeredTags);
		SnapEngineParser::setFilterRegister($this->registeredFilters);

		$this->registerDefaultTags();
	}

	public function setWrapperTemplate(string $wrapperTemplate) {
		$this->wrapperTemplate = $this->readTemplateFile($wrapperTemplate);
	}

	public function setBaseDirectory(string $baseDirectory) {
		$baseDirectory = rtrim($baseDirectory, '/') . '/';
		if (!file_exists($baseDirectory) || !is_dir($baseDirectory)) {
			throw new SnapEngineException('Base directory does not exist or is not a directory');
		}
		$this->baseDirectory = $baseDirectory;
	}

	private function registerDefaultTags() {
		$this->registerTagPackage('core');
		$this->registerTagPackage('logic');
		return true;
	}

	public function registerTagPackage(string $packageName) {
		return $this->registerTagsFromFile(__DIR__ . '/../tags/' . $packageName . '.php');
	}

	public function registerTagsFromFile(string $file) {
		if (!file_exists($file)) {
			throw new SnapEngineException('registerTagsFromFile: File not found. ' . $file);
		}
		$tags = require($file);
		foreach ($tags as $tag) {
			$this->registerTag($tag);
		}
		return true;
	}

	public function registerTag(SnapTag $tag) {
		$this->registeredTags->add($tag->name(), $tag);
	}

	public function registerFilter(SnapFilter $filter) {
		$this->registeredFilters->add($filter->name(), $filter);
	}

	private function readTemplateFile($file) {
		$filePath = $this->baseDirectory . $file;
		if (!file_exists($filePath)) {
			throw new SnapEngineException('Template file not found: ' . $filePath, 404);
		}

		return file_get_contents($filePath);
	}

	public function render($file, $data = []) {

		$template = $this->readTemplateFile($file);

		$content = SnapEngineParser::parse($template, $data);

		if ($this->wrapperTemplate !== null) {
			$data['content'] = $content;
			return SnapEngineParser::parse($this->wrapperTemplate, $data);
		} else {
			return $content;
		}
	}
}
