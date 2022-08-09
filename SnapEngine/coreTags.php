<?php

class SnapEngineCoreTags {
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

	public static function foreach($args) {
		$data = SnapEngineParser::lookupVariable($args['params']['key']);
		if (is_array($data)) {
			return SnapEngineParser::parseLoop($args['content'], $data);
		} else {
			return $data;
		}
	}
}

return [
	new SnapTag('var', ['SnapEngineCoreTags', 'var'], [
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

	new SnapTag('foreach', ['SnapEngineCoreTags', 'foreach'], [
		'description' => 'Ruft ein Template in einer Schleife mit den daten aus $key auf',
		'parameters' => [
			'key' => [
				'description' => 'the key of the variable in the template Data array',
				'type' => 'string',
				'default' => '*',
				'position' => 1,
			],
		],
	]),

	new SnapTag('testtag', function ($args) {
		return 'testtagFunction';
	}, [
		'description' => 'Nur ein Testtag',
	]),
];
