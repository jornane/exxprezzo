<?php namespace exxprezzo\module\producttree;

class Skrol 
{
	private $baseUrl;
	private $apiKey;

	private static $fullPricelist="pricelist";
	private static $stock="stock";
	private static $search="search";
	private static $searchParam="query";
	private static $webgroupChildren="childrenOfWebgroup";
	private static $article="article";
	private static $webgroup="webgroup";
	private static $getMethodParam="getMethod";
	private static $getMethodName="name";
	private static $getMethodId="id";
	private static $idParam="id";
	
	const agent = 'SKROL/1.5 (PHP) SkrolWeb/SNAPSHOT';

	public function __construct($webserviceBaseUrl, $apiKey)
	{
		$this->baseUrl = rtrim($webserviceBaseUrl, '/').'/';
		$this->apiKey = $apiKey;
	}
	
	protected function get($resource, $data) 
	{
		$data['apiKey'] = $this->apiKey;
		$r = curl_init($this->baseUrl.$resource.'/');
		curl_setopt($r, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($r, CURLOPT_USERAGENT, static::agent);
		curl_setopt($r, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($r, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($r, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json; charset=utf-8',
				'Accept: application/json; charset=utf-8'
			));
		curl_setopt($r, CURLOPT_FAILONERROR, TRUE);
		
		$result = json_decode( curl_exec($r) , TRUE);
		//$result = curl_exec($r);
		if (is_null($result))
			return array(
				'error' => 'json',
				'jsonError' => json_last_error(),
			);
		return $result;
	}
	
	public function getCustomers($fields, $orderBy = NULL, $asc = true) 
	{
		return $this->get('cust', array(
			'fields' => $fields,
			'orderBy' => $orderBy,
			'desc' => !$asc,
		));
	}
	
	/**
	 * Get Total Pricelist as an array
	 *
	 * @return array complete SKROL pricelist
	 */
	public function getTotalPriceList() 
	{
		return $this->get('pricelist', array());
	}

	/**
	 * Get latest $amount articles
	 *
	 * @param integer $amount	Amount of articles to return
	 * @return array	Array of articles
	 */
	public function getLatestArticles( $amount )
	{
		return NULL;
	}

	/**
	 * Get list of all articles that are in stock; stock>0
	 *
	 * @return array	Array of articles
	 */
	public function getStockPriceList( )
	{
		return $this->get('stock', array());
	}

	/**
	 * Get webgroups from webgroup with id $webgroup
	 *
	 * @param integer $webgroup	Webgroup ID
	 * @return array	Array of webgroups
	 */
	public function getWebGroupChildrenOf( $webgroup )
	{
		return $this->get('childrenOfWebgroup', array(
			'getMethod' => 'id',
			'id' => $webgroup,
		));
	}
	
	/**
	 * Get article with id $id
	 *
	 * @param integer $id	ID of the article
	 * @return array	Array with keys name, stock and price
	 */
    public function getArticle( $id )
    {
    	return $this->get('article', array(
		'id' => $id,
		));
    }
	
	/**
	 * Returns the webgroup with id $id
	 *
	 * @param integer $id	ID of the webgroup
	 * @return array	Array with keys name, id, parent_name_long and parent_id
	 */
	public function getWebgroupById( $id )
	{
		return $this->get('webgroup', array(
			'getMethod' => 'id',
			'id' => $id,
		));
	}
	
	/**
	 * Search articles
	 * 
	 * @param string $searchme	Search terms, splitted by spaces
	 * @return array	List of webgroups, containing only the searched articles
	 */
	public function searchArticles( $searchme )
	{
		return $this->get('search', array(
			'query' => $searchme,
		));
	}
}