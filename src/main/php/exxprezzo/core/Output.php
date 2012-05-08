<?php
namespace exxprezzo\core {
	interface Output extends Runnable {
		function renderContents();
		function getLength();
		function getLastModified();
		function getExpiryDate();
	}
}