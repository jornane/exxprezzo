<?php
namespace Nexxos\Core {
	interface Output extends Runnable {
		function renderContents();
		function getLength();
		function getLastModified();
		function getExpiryDate();
	}
}