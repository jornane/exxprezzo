<?php namespace exxprezzo\core\output;

use \exxprezzo\core\output\Output;

/**
 * A factory for creating output objects based on the runtime environment.
 * The primary goal is for switching the media type of the output based
 * on the Accept header, but future implementations may use different runtime
 * criteria to determine the type of output returned. 
 * 
 * @author wilfried
 *
 */
interface OutputFactory {
	
	/**
	 * Creates a new Output for the given source representing the given data.
	 * The actual representation is chosen by the Factory. This method may
	 * fail if the data is for some reason not something the factory can turn
	 * into a valid representation.
	 * 
	 * The client may present additional constraints by indicating a list of types
	 * the factory must choose from. This is also a preference list. The order is
	 * the preference order. 
	 * 
	 * TODO: Extend the following to allow different wild card types.
	 * Note that adding &lowast;/&lowast; to this list gives the factory a 
	 * possibility to choose the type freely. If no types are given the factory
	 * may choose any type based on the runtime environment.
	 * 
	 * @throws OutputGenerationFailedException
	 * @throws UnsupportedTypeException
	 * @param AbstractModule $source
	 * @param Content|Output $data
	 * @param string[] $types
	 */
	function newOutput($source, $data, $types = NULL);
	
}