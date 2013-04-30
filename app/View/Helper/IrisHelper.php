<?php
/**
 * Project "Iris"
 * A powerful, extendable, in-house developed multi-forum engine for commercial use.
 *
 * @copyright    (c) 2012 Jimmie Lin <jimmie.lin@gmail.com>
 * @license      See LICENSE.
 * @since        Version 10000. 2012.7.11
 *
 * Iris Helper.
 * Handles most of the heavy URL lifting work, and circumvents the URL router that CakePHP has (So we don't use Router::url, which is really slow)
 * This breaks CakePHP reverse routing, I know - sorry!
 *
 * How to use.
 * Just use it, remember siteURL, forumURL takes the $segment as first argument, don't use a starting slash, and pass $forum as second arg for forumURL.
 * Starting from 10200, the second argument can be omitted. However, you can always override with the second arg. (Seamless transition)
 * Written by Jimmie Lin
 * Currently Playing: Linkin Park - Castle Of Glass (I love this one)
 */

App::uses("AppHelper", "View/Helper");

class IrisHelper extends AppHelper {
	/**
	 * Constructor...
	 */
	function __construct() {
		$this->domain = Configure::read("Iris.domain");
		$this->urlMode = Configure::read("Iris.urlMode");
			// 0=subdomain (myforum.example.com/forums/2), 1=debug (example.com/myforum/forums/2)
		$this->iconMode = Configure::read("Iris.iconMode");
			// 1=debug (img/icon/icon.png), 0=production (specify prefix within iconURL function)

		if($this->domain == "localhost") {
			if($this->urlMode != 1) die("<strong>Iris Error:</strong> Don't use Iris.urlMode=0 (subdomain) when on localhost, you will kill yourself.");
		}
	}

	/**
	 * Tell Iris what forum we are in.
	 * @since    10200
	 */
	function setForum($forum) {
		if(!defined("FORUM")) define("FORUM", $forum);
	}

	/**
	 * Generate Site URL.
	 * Takes a URL segment, and generates a site URL (full) with it.
	 */
	function siteURL($segment) {
		// routineHook @ 10000 @ implementation omitted pb-opensource
	}

	/**
	 * Generate Forum URL.
	 * Takes a URL segment, slaps a subdomain or sub-folder into it and generates a URL (full) with it.
	 */
	function forumURL($segment, $forum = -1) {
		// routineHook @ 10000 @ implementation omitted pb-opensource
	}

	/**
	 * Generate Icon URL.
	 * Give me a icon *name*, and I'll slap a .png and prefix URL into it. See Iris.iconMode, same format as the $urlMode
	 */
	function iconURL($iconName) {
		// routineHook @ 10000 @ implementation omitted pb-opensource
	}

	/**
	 * Generates an absolute URL given something relative, including a fix for double-slash (though its only a hack. FIXME.)
	 * Part of code refactoring and a way to workaround the $this->redirect only accepting relative URLs *to CakePHP* and not to root dir
	 *
	 * This is *NOT* forum-aware. It will always return forum names in directories, regardless of setting.
	 * #Won't Fix
	 * @since    10024
	 */
	function absoluteURL($path) {
		if($path[0] == "/") {
			$path[0] = " ";
			$path = trim($path); // ugly, though clever hack ;)
		}

		// routineHook @ 10000 @ implementation omitted pb-opensource
	}

	/**
	 * Generates Paginator Numbers.
	 * This generates pretty much everything that a view needs when paginating things. Just pass me the Paginator Array
	 * and the base-url (such as sections/view/8) and forum-name (e.g. support) and I'll handle the rest with pretty Twitter Boostrap
	 * @since    10023
	 */
	function paginatorHTML($paginatorArray, $baseUrl, $forumName = -1) {
		// usage: $this->Iris->paginatorHTML($this->Paginator->params(), "sections/view/1")
		// if you want to use it on site, just pass "global" as the third parameter!
		if($forumName == -1) {
			$forumName = FORUM;
		}

		$output = "";

		if($paginatorArray["pageCount"] < 2)
			return $output;

		$output = "<div class='btn-toolbar'><div class='btn-group'>";

		// generate first link.
		if($paginatorArray["page"] != 1) {
			$output .= "<a class='btn btn-primary' href='" . $this->forumURL($baseUrl . "/page:" . ($paginatorArray["page"] - 1), $forumName) ."'>&lt; " . __("Previous") . "</a>";
		}
		else {
			$output .= "<a class='btn btn-primary disabled'>&lt; " . __("Previous") . "</a>";
		}

		if($paginatorArray["pageCount"] < 12) {
			for($i=1; $i != $paginatorArray["pageCount"] + 1; $i++) {
				$extra = "";
				if($i == $paginatorArray["page"]) $extra = "btn-primary";
				$output .= "<a class='btn $extra' href='" . $this->forumURL($baseUrl . "/page:" . $i, $forumName) . "'>" . $i . "</a>";
			}
		}
		else {
			// output five links before and after current page
			$start = (($paginatorArray["page"] - 5) >= 1 ? $paginatorArray["page"] - 5 : 1);
			$end = (($paginatorArray["page"] + 5) <= $paginatorArray["pageCount"] ? $paginatorArray["page"] + 5 : $paginatorArray["pageCount"]);

			if($end - $start < 8) {
				// bias this a little bit.
				if($start == 1) $end = (($end + 4) <= $paginatorArray["pageCount"] ? $end + 4 : $paginatorArray["pageCount"]);
				if($end == $paginatorArray["pageCount"]) $start = (($start - 4) >= 1 ? $start - 4 : 1);
			}

			if($start != 1) {
				$output .= "<a class='btn' href='" . $this->forumURL($baseUrl . "/page:1", $forumName) . "'>1</a>";
				$output .= "<a class='btn disabled'>...</a>";
			}

			for($i=$start;$i!=$end;$i++) {
				$extra = "";
				if($i == $paginatorArray["page"]) $extra = "btn-primary";
				$output .= "<a class='btn $extra' href='" . $this->forumURL($baseUrl . "/page:" . $i, $forumName) . "'>" . $i . "</a>";
			}

			if($end != $paginatorArray["pageCount"]) {
				$output .= "<a class='btn disabled'>...</a>";
				$output .= "<a class='btn' href='" . $this->forumURL($baseUrl . "/page:" . $paginatorArray["pageCount"], $forumName) . "'>" . $paginatorArray["pageCount"] . "</a>";
			}
		}

		// generate last link.
		if($paginatorArray["page"] != $paginatorArray["pageCount"]) {
			$output .= "<a class='btn btn-primary' href='" . $this->forumURL($baseUrl . "/page:" . ($paginatorArray["page"] + 1), $forumName) ."'>" . __("Next") . " &gt;</a>";
		}
		else {
			$output .= "<a class='btn btn-primary disabled'>" . __("Next") . " &gt;</a>";
		}

		$output .= "</div></div>";

		return $output;
	}

	/**
	 * Gravatar Support.
	 * Implements a Gravatar-URL with a given Email address.
	 * PG (included) since 10220.
	 * @since    10023
	 * See implementation on https://en.gravatar.com/site/implement/images/php/; only coding style was changed slightly
	 */
	public function gravatarURL($email, $size = 80) {
		return "http://0.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?s=" . $size . "&d=identicon&r=pg";
	}


	/**
	 * Permission Verification Function
	 * Pass a permission *serialized* array, user-GID, and permission key, and we'll give you a true or false.
	 * @since    10022 refactor
	 */
	function verifyPermissions($permissionArray, $gid, $permissionKey) {
		// guest override code.
		if($gid == -1 and ($permissionKey != "view" and $permissionKey != "read")) return false;

		$permissionData = unserialize($permissionArray);
		if(isset($permissionData["allow"][$permissionKey]) and $currentSetArray = $permissionData["allow"][$permissionKey]) {
			// allow-based code.
			// if you're not allowed, you're not in the array, so we will return false.
			return in_array($gid, $currentSetArray);
		}

		if(isset($permissionData["deny"][$permissionKey]) and $currentSetArray = $permissionData["deny"][$permissionKey]) {
			// deny-based code.
			// if you are not allowed, you are in the array (true), so we will return false. (inverse op)
			return !in_array($gid, $currentSetArray);
		}

		return true;
	}
}
