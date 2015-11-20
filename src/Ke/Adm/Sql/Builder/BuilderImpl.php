<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/20 0020
 * Time: 3:52
 */

namespace Ke\Adm\Sql\Builder;

interface BuilderImpl
{

	const MARRY_OR = ' OR ';

	const MARRY_AND = ' AND ';

	const WHERE_IN = 1;

	const WHERE_NOT_IN = 0;

	public static function mkLimitOffset($limit = 0, $offset = 0);

	public static function mkOrder($value);

	public static function mkGroup($value);

	public static function mkSelectByArray(array & $cd, & $sql, array & $params = null);


}