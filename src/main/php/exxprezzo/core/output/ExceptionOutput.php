<?php namespace exxprezzo\core\output;

use \exxprezzo\core\layout\Page;

use \exxprezzo\core\Content;
use \exxprezzo\core\Core;

class ExceptionOutput extends AbstractFormattableOutput implements PartialOutput {

	public function __construct($source, $exception) {
		// Get the title of the error
		if (get_class($exception) == 'ErrorException') {
			$title = 'PHP'; // Otherwise ErrorException would display as Error Error, now it displays as PHP Error
		} else if (get_class($exception) == 'Exception') {
			$title = 'General';
		} else {
			$title = '';
			$className = get_class($exception);
			$capital = true;
			for($i=strrpos($className, '\\')+1;$i<strlen($className)-9;$i++) {
				if (strtoupper($className{$i}) == $className{$i})
					$title .= ' ';
				$title .= $capital?$className{$i}:strtolower($className{$i});
				$capital = false;
			}
		}

		// Build a stacktrace
		$trace = "\n";
		$tracestart = 0;
		// Make sure the stacktrace only goes so far as where handle_error, oops or panic got called
		foreach($exception->getTrace() as $id => $t) {
			if (isset($t['function']) && in_array($t['function'], array(
					'handleError',
					'handleException',
					'__autoload',
					'trigger_error',
					'user_error',
					'loadClass',
				))) {
					$tracestart = $id;
				}
		}
		foreach($exception->getTrace() as $id => $t) {
			if ($id >= $tracestart && isset($t['file']))
				$trace .= '#'.($id-$tracestart).' '.substr($t['file'], strlen(getcwd())).'('.$t['line'].'): '
					.$t['function'].'('.implode(',',array_map('gettype',$t['args'])).")\n";
		}

		parent::__construct($source, new Content(array(
			'message' => $exception->getMessage(),
			'title' => $title,
			'stacktrace' => $trace,
			'exception' => $exception,
		)));

		$this->exception = $exception;
		$this->template = Page::getTemplate(
			$this,
			'exception',
			'default' // TODO get theme name from DB
		);
		$this->template->setContent($this->getContentObject());
	}

	public function getContent() {
		return $this->template->render();
	}

	public function getContentType() {
		return 'text/html';
	}

}
