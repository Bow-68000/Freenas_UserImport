<?php
/**
Freenas_Import.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("Freenas_Base.class.php");
include_once("Freenas_Database.class.php");
include_once("Freenas_UnixHash.class.php");
include_once("Freenas_SmbHash.class.php");
include_once("Freenas_GroupDiff.class.php");
include_once("Freenas_Utility.class.php");

class Freenas_Import extends Freenas_Base {
	private $freenas_database;
	private $freenas_unixhash;
	private $freenas_smbhash;
	private $freenas_group_diff;

	private $fname;
	private $line_count;

	public function __construct($ssh) {
		parent::__construct();

		$this->freenas_database = new Freenas_Database($ssh);
		$this->freenas_database->import();

		$this->freenas_unixhash = new Freenas_UnixHash();
		$this->freenas_smbhash = new Freenas_SmbHash();
		$this->freenas_group_diff = new Freenas_GroupDiff();
	}

	/*
	 * csvの内容
	 *
	 *  1.bsdusr_uid				数値	insert時は必須
	 *  2.bsdusr_username			文字列	update時のキー
	 *  3.password					文字列
	 *  4.bsdusr_full_name			文字列
	 *  5.bsdusr_email				文字列
	 *  6.bsdusr_group				文字列	プライマリーグループ名
	 *  7.bsdusr_shell				文字列
	 *  8.bsdusr_home				文字列
	 *  9.bsdusr_password_disabled	数値	0(enabled)か1(diabled)
	 * 10.bsdusr_locked				数値	0(unlocked)か1(locked)
	 * 11.bsdusr_microsoft_account	数値	0(non microsoft account)か1(microsoft account)
	 * 12.bsdusr_builtin			数値	0(non builtin)か1(builtin)
	 * 13.bsdusr_sudo				数値	0(can't sudo)か1(can sudo)
	 * 14.groups					文字列	グループの名前を|区切りで並べた文字列 (ex. ma|backup|test)
	 *
	 * 先頭行はヘッダという事で無視
	 * 空行は無視
	 */
	public function importCsvFile($fname) {
		$this->fname = $fname;

		$key_names = array(
			 0 => "bsdusr_uid",
			 1 => "bsdusr_username",
			 2 => "password",
			 3 => "bsdusr_full_name",
			 4 => "bsdusr_email",
			 5 => "bsdusr_group",
			 6 => "bsdusr_shell",
			 7 => "bsdusr_home",
			 8 => "bsdusr_password_disabled",
			 9 => "bsdusr_locked",
			10 => "bsdusr_microsoft_account",
			11 => "bsdusr_builtin",
			12 => "bsdusr_sudo",
			13 => "groups"
		);

		$this->writeLog("Loading " . $this->fname, LogFileMaker::NORMAL);
		$text = file_get_contents($this->fname);

		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);

		$lines = explode("\n", $text);

		$this->line_count = 0;
		$import_count = 0;
		foreach ($lines as $line) {
			$this->line_count++;
			if ($this->line_count == 1) {
				$this->writeLog("skip line " . $this->line_count, LogFileMaker::DEBUG);
				continue;
			}

			$line = trim($line);
			if ($line == "") {
				$this->writeLog("skip line " . $this->line_count, LogFileMaker::DEBUG);
				continue;
			}

			// カンマで分割
			$tmp_col = explode(",", $line);
			if (count($tmp_col) != 14) {
				throw new Exception($this->getInfo() . "illegal column count", E_USER_ERROR);
			}

			// カラムのトリム処理と空文字変換
			$col = array();
			for($i = 0; $i < count($tmp_col); $i++) {
				if (preg_match('/^"(.+)"$/', $tmp_col[$i], $m)) {
					$tmp_col[$i] = $m[1];
				}

				// 空のカラムは値が与えられていないものとする
				if ($tmp_col[$i] != "") {
					// 空文字を指定する場合は (empty) と指定するものとする
					if ($tmp_col[$i] == "(empty)") {
						$tmp_col[$i] = "";
					}
					$col[$key_names[$i]] = $tmp_col[$i];
				}
			}

			/*
			 * インポートデータのusernameを検索し、存在しなければinsert、存在すればupdateを行う
			 * 以上より、usernameは必須項目
			 */
			$this->writeLog("line " . $this->line_count . " checking", LogFileMaker::DEBUG);

			if (!isset($col["bsdusr_username"]) || $col["bsdusr_username"] == "") {
				throw new Exception($this->getInfo() . "bsdusr_username not found", E_USER_ERROR);
			}
			if (strlen($col["bsdusr_username"]) > 16) {
				throw new Exception($this->getInfo() . "bsdusr_username too long (" . $col["bsdusr_username"] . ")", E_USER_ERROR);
			}
			if (!Freenas_Utility::isUsername($col["bsdusr_username"])) {
				throw new Exception($this->getInfo() . "bsdusr_username illegal character (" . $col["bsdusr_username"] . ")", E_USER_ERROR);
			}

			// ここで、Freenas_Databaseよりusernameの存在チェックをして、insertかupdateかを調べる
			if ($this->freenas_database->existsUsername($col["bsdusr_username"])) {
				$this->updateUser($col, true);
			} else {
				$this->insertUser($col);
			}

			$import_count++;
		}

		$this->writeLog("success import " . $import_count, LogFileMaker::DEBUG);

		$this->freenas_database->execSqls();

		$this->freenas_database->execSambaUsers();

        $this->writeLog("Complete", LogFileMaker::NORMAL);
	}

	/*
	 * bsdusr_uid
	 * bsdusr_username
	 * password				-> unixhash
	 * bsdusr_full_name
	 * bsdusr_email
	 * bsdusr_group
	 * bsdusr_shell
	 * bsdusr_home
	 * bsdusr_password_disabled
	 * bsdusr_locked
	 * bsdusr_microsoft_account
	 * bsdusr_builtin
	 * bsdusr_sudo
	 *                  	-> smbhash
	 * group
     */
	private function insertUser($user) {
		if (!isset($user["bsdusr_full_name"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_full_name not found", E_USER_ERROR);
		}
		if (mb_strlen($user["bsdusr_full_name"]) > 120) {
			throw new Exception($this->getInfo() . "bsdusr_full_name too long", E_USER_ERROR);
		}

		if (!isset($user["bsdusr_password_disabled"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_password_disabled not found", E_USER_ERROR);
		}
		if (!Freenas_Utility::is01($user["bsdusr_password_disabled"])) {
			throw new Exception($this->getInfo() . "bsdusr_password_disabled is not 0,1", E_USER_ERROR);
		}

		if (!isset($user["bsdusr_locked"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_locked not found", E_USER_ERROR);
		}
		if (!Freenas_Utility::is01($user["bsdusr_locked"])) {
			throw new Exception($this->getInfo() . "bsdusr_locked is not 0,1", E_USER_ERROR);
		}

		// bsdusr_username チェック済

		if (!isset($user["bsdusr_group"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_group not found", E_USER_ERROR);
		}
		$group_id = $this->freenas_database->getGroupId($user["bsdusr_group"]);
		if ($group_id == -1) {
			throw new Exception($this->getInfo() . "bsdusr_group_id '" . $user["bsdusr_group"] . "' not found: ", E_USER_ERROR);
		}
		$user["bsdusr_group_id"] = $group_id;

		if (!isset($user["bsdusr_uid"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_uid not found", E_USER_ERROR);
		}
		if (!Freenas_Utility::isNum($user["bsdusr_uid"])) {
			throw new Exception($this->getInfo() . "bsdusr_uid not number", E_USER_ERROR);
		}
		if ($this->freenas_database->existsUserUid($user["bsdusr_uid"])) {
			throw new Exception($this->getInfo() . "bsdusr_uid already exists", E_USER_ERROR);
		}

		$user["id"] = $this->freenas_database->nextvalUserId();

		if (!isset($user["bsdusr_microsoft_account"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_microsoft_account not found", E_USER_ERROR);
		}
		if (!Freenas_Utility::is01($user["bsdusr_microsoft_account"])) {
			throw new Exception($this->getInfo() . "bsdusr_microsoft_account is not 0,1", E_USER_ERROR);
		}

		if (!isset($user["password"])) {
			throw new Exception($this->getInfo() . "insert error, password not found", E_USER_ERROR);
		}
		if (mb_strlen($user["password"]) > 32) {
			throw new Exception($this->getInfo() . "password too long", E_USER_ERROR);
		}
		if (!Freenas_Utility::isPassword($user["password"])) {
			throw new Exception($this->getInfo() . "password illegal character", E_USER_ERROR);
		}

		// unixhashの処理
		$salt = $this->freenas_unixhash->createSalt();
		$unixhash = $this->freenas_unixhash->createUnixHash($user["password"], $salt);
		$user["bsdusr_unixhash"] = $unixhash;

		// passwordが定義されている事は保証される
		// samba_userで必要

		if (!isset($user["bsdusr_shell"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_shell not found", E_USER_ERROR);
		}
		if (mb_strlen($user["bsdusr_shell"]) > 150) {
			throw new Exception($this->getInfo() . "bsdusr_shell too long", E_USER_ERROR);
		}
		if (!Freenas_Utility::isShell($user["bsdusr_shell"])) {
			throw new Exception($this->getInfo() . "bsdusr_shell illegal character", E_USER_ERROR);
		}

		if (!isset($user["bsdusr_builtin"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_builtin not found", E_USER_ERROR);
		}
		if (!Freenas_Utility::is01($user["bsdusr_builtin"])) {
			throw new Exception($this->getInfo() . "bsdusr_builtin is not 0,1", E_USER_ERROR);
		}

		if (!isset($user["bsdusr_home"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_home not found", E_USER_ERROR);
		}
		if (mb_strlen($user["bsdusr_home"]) > 150) {
			throw new Exception($this->getInfo() . "bsdusr_home too long", E_USER_ERROR);
		}
		if (!Freenas_Utility::isHome($user["bsdusr_home"])) {
			throw new Exception($this->getInfo() . "bsdusr_home illegal character", E_USER_ERROR);
		}

		if (!isset($user["bsdusr_sudo"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_sudo not found", E_USER_ERROR);
		}
		if (!Freenas_Utility::is01($user["bsdusr_sudo"])) {
			throw new Exception($this->getInfo() . "bsdusr_sudo is not 0,1", E_USER_ERROR);
		}

		if (!isset($user["bsdusr_email"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_email not found", E_USER_ERROR);
		}
		if (mb_strlen($user["bsdusr_email"]) > 150) {
			throw new Exception($this->getInfo() . "bsdusr_email too long", E_USER_ERROR);
		}
		if (!Freenas_Utility::isEmail($user["bsdusr_email"])) {
			throw new Exception($this->getInfo() . "bsdusr_email illegal character", E_USER_ERROR);
		}

		// sambaハッシュを定義
		$user["bsdusr_smbhash"] = $this->freenas_smbhash->createSmbhash($user["bsdusr_uid"], $user["bsdusr_username"]);

		if (!isset($user["bsdusr_email"])) {
			throw new Exception($this->getInfo() . "insert error, bsdusr_email not found", E_USER_ERROR);
		}

		// SQL
		$this->writeLog("line " . $this->line_count . " insert SQL create", LogFileMaker::DEBUG);
		$this->freenas_database->insertUser($user);

		if (isset($user["groups"])) {
			// ユーザがinsertなので、グループもすべてinsertで良い
			$insert_group_ids = $this->convertGroupIds($user["groups"]);
			$this->insertMemberships($user["id"], $insert_group_ids);
		}
	}

	private function updateUser($after_user, $fore_update = false) {
		$before_user = $this->freenas_database->getUser($after_user["bsdusr_username"]);

		$user = array();

		$user["id"] = $before_user["id"];
		$user["bsdusr_username"] = $before_user["bsdusr_username"];	// samba_userで必要

		// bsdusr_full_name
		if (isset($after_user["bsdusr_full_name"])) {
			if (strlen($after_user["bsdusr_full_name"]) > 40) {
				throw new Exception($this->getInfo() . "bsdusr_full_name too long", E_USER_ERROR);
			}
			if ($before_user["bsdusr_full_name"] != $after_user["bsdusr_full_name"] || $fore_update) {
				$user["bsdusr_full_name"] = $after_user["bsdusr_full_name"];
			}
		}

		// bsdusr_password_disabled
		if (isset($after_user["bsdusr_password_disabled"])) {
			if (!Freenas_Utility::is01($after_user["bsdusr_password_disabled"])) {
				throw new Exception($this->getInfo() . "bsdusr_password_disabled is not 0,1", E_USER_ERROR);
			}
			if ($before_user["bsdusr_password_disabled"] != $after_user["bsdusr_password_disabled"] || $fore_update) {
				$user["bsdusr_password_disabled"] = $after_user["bsdusr_password_disabled"];
			}
		}

		// bsdusr_locked
		if (isset($after_user["bsdusr_locked"])) {
			if (!Freenas_Utility::is01($after_user["bsdusr_locked"])) {
				throw new Exception($this->getInfo() . "bsdusr_locked is not 0,1", E_USER_ERROR);
			}
			if ($before_user["bsdusr_locked"] != $after_user["bsdusr_locked"] || $fore_update) {
				$user["bsdusr_locked"] = $after_user["bsdusr_locked"];
			}
		}

		// bsdusr_username 変更不可

		// bsdusr_group_id
		if (isset($after_user["bsdusr_group"])) {
			$group_id = $this->freenas_database->getGroupId($after_user["bsdusr_group"]);
			if ($group_id == -1) {
				throw new Exception($this->getInfo() . "bsdusr_group_id not found", E_USER_ERROR);
			}

			$after_user["bsdusr_group_id"] = $group_id;

			if ($before_user["bsdusr_group_id"] != $after_user["bsdusr_group_id"] || $fore_update) {
				$user["bsdusr_group_id"] = $after_user["bsdusr_group_id"];
			}
		}

		// uidが指定されていれば、あっているかチェック
		if (isset($col["bsdusr_uid"])) {
			if (!Freenas_Utility::isNum($after_user["bsdusr_uid"])) {
				throw new Exception($this->getInfo() . "bsduser_uid not number", E_USER_ERROR);
			}
			if ($before_user["bsdusr_uid"] != $after_user["bsdusr_uid"] || $fore_update) {
				throw new Exception($this->getInfo() . "error bsdusr_uid", E_USER_ERROR);
			}
		}

		// id 変更不可
		// idはキーの為、確実に必要(beforeとafterで必ず同一)

		//  bsdusr_microsoft_account
		if (isset($after_user["bsdusr_microsoft_account"])) {
			if (!Freenas_Utility::is01($after_user["bsdusr_microsoft_account"])) {
				throw new Exception($this->getInfo() . "bsdusr_microsoft_account is not 0,1", E_USER_ERROR);
			}
			if ($before_user["bsdusr_microsoft_account"] != $after_user["bsdusr_microsoft_account"] || $fore_update) {
				$user["bsdusr_microsoft_account"] = $after_user["bsdusr_microsoft_account"];
			}
		}

		// password => bsdusr_unixhashを作って比較
		if (isset($after_user["password"])) {
			if (strlen($after_user["password"]) > 32) {
				throw new Exception($this->getInfo() . "password too long", E_USER_ERROR);
			}
			if (!Freenas_Utility::isPassword($after_user["password"])) {
				throw new Exception($this->getInfo() . "password illegal character", E_USER_ERROR);
			}

			$before_salt = $this->freenas_unixhash->getSalt($before_user["bsdusr_unixhash"]);

			$after_unixhash = $this->freenas_unixhash->createUnixHash($after_user["password"], $before_salt);

			if ($before_user["bsdusr_unixhash"] != $after_unixhash || $fore_update) {
				$user["bsdusr_unixhash"] = $after_unixhash;
				$user["password"] = $after_user["password"];		// samba_userで必要
			}
		}

		// bsdusr_shell
		if (isset($after_user["bsdusr_shell"])) {
			if (strlen($after_user["bsdusr_shell"]) > 50) {
				throw new Exception($this->getInfo() . "bsdusr_shell too long", E_USER_ERROR);
			}
			if (!Freenas_Utility::isShell($after_user["bsdusr_shell"])) {
				throw new Exception($this->getInfo() . "bsdusr_shell illegal character", E_USER_ERROR);
			}
			if ($before_user["bsdusr_shell"] != $after_user["bsdusr_shell"] || $fore_update) {
				$user["bsdusr_shell"] = $after_user["bsdusr_shell"];
			}
		}

		// bsdusr_builtin
		if (isset($after_user["bsdusr_builtin"])) {
			if (!Freenas_Utility::is01($after_user["bsdusr_builtin"])) {
				throw new Exception($this->getInfo() . "bsdusr_builtin is not 0,1", E_USER_ERROR);
			}
			if ($before_user["bsdusr_builtin"] != $after_user["bsdusr_builtin"] || $fore_update) {
				$user["bsdusr_builtin"] = $after_user["bsdusr_builtin"];
			}
		}

		// bsdusr_home
		if (isset($after_user["bsdusr_home"])) {
			if (strlen($after_user["bsdusr_home"]) > 50) {
				throw new Exception($this->getInfo() . "bsdusr_home too long", E_USER_ERROR);
			}
			if (!Freenas_Utility::isHome($after_user["bsdusr_home"])) {
				throw new Exception($this->getInfo() . "bsdusr_home illegal character", E_USER_ERROR);
			}
			if ($before_user["bsdusr_home"] != $after_user["bsdusr_home"] || $fore_update) {
				$user["bsdusr_home"] = $after_user["bsdusr_home"];
			}
		}

		// bsdusr_sudo
		if (isset($after_user["bsdusr_sudo"])) {
			if (!Freenas_Utility::is01($after_user["bsdusr_sudo"])) {
				throw new Exception($this->getInfo() . "bsdusr_sudo is not 0,1", E_USER_ERROR);
			}
			if ($before_user["bsdusr_sudo"] != $after_user["bsdusr_sudo"] || $fore_update) {
				$user["bsdusr_sudo"] = $after_user["bsdusr_sudo"];
			}
		}

		// bsdusr_email
		if (isset($after_user["bsdusr_email"])) {
			if (strlen($after_user["bsdusr_email"]) > 50) {
				throw new Exception($this->getInfo() . "bsdusr_email too long", E_USER_ERROR);
			}
			if (!Freenas_Utility::isEmail($after_user["bsdusr_email"])) {
				throw new Exception($this->getInfo() . "bsdusr_email illegal character", E_USER_ERROR);
			}
			if ($before_user["bsdusr_email"] != $after_user["bsdusr_email"] || $fore_update) {
				$user["bsdusr_email"] = $after_user["bsdusr_email"];
			}
		}

		// bsdusr_smbhash => usernameとuidが変更不可の為、変更不可

		// SQL
		if ($this->isUpdate($user)) {
			$this->writeLog("line " . $this->line_count . " update SQL create", LogFileMaker::DEBUG);
			$this->freenas_database->updateUser($user);
		} else {
			$this->writeLog("line " . $this->line_count . " no update", LogFileMaker::DEBUG);
		}

		// グループの処理
		if (isset($after_user["groups"])) {
			$before_group_ids = $this->freenas_database->getGroupIds($before_user["id"]);
			$after_group_ids = $this->convertGroupIds($after_user["groups"]);

			$this->freenas_group_diff->setGroups($after_group_ids, $before_group_ids);

			// SQL
			$insert_group_ids = $this->freenas_group_diff->getInsertGroupIds();
			if (count($insert_group_ids) > 0) {
				$this->insertMemberships($user["id"], $insert_group_ids);
			}

			$delete_group_ids = $this->freenas_group_diff->getDeleteGroupIds();
			if (count($delete_group_ids) > 0) {
				$this->deleteMemberships($user["id"], $delete_group_ids);
			}
		}
	}

	private function isUpdate($user) {
		$labels = array(
			"bsdusr_full_name",
			"bsdusr_password_disabled",
			"bsdusr_locked",
			"bsdusr_group_id",
			"bsdusr_microsoft_account",
			"bsdusr_unixhash",
			"bsdusr_shell",
			"bsdusr_builtin",
			"bsdusr_home",
			"bsdusr_sudo",
			"bsdusr_email"
		);

		foreach($labels as $label) {
			if (isset($user[$label])) {
				return true;
			}
		}

		return false;
	}

	private function convertGroupIds($group_list) {
		if (is_null($group_list)) {
			return array();
		}
		$group_names = explode("|", $group_list);
		if (count($group_names) == 0) {
			return array();
		}

		$group_ids = array();
		foreach($group_names as $group_name) {
			$group_name = trim($group_name);

			if ($group_name == "") {
				continue;
			}

			$group_id = $this->freenas_database->getGroupId($group_name);
			if ($group_id == -1) {
				throw new Exception($this->getInfo() . "group name '" . $group_name .  "' not found", E_USER_ERROR);
			}
			$group_ids[] = $group_id;
		}

		return $group_ids;
	}


	private function insertMemberships($user_id, $group_ids) {
		foreach($group_ids as $group_id) {
			$this->freenas_database->insertMembership($user_id, $group_id);
		}
	}

	private function deleteMemberships($user_id, $group_ids) {
		foreach($group_ids as $group_id) {
			$this->freenas_database->deleteMembership($user_id, $group_id);
		}
	}

	public function getSqls() {
		return $this->freenas_database->getSqls();
	}

	private function getInfo() {
		return "file: " . $this->fname . ", line: " . $this->line_count . " ";
	}
}
