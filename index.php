<?php
require_once(__DIR__ . '/SnapEngine/index.php');


function pre($data) {
	echo '<pre>';
	var_dump($data);
	echo '</pre>';
}


$engine = new SnapEngine();



$templateFile = __DIR__ . '/templates/index.snap.html';

echo $engine->render($templateFile, [
	'title' => 'The <b>Title</b>',
	'array' => [
		'a',
		'b',
		'c',
	],
	'items' => [
		['name' => 'Item 1', 'price' => '10'],
		['name' => 'Item 2', 'price' => '20'],
		['name' => 'Item 3', 'price' => '30'],
	],
]);
