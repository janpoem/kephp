<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm;

class Exception extends \Exception
{

	const UNDEFINED_ADAPTER = 500;

	const UNKNOWN_ADAPTER = 501;

	const INVALID_READ_CONFIG = 502;

	const CONNECT_ERROR = 503;

	const INVALID_CONDITIONS = 504;

	const UNDEFINED_SOURCE = 505;

	const NOT_ENABLE_CACHE = 601;

	const INVALID_MODEL = 701;

	protected static $stdMessages = [
		self::UNDEFINED_ADAPTER   => 'Source "{0}"({1}): Undefined the adapter field.',
		self::UNKNOWN_ADAPTER     => 'Source "{0}"({1}): Unknown adapter {2}.',
		self::INVALID_READ_CONFIG => 'Source "{0}#{2}"({1}): Invalid read server config.',
		self::CONNECT_ERROR       => 'Source "{0}#{2}"({1}): Source connect error! {3}',
		self::INVALID_CONDITIONS  => 'Source "{0}"({1}): Invalid find conditions.',
		self::UNDEFINED_SOURCE    => 'Source "{0}"({1}): Undefined source "{0}".',
		self::NOT_ENABLE_CACHE    => 'Model "{0}": Not enable cache store with this class.',
		self::INVALID_MODEL       => 'Invalid model "{0}".',
	];

	public function __construct($msg, $context = null, \Exception $previous = null)
	{
		$this->context = (array)$context;
		if (isset(self::$stdMessages[$msg]))
			$msg = self::$stdMessages[$msg];
		parent::__construct(substitute($msg, $this->context), 0, $previous);
	}
}