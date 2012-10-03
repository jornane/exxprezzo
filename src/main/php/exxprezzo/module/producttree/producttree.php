<?php namespace exxprezzo\module\producttree;

use \exxprezzo\core\Content;

use \exxprezzo\core\output\BlockOutput;

use \exxprezzo\core\module\AbstractModule;

class ProductTree extends AbstractModule
{
    /**
     * @return string
     */
    public function init()
    {
        parent::init();
        $this->skrol = new Skrol('https://skrol.iapc.utwente.nl/skrol-service-1.6-SNAPSHOT/', 'foo');
    }

    public function getTitle($params)
    {
        return $this->getName();
    }

    protected static $functions = array
    (
        '/' => 'fullPriceList',
        '/json/webgroup/(?<id>\d+)' => 'getWebgroup'
    );

    public function fullPriceList()
    {
    	var_dump($this->skrol->getTotalPriceList());
    }
    
    public function getWebgroup($input)
    {
	    $output = new Content();
	    var_dump($this->skrol->getWebgroupChildrenOf($input['id']));
    }

    /*
	public function displayProductTree($curProducts, $cat)
    {
        for($i = 0; $i < sizeof($curProducts); $i++)
        {
            $webgroup = new Content();
            $webgroup->putVariables($curProducts[$i]);
            $cat->addLoop('category', $webgroup);
        }
    }
*/
}