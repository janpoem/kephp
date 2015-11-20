<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/19 0019
 * Time: 4:10
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

	protected static $stdMessages = [
		self::UNDEFINED_ADAPTER   => 'Source "{0}"({1}): Undefined the adapter field.',
		self::UNKNOWN_ADAPTER     => 'Source "{0}"({1}): Unknown adapter {2}.',
		self::INVALID_READ_CONFIG => 'Source "{0}#{2}"({1}): Invalid read server config.',
		self::CONNECT_ERROR       => 'Source "{0}#{2}"({1}): Source connect error! {3}',
		self::INVALID_CONDITIONS  => 'Source "{0}"({1}): Invalid find conditions.',
		self::UNDEFINED_SOURCE    => 'Source "{0}"({1}): Undefined source "{0}".',
	];

	public function __construct($msg, array $context = [], \Exception $previous = null)
	{
		$this->context = $context;
		if (isset(self::$stdMessages[$msg]))
			$msg = self::$stdMessages[$msg];
		parent::__construct(substitute($msg, $this->context), 0, $previous);
	}
}