<?php
/**
 * Project "Iris"
 * A powerful, extendable, in-house developed multi-forum engine for commercial use.
 *
 * @copyright    (c) 2012 Jimmie Lin <jimmie.lin@gmail.com>
 * @license      PandaBoards Shared-Source Initiative (MIT License), internal routines are proprietary.
 * @since        Version 10000. 2012.7.11
 *
 * Application level controller. App-wide controller-related methods are here.
 * 
 * Written by Jimmie Lin
 * Currently Playing: Linkin Park - Burn It Down
 */

error_reporting(E_ALL);

App::uses('Controller', 'Controller');
Configure::load("iris");
CakePlugin::load('EasyJoin');
CakePlugin::load("Autocache");

/**
 * Load HTML-purifier
 * @since    10106
 */
require_once("../Vendor/htmlpurifier/HTMLPurifier.standalone.php");

class AppController extends Controller {
	public $uses = array("User", "Forum", "Incident");
	public $helpers = array("Form", "Html", "Iris", "IrisIO", "Markdown");

	function beforeFilter() {
		// routineHook @ 10230 @ disable-PB-system-wide

		/**
		 * Dirty Hack for Using Iris Helper Under a Controller
		 * Thanks go to http://cakebaker.42dh.com/2007/08/09/how-to-use-a-helper-in-a-controller/
		 * @since    10018
		 */
		$view = new View($this);
		$this->Iris = $view->loadHelper("Iris");
		$this->IrisIO = $view->loadHelper("IrisIO");

		// routineHook @ 10001,10210 @ controls forum/app mode
		// $requestForum (internal) is forum name accessed, $this->forumData (public) is information about active forum
		// $this->workingMode is "forum". $this->forumID (public) is forum ID

		// routineHook @ 10240 @ $this->isSystemDisabledFully check for global shutdown

		// routineHook @ 10006,10009 @ custom authentication code
		// $this->loggedIn (public bool) is logged-in state
		// $this->uid (public) is user ID, $this->userData (public) is information about active user
		switch($this->workingMode) {
			case "site":
				// routineHook @ 10004 @ site working code
			break;

			case "forum":
				// $this->name (public) is Controller name, $this->action (public) is Action name
				// routineHook @ 10010 @ handle guest login

				// routineHook @ 10030 @ handle security
				
				if($this->loggedIn) {
					if(/* user exists */ true) {
						// routineHook @ 10010 @ check user exists
					}
					else {
						// routineHook @ 10220 @ gid, uid
						// $this->isSMod (public bool), $this->isAdmin (public bool)

						// routineHook @ 10220 @ check banning
					}
				}
				else {
					$this->isAdmin = false;
					$this->isSMod = false;
				}

				// routineHook @ 10100.6 @ handle board suspension (global control)

				// routineHook @ 10100.6 @ handle board on/off toggle (admin control)

				// routineHook @ 10022,10023 @ rebuild board counters

				/**
				 * Private Messaging System.
				 * Get the User's New Messages ($newMsg and $newMsgCount) for use in the view.
				 * @since    10100 Rev. 6
				 */
				$newMsg = $this->User->getNewMessages($this->userData["User"]["id"]);
				$newMsgCount = count($newMsg["AsHost"]) + count($newMsg["AsGuest"]);
				$this->set("newMsgCount", $newMsgCount);
				$this->set("newMsg", $newMsg);

				// routineHook @ 10102 @ flood control
			break;
		}

		/**
		 * CSRF Verification
		 * Port from .Grid
		 *
		 * @since    10024
		 */
		
		// routineHook @ 10024 @ generate $userUniqueSecret
		$userUniqueSecret = "removed-shared-source-variant";
		$this->csrfToken = sha1(microtime() . "removed-shared-source-variant_uniqueSecret" . $userUniqueSecret . "9999");

		$this->set("csrfToken", $this->csrfToken);

		/**
		 * Fixes some CSRF-token Reloading Issues we have when handling 404s.
		 * @since    10101 Rev. 2
		 */
		if(strtolower($this->name) == "pages") {
			$this->_delayCSRFRegeneration();
		}

		/**
		 * Previously AppController has defined App-Wide Variables. Now we moved them to /app/Config/iris.php!
		 * Never define configuration data within AppController. Period.
		 * @since    Version 10000
		 * Removed below on 10006. Below code only handles App-Wide Vars for View, but takes them from configure anyway.
		 * Added IRISAuth data on 10009, group/permission related data on 10021
		 */
		$this->branding = Configure::read("Iris.branding");
		$this->set("branding", $this->branding);
		$this->set("humanVersion", Configure::read("Iris.humanVersion"));
		$this->set("version", Configure::read("Iris.version"));
		$this->set("rev", Configure::read("Iris.revision"));
		$this->set("workingMode", $this->workingMode);

		$this->set("loggedIn", $this->loggedIn);
		$this->set("userData", $this->userData);
		$this->set("userID", $this->uid);

		$this->set("gid", $this->gid);

		$this->set("isAdmin", $this->isAdmin);
		$this->set("isSMod", $this->isSMod);

		if($this->workingMode == "forum") $this->set("forumData", $this->forumData);
	}


	public function afterFilter() {
		// save next session's CSRF token
		if(!isset($this->delayCSRF)) {
			$this->Session->write("System.csrfToken", $this->csrfToken);
		}

		// moved in 10220. user cleanup stuff
		if($this->loggedIn and $this->workingMode == "forum") {
			/**
			 * Update Users Last Online.
			 * The last_active (int unix) is only updated if time() - last_active > 45 (seconds)
			 * @since    10021
			 */
			if(time() - $this->userData["User"]["last_active"] > 45) {
				$this->User->updateLastActive($this->uid, $this->here);
			}

			/**
			 * Clean out messages that are older than 2 days and have been read, or older than 14 days that haven't been read.
			 * @since    10220
			 */
			if(!($lastDailyCleanup = Cache::read("Iris-last-daily-cleanup")) or $lastDailyCleanup + 86400 < time()) {
				$cutOff = time() - 172800; // 2d
				$cutOffUnread = time() - 1209600; // 14d
				$this->User->MessageThread->deleteAll(
					array(
						"OR" => array(
							array(
								"MessageThread.update_date <" => $cutOff,
								"MessageThread.flag_host" => 0,
								"MessageThread.flag_guest" => 0
							),
							array(
								"MessageThread.update_date <" => $cutOffUnread
							),
							array(
								"MessageThread.flag_forcedel" => 1
							)
						)
					)
				);

				// good.
				Cache::write("Iris-last-daily-cleanup", time());
			}
		}
	}

	/**
	 * Verify-CSRF
	 * Pass me a CSRF token, I'll take care of checking whether it works and send the user on its way out if not
	 * @since    10024
	 */
	protected function _verifyCSRF($token) {
		$this->lastToken = $this->Session->read("System.csrfToken");
		if($this->lastToken != $token) {
			$this->Incident->reportIncident("CSRF Token Mismatch (real={$this->lastToken})", "core/security/csrf", 1);
			$this->redirect($this->Iris->siteURL("/pages/csrf"));
		}

		return true;
	}
	
	/**
	 * Delay CSRF Regeneration
	 * Delays CSRF Regeneration for ONE request. This essentially means to persist the last CSRF token.
	 * Don't use this unless in AJAX.
	 * @since    10040
	 */
	protected function _delayCSRFRegeneration() {
		$this->delayCSRF = true;
	}

	/**
	 * Enforce Logon
	 * Call $this->_enforceLogon to ensure there's an registered user accessing this page.
	 * @since    10041
	 */
	protected function _enforceLogon() {
		if(!$this->loggedIn) {
			$this->Incident->reportIncident("Permission Error: Guest is attempting access to a Logon-Enforced Page", "core/user, permissions", 1);
			$this->redirect($this->Iris->siteURL("/pages/404"));
		}
	}

	/**
	 * Enforce Logon
	 * Call $this->_enforceForum to ensure you are in workingMode=forum
	 * @since    10100 Rev. 5
	 */
	protected function _enforceForum() {
		if($this->workingMode != "forum") {
			$this->Incident->reportIncident("Attempted to access forum-level action when in site mode. (_enforceForum)", "site", 1);
			$this->redirect($this->Iris->siteURL("/pages/404"));
		}
	}

}
