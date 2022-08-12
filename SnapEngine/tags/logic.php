<?php

class SnapEngineLogicTags {

	public static function foreach($args) {
		$data = SnapEngineParser::lookupVariable($args['params']['key']);
		if (is_array($data)) {
			return SnapEngineParser::parseLoop($args['content'], $data);
		} else {
			return $data;
		}
	}

	public static function if($args) {
		if (isset($args['content'])) {
			$contentMode = true;
			$contentParts = explode('{{else#' . $args['tagNum'] . '}}', $args['content']);
			$args['params']['ifTrue'] = array_shift($contentParts);
			if (sizeof($contentParts) > 0) {
				$args['params']['ifFalse'] = array_shift($contentParts);
			}
		} else {
			$contentMode = false;
		}
		$opA = null;
		$opB = null;

		$operatorFound = false;
		$knownOperators = ['!=', '<=', '>=', '<', '>', '='];
		foreach ($knownOperators as $operator) {
			if (strpos($args['params']['condition'], $operator)) {
				$operatorFound = $operator;
				$operators = explode($operator, $args['params']['condition']);

				$opASource = array_shift($operators);
				$opASource = trim($opASource);
				if (strpos($opASource, '$') === 0) {
					$opA = SnapEngineParser::lookupSpecialOperator($opASource);
				} else {
					$opA = $opASource;
				}

				if (is_array($opA)) {
					$opA = sizeof($opA);
				}

				$opBSource = array_pop($operators);
				$opBSource = trim($opBSource);
				if (strpos($opBSource, '$') === 0) {
					$opB = SnapEngineParser::lookupSpecialOperator($opBSource);
				} else {
					$opB = $opBSource;
				}

				if (is_array($opB)) {
					$opB = sizeof($opB);
				}
				break;
			}
		}

		$conditionResult = false;
		if ($operatorFound !== false) {
			if ($operatorFound === '=') {
				$conditionResult = ($opA == $opB);
			} else if ($operatorFound === '!=') {
				$conditionResult = ($opA != $opB);
			} else if ($operatorFound === '<') {
				$conditionResult = ($opA < $opB);
			} else if ($operatorFound === '<=') {
				$conditionResult = ($opA <= $opB);
			} else if ($operatorFound === '>=') {
				$conditionResult = ($opA >= $opB);
			} else if ($operatorFound === '>') {
				$conditionResult = ($opA > $opB);
			}
		} else {
			$var = SnapEngineParser::lookupVariable($args['params']['condition']);
			if (is_array($var)) {
				$var = sizeof($var);
			}
			$conditionResult = ($var == true);
		}

		if ($conditionResult === true) {
			$responsePart = $args['params']['ifTrue'];
		} else {
			$responsePart = $args['params']['ifFalse'];
		}

		if ($contentMode === true) {

			if (isset($data['template']) && isset($data['template']['depth'])) {
				$currentDepth = $data['template']['depth'];
			} else {
				$currentDepth = 0;
			}

			$responsePart = SnapEngineParser::parse($responsePart, $args['data']);
		}

		return $responsePart;
	}
}

return [

	new SnapTag('foreach', ['SnapEngineLogicTags', 'foreach'], [
		'description' => 'uses it\'s content to print it out for every item in the array $key',
		'parameters' => [
			'key' => [
				'description' => 'the key of the variable in the template Data array',
				'type' => 'string',
				'default' => '*',
				'position' => 1,
			],
		],
	]),

	new SnapTag('if', ['SnapEngineLogicTags', 'if'], [
		'description' => 'outputs the wrapped content if condition is matched. Can contain an {{else}} tag to be output if the condition is not matched',
		'parameters' => [
			'condition' => [
				'description' => 'the condition to be matched',
				'type' => 'string',
				'default' => '*',
				'position' => 1,
			],
			'ifTrue' => [
				'description' => 'the content to be output if the condition is matched',
				'type' => 'string',
				'default' => '',
			],
			'ifFalse' => [
				'description' => 'the content to be output if the condition is not matched',
				'type' => 'string',
				'default' => '',
			],
		],
		'isContentTag' => 'else',
	]),
];
