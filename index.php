<?php
require_once(__DIR__ . '/SnapEngine/init.php');


echo $engine->render('index.snap.html', [
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
