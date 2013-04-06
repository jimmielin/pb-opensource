<?php
/**
 * Project "Iris"
 * A powerful, extendable, in-house developed multi-forum engine for commercial use.
 *
 * @copyright    (c) 2012 Jimmie Lin <jimmie.lin@gmail.com>
 * @license      See LICENSE.
 * @since        Version 10200, 2012.8.20
 *
 * Iris/IO Helper.
 * Handles IO Operations (Input Filtering, Output Filtering), BBCode Parsing, Security Stuff, eh.
 * Pretty important guy, isn't him?
 *
 * Written by Jimmie Lin
 * Currently Playing: "There must be something I can craft, to ease the burden of this task!"
 */

App::uses("AppHelper", "View/Helper");

class IrisIOHelper extends AppHelper {
	function __construct() {
		require_once(dirname(__FILE__) . DS . "../../Vendor/decoda/Decoda.php");
		$this->pureconfig = HTMLPurifier_Config::createDefault();
		$this->pure = new HTMLPurifier($this->pureconfig);
	}

	/**
	 * Parses BBCode (previously Markdown) using Decoda, and filters the result with HTMLPurifier.
	 * HTML is always accepted, and purified.
	 * @since    10200
	 */
	function parse($text, $engine = 1) {
		$parsed = $text;
		// engine: 0 = markdown (currently absent on vacation after 10200), 1 = BBCode ("Decoda")
		if($engine == 1) {
			// go before decoda and fix the [url] tags proper
			$text = preg_replace('/\[url=(.+?)\](.+?)\[\/url\]/', '<a href="\1">\2</a>', $text);

			$textDecodaInstance = new Decoda($text);
			$textDecodaInstance->defaults();
			$parsed = $textDecodaInstance->parse();
		}

		$purified = $this->pure->purify($parsed);
		$purified = str_replace("<pre>", "<pre class='pre-scrollable'>", $purified);
		// youtube tag. you gotta go before htmlpurifier so it's safe.
		$result = preg_replace('@\[youtube\].*?(?:v=)?([^?&[]+)(&[^[]*)?\[/youtube\]@is', '<iframe class="youtube-player" type="text/html" width="640" height="385" src="http://youtube.com/embed/\\1" frameborder="0"></iframe>', $purified);
		// good.
		return $result;
	}

	/**
	 * Clean Input.
	 * Makes Input pretty DB safe (cleans out all the nasty stuff that appears).
	 */
	function cleanInput($text) {
		$result = $this->pure->purify($text);
		return $result;
	}

	/**
	 * Clean Editing Output.
	 * Makes Output textarea-safe (uses HTMLEntities)
	 */
	function cleanEditingOutput($text) {
		$result = htmlentities($text);
		return $result;
	}

	/**
	 * Sanitize Usernames
	 * Inspired by myBB's source code. Really. They throw an error, but we just remove it silently (handling errors is harder)
	 */
	function sanitizeUsername($text) {
		$result = $this->sanitizeTitle(substr($text, 0, 18));
		$result = str_replace("<", "", $result);
		$result = str_replace(">", "", $result);
		$result = str_replace("&", "", $result);
		$result = str_replace("\\", "", $result);
		$result = str_replace(";", "", $result);
		$result = str_replace("'", "", $result);
		$result = str_replace('"', "", $result);
		$result = str_replace(",", "", $result);

		return $result;
	}

	/**
	 * Sanitize Titles
	 * Used for topic titles, message thread titles, and other stuff that does not go through the parser.
	 * Is ran prior to DB Insert (making it DB Safe). For exit handling, use sanitizeTitleOut()
	 * @since    10200
	 */
	function sanitizeTitle($text) {
		$text = substr($text, 0, 100); // 100 aught to be enough
		$text = trim($text);

		$text = html_entity_decode($text);
		return $text;
	}

	/**
	 * Sanitize Titles (Out)
	 * Is ran on topic titles, message thread titles, and other stuff that doesn't go through the parser, on exit (output), after DB Insert
	 */
	function sanitizeTitleOut($text) {
		return htmlentities($text);
	}

}