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
    
    protected static $paths = array
    (
    	'getWebgroup' => array('/json/webgroup/{$id}')
    );
    
    public function fullPriceList()
    {
    	var_dump($this->skrol->getTotalPriceList());
    }
    
    public function getWebgroup($input)
    {
	    $output = new Content();
	    $children = $this->skrol->getWebgroupChildrenOf($input['id'])['children'];
	    foreach($children as $child)
	    {
	    	if( isset($child['contents']) )
	    	{
	    		$child['href'] = $this->mkurl('getWebgroup', array('id'=>$child['id']));
		    	$output->addLoop('webgroup', $child);
		    }
		    else
		    {
			    $product = $this->skrol->getArticle($child['id']);
			    $output->addLoop('product', $product);
		    }
	    }
	    return new BlockOutput($this, $output);
    }
}