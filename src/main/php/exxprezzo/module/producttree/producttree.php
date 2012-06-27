<?php namespace exxprezzo\module\producttree;

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
        $this->displayProductTree($this->products["tree"]);
    }

    public function displayProductTree($curProducts)
    {
        for($i = 0; $i < sizeof($curProducts); $i++)
        {
            if(isset($curProducts[$i]["contents"]))
            {
                echo "Category: " . $curProducts[$i]["name"] . "</label><br/>";
                $this->displayProductTree($curProducts[$i]["contents"]);
            }
            else
            {
                echo "Product " . $curProducts[$i]["id"] . ": " . $curProducts[$i]["name"] . "</label><br/>";
            }
        }
    }
}