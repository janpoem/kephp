<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/9 0009
 * Time: 13:45
 */


$args = new \Ke\Cli\Args([
	'hello_world',
	'-d', KE_APP_ROOT,
	'--class=Hello',
]);

function initGuide(array $guide, array $args)
{
	$fields = [];
	$helpers = [];
	$params = [];
	$used = [];
	$allowTypes = array_flip([
		KE_NULL, KE_BOOL, KE_INT, KE_FLOAT, KE_STR, /*KE_ARY,*/
		'dir', 'file', 'json', /* 'dirs', 'files' */
		'any', 'concat',
	]);
	if (!isset($guide['command']))
		$guide = array_merge(['command' => ['field' => 0]], $guide);
	foreach ($guide as $name => $options) {
		$name = trim((string)$name, '-');
		$type = isset($options['type']) && isset($allowTypes[$options['type']]) ? $options['type'] : KE_STR;
		$default = isset($options['default']) ? (string)$options['default'] : null;
		$field = [
			'type' => '',
			'default' => '',
			'field' => '',
			'shortcut' => false,
		];
		if (!empty($options['single'])) {
			$field['type'] = KE_BOOL;
			$field['default'] = $default;
		} elseif (!empty($options['concat'])) {
			$field['type'] = 'concat';
			$field['spr'] = $options['concat'];
		} else {
			$field['type'] = $type;
			$field['default'] = $default;
		}
		$hasField = false;
		if (isset($options['field'])) {
			if (is_numeric($options['field']) && $options['field'] >= 0) {
				$options['field'] = (int)$options['field'];
				$hasField = true;
			} elseif (!empty($options['field']) && is_string($options['field'])) {
				$options['field'] = trim($options['field'], '-');
				if (!empty($options['field']))
					$hasField = true;
			}
		}
		if (!$hasField) {
			$f = preg_replace_callback('#([A-Z])#', function ($matches) {
				return '-' . strtolower($matches[1]);
			}, $name);
			$options['field'] = $f;
		}
		$field['field'] = $options['field'];
		if (!empty($options['shortcut']) && is_string($options['shortcut'])) {
			$options['shortcut'] = trim(strtolower($options['shortcut']), '-');
		}
		if (empty($options['shortcut']) || !is_string($options['shortcut'])) {
			$options['shortcut'] = false;
		}
		$field['shortcut'] = $options['shortcut'];
		$fields[$name] = $field;
//		if (isset($options['index']) && is_numeric($options['index']) && $options['index'] >= 0) {
//			$fields[$name] = [
//				'field'   => (int)$options['index'],
//				'type'    => $type,
//				'default' => $default,
//			];
//		} else {
//
//		}
		$fields[$name]['default'] = verifyType($fields[$name]['default'], $field['type'], $field);
		//////////////////////////////////////////////////////////////
		// pick the arg
		//////////////////////////////////////////////////////////////
		$field = $fields[$name];
		$params[$name] = $field['default'];
		$hasValue = true;
		printf('%s, %s <br />', $name, $field['field']);
		if (isset($field['field']) && isset($args[$field['field']])) {
			$params[$name] = $args[$field['field']];
			$used[$field['field']] = 1;
		} elseif (isset($field['shortcut']) && isset($args[$field['shortcut']])) {
			$params[$name] = $args[$field['shortcut']];
			$used[$field['shortcut']] = 1;
		} else {
			$hasValue = false;
		}
		if ($hasValue) {
			$params[$name] = verifyType($params[$name], $field['type'], $field);
		}
	}
	$diff = array_diff_key($args, $used);
	foreach ($diff as $field => $value) {
		if (is_string($field))
			$field = trim($field, '-');
		if (!isset($params[$field]))
			$params[$field] = verifyType($value, 'any');
	}
	var_dump($params);
}

function verifyType($value, $type, array $field = null)
{
	if ($type === KE_STR) {
		return (string)$value;
	} elseif ($type === KE_BOOL) {
		if ($value === 'false' || $value === '0' || $value === 0 || $value === 0.00)
			return true;
		return (bool)$value;
	} elseif ($type === KE_INT)
		return (int)$value;
	elseif ($type === KE_FLOAT)
		return (float)$value;
	elseif ($type === KE_ARY)
		return (array)$value;
	elseif ($type === 'dir') {
		if (empty($value))
			return false;
		return is_dir($value) ? realpath($value) : false;
	} elseif ($type === 'file') {
		if (empty($value))
			return false;
		return is_file($value) && is_readable($value) ? realpath($value) : false;
	} elseif ($type === 'dirs') {
		if (empty($value))
			return [];
		if (!is_array($value))
			$value = [$value];
		foreach ($value as & $item) {
			$item = verifyType($item, 'dir');
		}
		return $value;
	} elseif ($type === 'files') {
		if (empty($value))
			return [];
		if (!is_array($value))
			$value = [$value];
		foreach ($value as & $item) {
			$item = verifyType($item, 'file');
		}
		return $value;
	} elseif ($type === 'json') {
		$decode = json_decode($value, true);
		return $decode;
	} elseif ($type === 'concat' && !empty($field['spr']) && is_string($field['spr'])) {
		if (empty($value))
			return [];
		return explode($field['spr'], $value);
	} else {
		if ($value === 'false')
			return false;
		if ($value === 'true')
			return true;
		if ($value === 'null')
			return null;
		if (is_float($value))
			return (float)$value;
		if (is_int($value))
			return (int)$value;
		return $value;
	}
}

function filterArgs(array $args)
{
	$newArgs = [];
	$inPair = false;
	foreach ($args as $index => $item) {
		if (preg_match('#^(\-+)([^\=]+)(?:\=(.*))?#', $item, $matches)) {
			if ($inPair !== false) {
				$inPair = false;
			}
			if ($inPair === false) {
//				$prefix = $matches[1];
//				if (strlen($prefix) > 2)
//					$prefix = '--'; // limit -- should in 2 chars
				$prefix = $matches[2];
				if (isset($matches[3]))
					$newArgs[$prefix] = $matches[3];
				else {
					$newArgs[$prefix] = '';
					$inPair = $prefix;
				}
			}
			continue;
		}
		if ($inPair !== false) {
			$newArgs[$inPair] = $item;
			$inPair = false;
			continue;
		}
		$newArgs[] = $item;
	}
	return $newArgs;
}

$args = filterArgs([
	'hello_world',
	'good',
	'-f', './cli.php',
	'--class=Hello',
	'--is-ok', 'false',
	'--data', '[1,"a",bb,4]',
]);

var_dump($args);

initGuide([
	'model'   => [
		'field'  => 1,
		'concat' => ',',
	],
	'file'    => [
		'shortcut' => 'f',
		'type'     => 'file',
	],
	'class'   => [
		'shortcut' => 'c',
	],
	'is-test' => [
		'single' => 1,
	],
	'data'    => [
		'type' => 'json',
	],
], $args);
