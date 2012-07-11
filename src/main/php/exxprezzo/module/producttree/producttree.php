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
        $this->products = $this->skrol->getTotalPriceList();
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
        $this->displayProductTree($this->products["tree"], $tree);
        return new BlockOutput($this, $tree);
    }

    public function displayProductTree($curProducts, $cat)
    {
        for($i = 0; $i < sizeof($curProducts); $i++)
        {
            if(isset($curProducts[$i]["contents"]))
            {
                $category = new Content();
                $category->putVariables($curProducts[$i]);
                $cat->addLoop('category', $category);
                $this->displayProductTree($curProducts[$i]["contents"], $category);
            }
            else
            {
                $product = new Content();
                $product->putVariables($curProducts[$i]);
                $cat->addLoop('category', $product);
            }
        }
    }
}