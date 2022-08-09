# Snap Template Engine

## Introduction

This will be a port of my qCore Template Engine as a standalone class.

It's meant to be a lightweight but highly extensible template engine.

It uses the Batleth Tag Syntax, a mixture between Laravel Blade and Contao Insert Tags.

## Features

New Tags are easy to add by registering it to the engine.

Each Tag is of the SnapTag class and is created with a tag name, a callback and a configuration of what parameters it receives via the Bathleth Tags.

All parameters will be found in the 'params' key of $args passed 

```php
$engine = new SnapEngine();

$engine->registerTag(new SnapTag('my_tag', function($args) {
	return $args['params']['arg1'];
}, [
	'description' => 'This is a mandatory description of what the tag is doing. If you don\'t provide one the script will fail. Documentation is key.',
	'parameters' => [
		'arg1' => [
			'description' => 'This is a mandatory description of what the argument is for. If you don\'t provide one the script will fail. Documentation is key.',
			'type' => 'string', //this field is required. The Engine will cast all data you get to the correct type. No further actions required
			'required' => true, //is it required
			'default' => 'default value', // what's the default value. if this is set required will be set to false!
			'position' => 1, // the position of the parameter in the Bathleth Tag. Human readable Int starting with 1
		],
	],
]));
```

## Batleth Tag

Tags are written as follows

```html
{{tagname::arg1Value[::argXValue ...]}}
```
Parameters can also be passed in via Get Query or a mix of both versions

```html
{{tagname::arg1Value?arg2=arg2Value}}
```

