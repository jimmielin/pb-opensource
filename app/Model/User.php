<?php
/**
 * Project "Iris"
 * A powerful, extendable, in-house developed multi-forum engine for commercial use.
 *
 * @copyright    (c) 2012 Jimmie Lin <jimmie.lin@gmail.com>
 * @license      See LICENSE.
 * @since        Version 10000. 2012.7.11
 *
 * User Model.
 * Note that our User System is completely different from Cake's Built-in Authentication System.
 * In order to control the authentication data within different forums, we have opted to use a unified User table (users)
 * that contains users from all forums, separated by forum_id. As for the main site, we opted to use the 'support' forum's auth section
 * (forum_id=1). As for forum ownership, yep, it is pointed to a user_id (where forum_id=1) too~
 *
 * Session Management Part.
 * For session keys, we store the current logged-in user ID within a key named iris-session-X, where X is the respective forum.
 * 
 * Written by Jimmie Lin
 * Currently Playing: Linkin Park - Don't Stay, The Bravery - I Am Your Skin (Stir the Blood), Linkin Park - Burn It Down
 */

class User extends AppModel {
	public $name = "User";

	public $hasMany = array(
		"Topic",
		"Reply",
		"Incident",
		"ModeratorAction",
		"MessageThread" => array(
			"dependent" => true,
			"foreignKey" => "host_id"
		),
		"MessagePost",
		"Warning" => array(
			"dependent" => true,
			"foreignKey" => "user_id"
		)
	);

	public $belongsTo = array("Forum", "Group");

	/**
	 * Moderation Code.
	 * @since    10021, adopted for User.php on 10023
	 */
	public $hasAndBelongsToMany = array(
		"ModeratedSection" => array(
			"className" => "Section",
			"joinTable" => "moderators_sections",
			"associationForeignKey" => "section_id",
			"foreignKey" => "moderator_id"
		)
	);

	/**
	 * Password Hashing.
	 * This doesn't use CakePHP's system, but a rather, uh, weird approach. This is just SHA-512 with some random salt though.
	 * Salts have been modified for shared-source code. Other parts mainly untouched.
	 */
	public function hash($password) {
		$hash = $password . "salt-part1" . strlen($password) . "salt-part2";

		if(strlen($password) < 6) return false;

		// routineHook @ 10002 @ salting processing

		$hash = hash("sha512", $hash);

		return $hash;
	}

	/**
	 * Validate Username.
	 * This is a variation of "Unique"; actually, this validates the username *and* forum_id, and ensures the username is valid for given forum_id
	 */
	public function validateUsername($username, $forum_id) {
		return !($this->find("count", array("conditions" => array("User.username" => $username, "User.forum_id" => $forum_id))) > 0);
		// gotta love one-liners.
	}

	/**
	 * Get Userdata (by ID)
	 * This is used to get a full stack of Userdata. Note that this is called much too often (to get user data), however it isn't useful it you want
	 * a full report for a user - this only gives you the User table itself and the forum it belongs to.
	 * Feel free to run this often, same for the username variant.
	 * @since    10010
	 */
	public function getUserData($id) {
		return $this->getUserDataGlobal("id", $id);
	}

	/**
	 * Get Userdata (by UName)
	 * This is used to get a full stack of Userdata. Note that this is called much too often (to get user data), however it isn't useful it you want
	 * a full report for a user - this only gives you the User table itself and the forum it belongs to.
	 * Feel free to run this often, same for the ID variant.
	 * @since    10010
	 */
	public function getUserDataByName($username, $forum_id) {
		return $this->getUserDataGlobal("username", $username, $forum_id);
	}

	/**
	 * Global Get User Data.
	 * This is a shared function (getUserData and getUserDataByName) where you pass the field (uniquely identifiable), the value, and forum_id (if necessary)
	 * If you use ID, do not worry about forum_id (omit the argument). If you use "username", you'll have to pass the forum_id or we will throw an error.
	 * @since    10021
	 */
	public function getUserDataGlobal($field, $value, $forum_id = -1, $extra_contain = array()) {
		if($field != "id" and $forum_id == -1) {
			return false;
		}

		$conditions["User." . $field] = $value;
		if($forum_id != -1)
			$conditions["User.forum_id"] = (int) $forum_id;

		$contain = array(
					"Forum",
					"Group",
					"ModeratedSection" => array(
						"fields" => array(
							"id", "title"
						)
					),
					"Warning" => array(
						"order" => array("Warning.created DESC")
					)
				);
		if(count($extra_contain)) $contain = array_merge($contain, $extra_contain);

		return $this->find("first",
			array(
				"conditions" => $conditions,
				"contain" => $contain
			)
		);

	}

	/**
	 * Validate User Login (by username, password, forum_id)
	 * Returns a UserController/UserViews-compatible Error Code. 0=ok, 1=no such user, 2=password doesn't match
	 * Returns array(bool result, int error code [, int user_id (if success)])
	 * @since    10010
	 */
	public function validateLogin($username, $password, $forum_id) {
		$user = $this->getUserDataByName($username, $forum_id);
		if(!$user) {
			$userAlt = $this->getUserDataGlobal("last_username", $username, $forum_id);
			if(!$userAlt) return array(false, 1);
			else {
				// handle this, but remember to return "error code" as 9 (username change)
				$ec = 9;
				$user = $userAlt;
			}
		}
		else $ec = 0;

		if($this->hash($password) != $user["User"]["password"]) {
			return array(false, 2);
		}

		/**
		 * User is correctly validated. Update Last Active. Also wipe previous username if necessary
		 * @since    10210/10220
		 */
		if($ec == 0 and !empty($user["User"]["last_username"])) {
			$this->save(
				array(
					"User" => array(
						"id" => $user["User"]["id"],
						"last_active" => time() - 145, // will be updated on next refresh
						"last_active_here" => "",
						"is_logout" => 0,
						"last_active_ip" => $_SERVER["REMOTE_ADDR"],
						"last_login" => time(),
						"previous_login" => $user["User"]["last_login"],
						"last_username" => ""
					)
				)
			);
		}
		else {
			$this->save(
				array(
					"User" => array(
						"id" => $user["User"]["id"],
						"last_active" => time() - 145, // will be updated on next refresh
						"last_active_here" => "",
						"is_logout" => 0,
						"last_active_ip" => $_SERVER["REMOTE_ADDR"],
						"last_login" => time(),
						"previous_login" => $user["User"]["last_login"]
					)
				)
			);
		}

		return array(true, $ec, $user["User"]["id"]);
	}

	/** 
	 * Update Last Active Field.
	 * Updates a given user's last_active field to the current time() timestamp.
	 * @since    10021
	 */
	public function updateLastActive($id, $here = "") {
		return $this->save(
			array(
				"User" => array(
					"id" => $id,
					"last_active" => time(),
					"last_active_here" => $here,
					"is_logout" => 0,
					"last_active_ip" => $_SERVER["REMOTE_ADDR"]
				)
			)
		);
	}

	/**
	 * Private Messaging System.
	 * Get Messages.
	 * This will get messages returned in two arrays. [AsHost] and [AsGuest], and what is returned is only the ones that are flagged for this user.
	 * @since    10100 Rev. 6
	 */
	function getNewMessages($id) {
		$Messages["AsHost"] = $this->MessageThread->find("all", array(
			"conditions" => array(
				"MessageThread.host_id" => $id,
				"MessageThread.flag_host" => 1
			),
			"contain" => array(
				"MessagePost" => array(
					"order" => "MessagePost.date DESC",
					"limit" => 1
				)
			)
		));
		
		$Messages["AsGuest"] = $this->MessageThread->find("all", array(
			"conditions" => array(
				"MessageThread.guest_id" => $id,
				"MessageThread.flag_guest" => 1
			),
			"contain" => array(
				"MessagePost" => array(
					"order" => "MessagePost.date DESC",
					"limit" => 1
				)
			)
		));
		
		return $Messages;
	}

}