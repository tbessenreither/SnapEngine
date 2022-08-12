<?php

namespace SnapEngine\Tag;

use SnapEngine\SnapEngineParser;
use SnapEngine\SnapEngineException;


class SnapEngineCoreTags {
	private static function preHelper($args) {

		ob_start();
		var_dump($args);
		$pre = ob_get_contents();
		ob_end_clean();

		$replace = array(
			"=>\n"				=> '=>',
			"array(0) {\n"		=> 'array(0) {',
			"  "				=> "   ",
			"{      }"			=> '{}',
			'{            }'	=> '{}',
			'=>      '			=> "=>\t",
		);
		$pre = str_replace(array_keys($replace), array_values($replace), $pre);
		$pre = '<pre class="debug">' . PHP_EOL . htmlentities($pre) . "</pre><br/>" . PHP_EOL;
		return $pre;
	}
	public static function var($args) {
		$variable = SnapEngineParser::lookupVariable($args['params']['key']);

		//no variable was found
		if ($variable === null) {
			if ($args['params']['default'] !== null) {
				return $args['params']['default'];
			} else {
				return 'undefined variable >' . $args['params']['variable'] . '<';
			}
		}

		if ($args['params']['encoding'] === 'none') {
			return $variable;
		} else if ($args['params']['encoding'] === 'html') {
			return htmlspecialchars($variable);
		} else if ($args['params']['encoding'] === 'url') {
			return urlencode($variable);
		} else if ($args['params']['encoding'] === 'json') {
			return json_encode($variable);
		} else {
			return htmlspecialchars($variable);
		}

		return $variable;
	}

	public static function pre($args) {
		return SnapEngineCoreTags::preHelper(SnapEngineParser::lookupVariable($args['params']['key']));
	}

	public static function template($args) {
		try {
			$data = SnapEngineParser::lookupVariable($args['params']['key']);
			return SnapEngineParser::$engine->render($args['params']['template'], $data);
		} catch (SnapEngineException $ex) {
			return $ex->getMessage();
		}
	}
}

return [
	new SnapTag('var', ['SnapEngine\Tag\SnapEngineCoreTags', 'var'], [
		'description' => 'Gibt eine Variable aus. Füge Scope über @ nach dem Variablennamen hinzu',
		'parameters' => [
			'key' => [
				'description' => 'the key of the variable in the template Data array',
				'type' => 'string',
				'position' => 1,
				'required' => true,
			],
			'encoding' => [
				'description' => 'the output encoding of the variable',
				'type' => 'string',
				'position' => 2,
				'default' => 'html',
				'possibleValues' => ['html', 'none', 'url', 'json'],
			],
			'default' => [
				'description' => 'the default value if the variable does not exist',
				'type' => 'string',
				'position' => 3,
				'default' => null,
			],
		],
	]),

	new SnapTag('pre', ['SnapEngine\Tag\SnapEngineCoreTags', 'pre'], [
		'description' => 'creates debug output of the variable $key',
		'parameters' => [
			'key' => [
				'description' => 'the key of the variable in the template Data array',
				'type' => 'string',
				'default' => '*',
				'position' => 1,
			],
		],
	]),

	new SnapTag('template', ['SnapEngine\Tag\SnapEngineCoreTags', 'template'], [
		'description' => 'Ruft ein Template mit den daten aus $dataKey auf',
		'parameters' => [
			'template' => [
				'description' => 'the template to call',
				'required' => true,
				'type' => 'string',
				'position' => 1,
			],
			'key' => [
				'description' => 'the key of the data passed through to the template',
				'default' => '*',
				'type' => 'mixed',
				'position' => 2,
			],
		],
	]),
];
