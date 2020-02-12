<?php
/**
Freenas_Database.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("Freenas_Base.class.php");
include_once("Freenas_Ssh2.class.php");
include_once("Freenas_Id.class.php");
include_once("Freenas_UserSqlCreator.class.php");
include_once("Freenas_MembershipSqlCreator.class.php");

class Freenas_Database extends Freenas_Base {
	private $freenas_ssh;

	private $select_users;
	private $select_groups;
	private $select_memberships;

	private $freenas_user_id;
	private $freenas_group_id;
	private $freenas_membership_id;

	private $freenas_user_sql_creator;
	private $freenas_membership_sql_creator;

	private $sqls;
	private $samba_users;

	public function __construct($ssh) {
		parent::__construct();

		$this->freenas_ssh = new Freenas_Ssh2($ssh);
		$this->freenas_user_id = new Freenas_Id();
		$this->freenas_group_id = new Freenas_Id();
		$this->freenas_membership_id = new Freenas_Id();

		$this->freenas_user_sql_creator = new Freenas_UserSqlCreator();
		$this->freenas_membership_sql_creator = new Freenas_MembershipSqlCreator();

		$this->sqls = array();
		$this->samba_users = array();
	}

	/*
	 * 戻り値は以下の配列
	 * bsdusr_full_name
	 * bsdusr_password_disabled
	 * bsdusr_locked
	 * bsdusr_username
	 * bsdusr_group_id
	 * bsdusr_uid
	 * id							<- index
	 * bsdusr_microsoft_account
	 * bsdusr_unixhash
	 * bsdusr_shell
	 * bsdusr_builtin
	 * bsdusr_home
	 * bsdusr_sudo
	 * bsdusr_email
	 * bsdusr_smbhash
	 */
	private function importUsers() {
		$lines = $this->freenas_ssh->queryUsers();

		$this->select_users = array();
		foreach ($lines as $line) {
			$src = explode("|", $line);
			$col = array();

			$col["bsdusr_full_name"] = $src[0];

			$col["bsdusr_password_disabled"] = $src[1];
			$col["bsdusr_locked"] = $src[2];
			$col["bsdusr_username"] = $src[3];
			$col["bsdusr_group_id"] = $src[4];
			$col["bsdusr_uid"] = $src[5];

			$id = $src[6];
			$col["id"] = $id;

			// microsoft accountはroot以外のビルトインユーザの場合 False とでる
			// これでいいかは大いに疑問
			$col["bsdusr_microsoft_account"] = $src[7];

			$col["bsdusr_unixhash"] = $src[8];
			$col["bsdusr_shell"] = $src[9];
			$col["bsdusr_builtin"] = $src[10];
			$col["bsdusr_home"] = $src[11];
			$col["bsdusr_sudo"] = $src[12];

			$col["bsdusr_email"] = $src[13];
			$col["bsdusr_smbhash"] = $src[14];

			$this->freenas_user_id->addId($id);

			$this->select_users[$id] = $col;
		}
	}

	/*
	 * 戻り値は以下の配列
	 * bsdgrp_group
	 * bsdgrp_gid
	 * id							<- index
	 * bsdgrp_builtin
	 * bsdgrp_sudo
	 */
	private function importGroups()	{
		$lines = $this->freenas_ssh->queryGroups();

		$this->select_groups = array();
		foreach ($lines as $line) {
			$src = explode("|", $line);
			$col = array();
			$col["bsdgrp_group"] = $src[0];
			$col["bsdgrp_gid"] = $src[1];

			$id = $src[2];
			$col["id"] = $id;

			$col["bsdgrp_builtin"] = $src[3];
			$col["bsdgrp_sudo"] = $src[4];

			$this->freenas_group_id->addId($id);

			$this->select_groups[$id] = $col;
		}
	}

	/*
	 * 戻り値は以下の配列
	 * id							<- index
	 * bsdgrpmember_group_id
	 * bsdgrpmember_user_id
	 */
	private function importMemberships() {
		$lines = $this->freenas_ssh->queryMemberships();

		$this->select_memberships = array();
		foreach ($lines as $line) {
			$src = explode("|", $line);
			$col = array();

			$id = $src[0];
			$col["id"] = $id;

			$col["bsdgrpmember_group_id"] = $src[1];
			$col["bsdgrpmember_user_id"] = $src[2];

			// ここで、メンバーシップがビルトインユーザのものかどうかの情報を付加する
			// その為に、importUsersが先に実行されてselect_usersがセットされている必要がある
			if ($this->isBuiltinUser($col["bsdgrpmember_user_id"])) {
				$col["bsdgrpmember_builtin"] = 1;
			} else {
				$col["bsdgrpmember_builtin"] = 0;
			}

			$this->freenas_membership_id->addId($id);

			$this->select_memberships[$id] = $col;
		}
	}

	private function isBuiltinUser($user_id) {
		foreach($this->select_users as $user) {
			if ($user["id"] == $user_id) {
				return $user["bsdusr_builtin"] == 1;
			}
		}
		return false;
	}

	public function import() {
		$this->importUsers();
		$this->importGroups();
		$this->importMemberships();
	}

	public function existsUserUid($user_uid) {
		foreach($this->select_users as $index => $user) {
			if ($user["bsdusr_uid"] == $user_uid) {
				return true;
			}
		}
		return false;
	}

	public function existsUsername($username) {
		foreach($this->select_users as $index => $user) {
			if ($user["bsdusr_username"] == $username) {
				return true;
			}
		}
		return false;
	}

	public function nextvalUserId() {
		return $this->freenas_user_id->nextvalId();
	}

	public function nextvalGroupId() {
		return $this->freenas_group_id->nextvalId();
	}

	public function nextvalMembershipId() {
		return $this->freenas_membership_id->nextvalId();
	}

	public function getGroupId($groupname) {
		foreach($this->select_groups as $index => $group) {
			if ($group["bsdgrp_group"] == $groupname) {
				return $index;
			}
		}
		return -1;
	}

	public function getUser($username) {
		foreach($this->select_users as $index => $user) {
			if ($user["bsdusr_username"] == $username) {
				return $user;
			}
		}
		return -1;
	}

	public function getGroupIds($user_id) {
		$group_ids = array();
		foreach($this->select_memberships as $index => $membership) {
			if ($membership["bsdgrpmember_user_id"] == $user_id) {
				$group_ids[] = $membership["bsdgrpmember_group_id"];
			}
		}
		return $group_ids;
	}

	public function getMembershipId($user_id, $group_id) {
		foreach($this->select_memberships as $index => $membership) {
			if ($membership["bsdgrpmember_user_id"] == $user_id && $membership["bsdgrpmember_group_id"] == $group_id) {
				return $index;
			}
		}

		return -1;
	}

	public function insertUser($user) {
		$id = $user["id"];
		$this->select_users[$id] = $user;
		$this->sqls[] = $this->freenas_user_sql_creator->createInsertSql($user);

		$this->setSambaUser($user["bsdusr_username"], $user["password"]);
	}

	public function updateUser($user) {
		$id = $user["id"];
		foreach($user as $label => $val) {
			$this->select_users[$id][$label] = $val;
		}

		$this->sqls[] = $this->freenas_user_sql_creator->createUpdateSql($user);

		if (isset($user["bsdusr_username"]) && isset($user["password"])) {
			$this->setSambaUser($user["bsdusr_username"], $user["password"]);
		}
	}

	public function insertMembership($user_id, $group_id) {
		$id = $this->nextvalMembershipId();
		$this->select_memberships[$id] = array(
			"id" => $id,
			"bsdgrpmember_group_id" => $group_id,
			"bsdgrpmember_user_id" => $user_id
		);

		$this->sqls[] = $this->freenas_membership_sql_creator->createInsertSql($id, $user_id, $group_id);
	}

	public function deleteMembership($user_id, $group_id) {
		$id = $this->getMembershipId($user_id, $group_id);
		if ($id == -1) {
			return;
		}
		unset($this->select_memberships[$id]);

		$this->sqls[] = $this->freenas_membership_sql_creator->createDeleteSql($id);
	}

	public function getSqls() {
		return $this->sqls;
	}

	public function execSqls() {
		if (count($this->sqls) == 0) {
			return;
		}

		$this->freenas_ssh->backup();

		foreach($this->sqls as $sql) {
			if (is_array($sql)) {
                $this->writeLog("execute " . $sql["sql"], LogFileMaker::DEBUG);
                $this->writeLog($sql["message"], LogFileMaker::NORMAL);
                $sql_exec = $sql["sql"];
            } else {
                $sql_exec = $sql;
			}

			$this->freenas_ssh->execSqlBuffer($sql_exec);
		}

		$this->freenas_ssh->flushSqlBuffer();

		$this->freenas_ssh->refrect();
	}

	public function begin() {
		$this->freenas_ssh->query("BEGIN TRANSACTION");
	}

    public function commit() {
		$this->freenas_ssh->query("COMMIT");
    }

    private function setSambaUser($username, $password) {
		$this->samba_users[$username] = $password;
	}

	public function getSambaUser() {
		return $this->samba_users;
	}

	public function execSambaUsers() {
		foreach($this->samba_users as $username => $password) {
			$this->writeLog("execute pdbedit " . $username, LogFileMaker::DEBUG);
            $this->writeLog("Samba " . $username, LogFileMaker::NORMAL);
			$this->freenas_ssh->execPdbedit($username, $password);
		}
	}
}
