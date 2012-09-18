<?php namespace exxprezzo\module\news;

use \exxprezzo\core\db\SQL;
use \exxprezzo\core\Content;
use \exxprezzo\core\output\BlockOutput;
use \exxprezzo\core\module\AbstractModule;

class News extends AbstractModule
{
	/** @var SQL */
	protected $db;
	
	public function init()
	{
		parent::init();
		$this->db = $this->getModuleParam();
	}
	
	public function getTitle($params)
	{
		return $this->getName();
	}
	
	protected static $functions = array(
			'/' => 'news'
	);
	
	public function news()
	{
		$sql = $this->db->query('SELECT * FROM `news` LIMIT 5 ');
		$news = new Content();
		
		for($i = 0; $i < 2; $i++)
		{
			$newsItem = new Content();
			$newsItem->putVariables($sql[$i]);
			$news->addLoop('newsItem', $newsItem);
		}
		
		return new BlockOutput($this, $news);
	}
}
