<?php namespace exxprezzo\module\producttree;

use \exxprezzo\core\Content;

use \exxprezzo\core\output\BlockOutput;

use \exxprezzo\core\module\AbstractModule;

use \exxprezzo\core\output\JSONOutput;

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
    	$output = new Content();
	    $webgroup = $this->skrol->getWebgroupChildrenOf(0);
	    $output->putVariable('webgroup', $webgroup);
	    
	    $children = $webgroup['contents'];
	    
	    foreach($children as $child)
	    {
	    	if( isset($child['contents']) )
	    	{
	    		$child['href'] = $this->mkurl('getWebgroup', array('id'=>$child['id']));
		    	$output->addLoop('contents', $child);
		    }
	    }
	    
	    return new BlockOutput($this, $output);
    }
    
    public function getWebgroup($input)
    {
	    $output = new Content();
	    $webgroup = $this->skrol->getWebgroupChildrenOf($input['id']);
	    $children = $webgroup['contents'];
	    $output->putVariable('webgroup', $webgroup);
	    return new JSONOutput($this, $output);
    }
}