<?php namespace exxprezzo\core\type;

class Size implements \JsonSerializable {
	
	protected $count;
	protected static $unit = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
	
	public function __construct($count) {
		$this->count = $count;
	}
	
	public function getBytes($omitUnit=false) {
		return $this->getNamedBytes(0, $omitUnit);
	}
	
	public function getKiloBytes($omitUnit=false) {
		return $this->getNamedBytes(1, $omitUnit);
	}
	
	public function getMegaBytes($omitUnit=false) {
		return $this->getNamedBytes(2, $omitUnit);
	}
	
	public function getGigaBytes($omitUnit=false) {
		return $this->getNamedBytes(3, $omitUnit);
	}
	
	public function getTeraBytes($omitUnit=false) {
		return $this->getNamedBytes(4, $omitUnit);
	}
	
	public function getPetaBytes($omitUnit=false) {
		return $this->getNamedBytes(5, $omitUnit);
	}
	
	public function getExaBytes($omitUnit=false) {
		return $this->getNamedBytes(6, $omitUnit);
	}
	
	public function getZettaBytes($omitUnit=false) {
		return $this->getNamedBytes(7, $omitUnit);
	}
	
	public function getYottaBytes($omitUnit=false) {
		return $this->getNamedBytes(8, $omitUnit);
	}
	
	public function getNamedBytes($step, $omitUnit=false, $round=2) {
		return round($this->count/pow(2, $step*10), $round) . ($omitUnit ? '' : self::$unit[$step]);
	}
	
	public function __toString() {
		for($i=0;$i<8;$i++) {
			if ($this->count < 0.75*pow(2, ($i+1)*10)) // 0.75*2^((i+1)*10)
				break;
		}
		return $this->getNamedBytes($i);
	}
	
	public function jsonSerialize() {
		return array('count' => $count);
	}
	
	public function __sleep() {
		return array('count');
	}
	
	public function __wakeup() {}
	
}
