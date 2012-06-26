<?php namespace exxprezzo\core\output;

use \exxprezzo\core\output\OutputFactory;
use \exxprezzo\core\output\Output;
use \exxprezzo\core\Content;

/**
 * An OutputFactory that chooses an appropriate output media type
 * based on the Access header field as provided by the client. If
 * the provided $types in newOutput does not contain the requested 
 * type the first possible (available from this factory) preferred 
 * type will be used. If no types are given this factory will generate
 * BlockOutput (media type text/html as from Page) for Content or the 
 * type given in Access if providable. This factory does not touch 
 * $data if it is already an Output instance
 * 
 * TODO: Implement wildcard media type detection {@link OutputFactory#newOutput}
 * @author wilfried
 *
 */
class AcceptOutputFactory implements OutputFactory {
	
	private static $knownTypes = array('text/html', 'application/json');
	
	function newOutput($source, $data, $types = NULL) {
		if($data instanceof Output)
			return $data;
		$mediaType = self::$knownTypes[0];
		if(isset($_SERVER['HTTP_ACCEPT'])) {
			$acceptValue = $_SERVER['HTTP_ACCEPT'];
		}
		if(!isset($acceptValue)){
			$headers = getallheaders();
			$headers = array_change_key_case($headers);
			if(isset($headers['accept']))
				$acceptValue = $headers['accept'];
		}
		if(isset($acceptValue)) {
			if(strpos($acceptValue, ';'))
				$requestTypes = substr($acceptValue, 0, strpos($acceptValue, ';'));
			else
				$requestTypes = $acceptValue;
			$requestTypes = explode(',', $requestTypes);
			$intersectKnown = array_values(array_intersect(self::$knownTypes, $requestTypes));
			if(isset($types) && $types)
				// XXX I think the wild card detection should be placed instead of this
				// intersect. */*, for example, would then cause all the known types to be 
				// included. However the order of this must vary based on the preference 
				// order in types.
				$intersect = array_intersect($types, $intersectKnown);
			if(isset($intersect) && $intersect) {
				$mediaType = $intersect[0];
			} else if($intersectKnown) {
				$mediaType = $intersectKnown[0];
			}
		}
		if($data instanceof Content){
			if($mediaType === 'application/json') {
				return new JSONOutput($source, $data);
			} else if($mediaType === 'text/html') {
				return new BlockOutput($source, $data);
			}
		}
		return NULL;
	}
	
}
