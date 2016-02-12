<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/2/12 0012
 * Time: 15:22
 */

namespace Ke\Utils\DocMen;


class CommentDocParser
{

	public $comment = '';

	public $fields = [];

	protected $allowMultiFields = [
		'param'    => true,
		'property' => true,
		'link'     => true,
	];

	public function __construct(string $comment)
	{
		$this->comment = $this->purgeNoise($comment);
		$this->takeFields();
	}

	public function purgeNoise(string $comment)
	{
		$comment = trim($comment, '/* ');
		$comment = trim($comment);
		return preg_replace('#^(\*{1,}\s{0,1}|[\t\s]+\s{0,1}\*\s{0,1})#mi', '', $comment);
	}

	public function takeFields()
	{
//		echo '<hr>';
//		echo '<pre>';
//		echo htmlentities($this->comment);
//		echo '</pre>';
		$fields = &$this->fields;
		$regex = '#^\@([^\s]+)(?:[\s\t]+([^\s]+)(.*([\r\n]+(?!^\@)\s*.*)*))?#mi';
		$this->comment = preg_replace_callback($regex, function ($matches) use (&$fields) {
			$this->filterField($matches[1], $matches);
			return '';
		}, $this->comment);
		$this->comment = trim($this->comment);
//		echo 'after:<pre>';
//		echo htmlentities($this->comment);
//		echo '</pre>';
//		var_dump($this->fields);
		return $this;
	}

	public function filterField(string $type, array $matches)
	{
		foreach ($matches as $index => $match) {
			$matches[$index] = trim($matches[$index]);
		}
		$data = [$matches[2]];
		if (!empty($matches[3])) {
			$regex = '#^([^\s]+)[\t\s]+(.*(?:[\r\n].*)*)#mi';
			if (preg_match($regex, $matches[3], $match)) {
				$data[] = $match[1];
				$match[2] = preg_replace('#^([\t\s]+)#mi', '', $match[2]);
//				$match[2] = preg_replace('#([\r\n])#mi', '', $match[2]);
				$data[] = $match[2];
			}
			else {
				$data[] = $matches[3];
				$data[] = null;
			}
		}
		else {
			$data[] = null;
			$data[] = null;
		}
		$this->addField($type, $data);
		return $matches;
	}

	public function addField(string $type, array $data)
	{
		if (isset($this->allowMultiFields[$type])) {
			$this->fields[$type][] = $data;
		}
		else {
			$this->fields[$type] = $data;
		}
		return $this;
	}

}