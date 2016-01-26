<?php
/**
 * KePHP, Keep PHP easy!
 */

namespace Demo\Model\User;

use Ke\Adm\Model;

/**
 * Class User
 * tableName ke_user
 *
 * // class properties
 * @property int    $id         主键
 * @property string $email      邮箱
 * @property string $password   密码
 * @property string $salt       salt
 * @property string $name       名称
 * @property int    $created_at 创建时间
 * @property int    $updated_at 更新时间
 * // class properties
 */
class User extends Model
{

	protected static $cacheSource = null;

	protected static $pk = 'id';

	protected static $pkAutoInc = true;

	protected static $columns = [
		'email'    => ['require' => true],
		'password' => ['hidden' => true,],
		'salt'     => ['hidden' => true],
	];

	public static function dbColumns()
	{
		// database columns
		// generated as 2016-01-26 15:31:34
		return [
			'id'         => ['label' => '主键', 'int' => 1, 'pk' => 1],
			'email'      => ['label' => '邮箱', 'max' => 64],
			'password'   => ['label' => '密码', 'max' => 32],
			'salt'       => ['label' => 'salt', 'max' => 32],
			'name'       => ['label' => '名称', 'max' => 16],
			'created_at' => ['label' => '创建时间', 'timestamp' => 1, self::ON_CREATE => 'now'],
			'updated_at' => ['label' => '更新时间', 'timestamp' => 1, self::ON_UPDATE => 'now'],
		];
		// database columns
	}

}
