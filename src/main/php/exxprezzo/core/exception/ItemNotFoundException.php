<?php namespace exxprezzo\core\exception;

class ItemNotFoundException extends ExxprezzoException {
	protected $item = NULL;
	protected $itemName = NULL;
	
	public function __construct($item, $itemName, $code = 404, Exception $previous = null) {
		$this->item = $item;
		$this->itemName = $itemName;
		parent::__construct('Could not find '.$itemName.' "'.$item.'"', $code, $previous);
	}

	public function getItem() {
		return $this->item;
	}

	public function getItemName() {
		return $this->itemName;
	}
}
