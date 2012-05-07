<?php
namespace Nexxos\Core\Module {
	abstract class AbstractModule implements \Nexxos\Core\Runnable {
		protected $isMain;
		protected $urlManager;
		
		public static function getInstanceFor($hostGroup, $path) {
			return new DefaultModule;
		}
		
		public function setMain($isMain) {
			$this->isMain = $isMain;
		}
		public function isMain() {
			return $this->isMain;
		}
		
		public function setUrlManager($urlManager) {
			$this->urlManager = $urlManager;
		}
		public function getUrlManager() {
			return $this->urlManager;
		}
	}
}