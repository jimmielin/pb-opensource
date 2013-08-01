<?php
/**
 * Project "Iris"
 * A powerful, extendable, in-house developed multi-forum engine for commercial use.
 *
 * @copyright    (c) 2012 Jimmie Lin <jimmie.lin@gmail.com>
 * @license      See LICENSE.
 * @since        Version 10000. 2012.7.11
 *
 * Forums Controller
 * 
 * Written by Jimmie Lin
 * Currently Playing: Stir The Blood (album) - The Bravery
 */

class ForumsController extends AppController {
	public function index() {
		// processing stripped @ 10010 @ github

		$this->set(compact("onlineUsers", "onlineCount", "onlineRecord", "onlineRecordTime", "latestFive"));
	}

	/**
	 * Search Engine. This is an extremely simple system, but hey, it works.
	 * For other purposes, please, just use Google. It doesn't hurt. We are considering Adsense Integration, but not yet.
	 * @since    10100 Rev. 12
	 * Note for JOINs: I really suck at them. Migrated to https://github.com/tigrang/EasyJoin (See SO Question of mine)
	 */
	public function search($forum, $query = false) {
		if(isset($this->data["query"])) $this->set("query", $this->data["query"]);
		if($this->loggedIn and isset($this->data["query"])) {
			$this->redirect($this->Iris->forumURL("forums/search/" . $this->data["query"]));
		}

		if(!$this->loggedIn and !isset($_POST["adcopy_challenge"])) {
			$this->Session->setFlash(__("CAPTCHA Verification Failed! Please try again or log in to skip the CAPTCHA."), "error");
			return;
		}

		if(!$this->loggedIn and isset($_POST["adcopy_challenge"])) {
			// validate captcha @ 10182
		}

		if($query) {
			// find it, for me.
			App::uses('Reply', 'Model'); App::uses('Topic', 'Model'); App::uses('Section', 'Model');
			$replyOptions["joins"] = array(
				Reply::joinLeft("Topic", true, false),
				Topic::joinLeft("Section"),
				Section::joinLeft("Category")
			);

			$replyOptions["conditions"] = array(
				"Category2.forum_id" => $this->forumData["Forum"]["id"],
				"Reply.content LIKE" => "%" . $query . "%"
			);

			$replyOptions["order"] = array(
				"Topic2.last_update DESC"
			);

			$replyOptions["limit"] = 10;
			$replyOptions["contain"] = array("Topic" => array("fields" => array("id", "section_id", "title"), "Section" => array("fields" => array("id", "title", "permission_set"))));

			$replies = $this->Forum->Category->Section->Topic->Reply->find("all", $replyOptions);

			// ... same for topics. they're less... burdensome?
			$topicOptions["joins"] = array(
				Topic::joinLeft("Section", true, false),
				Section::joinLeft("Category")
			);

			$topicOptions["conditions"] = array(
				"Category2.forum_id" => $this->forumData["Forum"]["id"],
				"OR" => array(
					"Topic.content LIKE" => "%" . $query . "%",
					"Topic.title LIKE" => "%" . $query . "%"
				)
			);

			$topicOptions["order"] = array(
				"Topic.last_update DESC"
			);

			$topicOptions["limit"] = 10;
			$topicOptions["contain"] = array("Section" => array("fields" => array("id", "title", "permission_set")));

			$topics = $this->Forum->Category->Section->Topic->find("all", $topicOptions);

			// and so on.
			$this->set(compact("replies", "topics", "query"));
		}
	}

	/**
	 * The below are Offline (Board On/Off) and Ban (Banning) Modules' Static (or sorta) pages.
	 * @since    10100 Rev. 8
	 */
	public function offline() {
		if(!$this->forumData["Forum"]["is_offline"] or ($this->isAdmin or $this->isSMod)) {
			$this->redirect($this->Iris->forumURL("forums/index"));
		}
	}

	public function ban() {
		if($this->userData["User"]["is_banned"] > time() and !$this->isAdmin and !$this->isSMod) {
			
		}
		else {
			$this->redirect($this->Iris->forumURL("forums/index"));
		}
	}

	/**
	 * Get the latest X topics (3 by default) from a given sectionID.
	 * Comparison is relaxed and access is using -1 (Guest Group).
	 * @since    10100 Rev. 20
	 */
	public function newestTopics($forum, $id, $count = 3) {
		$this->layout = "ajax";
		header("Access-Control-Allow-Origin: *"); // FIXME
		/**
		 * Get this section and check permissions.
		 */
		$sectionData = $this->Forum->Category->Section->find("first", array(
			"conditions" => array(
				"Section.id" => (int) $id
			),
			"contain" => array(
				"Topic" => array(
					"User" => array(
						"fields" => array("id", "username")
					),
					"limit" => (int) $count,
					"order" => array(
						"Topic.created DESC"
					)
				),
				"Category" => array(
					"fields" => array("id", "forum_id")
				)
			)
		));

		if(!$sectionData or $sectionData["Section"]["is_redirect"] or $sectionData["Category"]["forum_id"] != $this->forumData["Forum"]["id"]) {
			die(); // FIXME: Handle this better.
		}

		/**
		 * Verify Permissions...
		 * @since    10022
		 */
		$permissionResult = $this->Iris->verifyPermissions($sectionData["Section"]["permission_set"], -1, "view");
		if($permissionResult === false) {
			die(); // FIXME: Handle this better.
		}

		$this->set("data", $sectionData);
	}

	/**
	 * Get the Latest Topics (updated) since Login (between previous_login and last_login).
	 * @since    10210
	 */
	public function latestSinceLogin($forum) {
		if(!$this->loggedIn)
			$this->redirect($this->Iris->forumURL("users/login"));

		$data = $this->User->Topic->find(
			"all",
			array(
				"limit" => 20,
				"order" => array("Topic.last_update DESC"),
				"contain" => array(
					"Reply" => array(
						"limit" => 1,
						"order" => array("Reply.created DESC"),
						"User" => array(
							"fields" => array("id", "username", "email", "group_id"),
							"Group" => array(
								"fields" => array("id", "color")
							)
						)
					),
					"Section" => array(
						"fields" => array("id", "permission_set")
					),
					"User" => array(
						"fields" => array("id", "username", "email", "group_id"),
						"Group" => array(
							"fields" => array("id", "color")
						)
					)
				),
				"fields" => array("id", "title", "last_update", "section_id", "reply_count", "created", "content"),
				"joins" => array(
					Topic::joinLeft("Section", true, false),
					Section::joinLeft("Category")
				),
				"conditions" => array(
					"Category2.forum_id" => $this->forumData["Forum"]["id"],
					"Topic.last_update >" => $this->userData["User"]["previous_login"],
					"Topic.last_update <" => $this->userData["User"]["last_login"]
				),
				"autocache" => true
			)
		);
		$this->set("data", $data);
	}
}