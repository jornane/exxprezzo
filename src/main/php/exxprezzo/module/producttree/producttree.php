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
        $this->webGroups = $this->skrol->getWebgroupChildrenOf(0);
    }

    public function getTitle($params)
    {
        return $this->getName();
    }

    protected static $functions = array(
        '/' => 'fullPriceList'
    );

    public function fullPriceList()
    {
        $tree = new Content();
        $tree->add('category', $this->webGroups[1]['id']);
        return new BlockOutput($this, $tree);
    }

    public function displayProductTree($curProducts, $cat)
    {
        for($i = 0; $i < sizeof($curProducts); $i++)
        {
            $webgroup = new Content();
            $webgroup->putVariables($curProducts[$i]);
            $cat->addLoop('category', $webgroup);
        }
    }
}