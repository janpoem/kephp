<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Cli\Cmd;

class UpdateModel extends NewModel
{

	protected static $commandName = 'updateModel';

	protected static $commandDescription = '';

	protected $tip = 'Updating model';

	public function buildModel(string $table, string $class, string $path)
	{
		$forge = $this->adapter->getForge();
		$vars = $forge->buildTableProps($table);

		if (!is_file($path))
			return ['Fail', PHP_EOL, "File {$path} does not exist!"];

		$content = file_get_contents($path);
		$split = preg_split('#\s{1}\*\s{1}\/\/\s{1}class\s{1}properties#i', $content);
		if (count($split) >= 3) {
			$split[1] = $vars['props'];
		}
		$content = implode('', $split);

		$split = preg_split('#[\t\s]+\/\/\s{1}database\s{1}columns#', $content);
		if (count($split) >= 3) {
			$split[1] = PHP_EOL . $vars['columns'];
		}
		$content = implode('', $split);

		$content = preg_replace_callback('#[\t\s]+protected[\t\s]+static[\t\s]+\$pk[\t\s]+\=[\t\s]+([^\t\s]+)\;#i', function($matches) use ($vars) {
			return str_replace($matches[1], $vars['pk'], $matches[0]);
		}, $content);

		$content = preg_replace_callback('#[\t\s]+protected[\t\s]+static[\t\s]+\$pkAutoInc[\t\s]+\=[\t\s]+([^\t\s]+)\;#i', function($matches) use ($vars) {
			return str_replace($matches[1], $vars['pkAutoInc'], $matches[0]);
		}, $content);

		if (file_put_contents($path, $content)) {
			return ['Success'];
		}
		else {
			return ['Fail', 'I/O error, please try again!'];
		}
	}
}