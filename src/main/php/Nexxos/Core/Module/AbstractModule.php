<?php
namespace Nexxos\Core\Module {
	abstract class AbstractModule implements \Nexxos\Core\Runnable {
		protected $isMain;
		private $urlManager;
		
		public static function getInstanceFor($hostGroup, $path) {
			return new DefaultModule;
		}
		
		public function setMain($isMain) {
			$this->isMain = $isMain;
		}
		public function isMain() {
			return $this->isMain;
		}
		
		public final function setUrlManager($urlManager) {
			if ($urlManager instanceof \Nexxos\Core\URL\AbstractUrlManager)
				$this->urlManager = $urlManager;
			else
				user_error('urlManager is not of kind AbstractUrlManager');
		}
		public final function getUrlManager() {
			return $this->urlManager;
		}
		
		public function getFunctionPath() {
			
		}
	}
}