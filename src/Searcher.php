<?php namespace Fuse;

interface Searcher {
	/**
	 * Create a new Searcher
	 * @param {string} $pattern A search query or token to look for
	 * @param {array}  $options Receives the options of the Fuse instance
	 */
	public function __construct ($pattern, $options);

	/**
	 * @return {string} The search query or token
	 */
	public function getPattern();

	/**
	 * Search the given text with the instance's search pattern
	 * @param  {string} $text The text to search
	 * @return {array}        An associative array with a boolean "isMatch" and an integer "score" entry
	 */
	public function search($text);
}