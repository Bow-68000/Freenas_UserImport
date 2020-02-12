<?php
/**
Freenas_UserSqlCreator.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("Freenas_Base.class.php");
include_once("SqlCreator.class.php");

class Freenas_UserSqlcreator extends Freenas_Base {
	private $creator;

	function __construct() {
		parent::__construct();

		$this->creator = new SqlCreator();
	}
	/*
	 * id
     * bsdusr_full_name
     * bsdusr_password_disabled
     * bsdusr_locked
     * bsdusr_username
     * bsdusr_group_id
     * bsdusr_uid
     * bsdusr_microsoft_account
     * bsdusr_unixhash
     * bsdusr_shell
     * bsdusr_builtin
     * bsdusr_home
     * bsdusr_sudo
     * bsdusr_email
	 * bsdusr_smbhash
     */
	public function createInsertSql($user) {
		$this->creator->setTable("account_bsdusers");

		if (!isset($user["id"])) {
			throw new Exception("SQL: id not found", E_USER_ERROR);
		}
		$this->creator->addCol("id", $this->creator->dbid($user["id"]));

		if (!isset($user["bsdusr_full_name"])) {
			throw new Exception("SQL: bsdusr_full_name not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_full_name", $this->creator->dbstr($user["bsdusr_full_name"]));

		if (!isset($user["bsdusr_password_disabled"])) {
			throw new Exception("SQL: bsdusr_password_disabled not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_password_disabled", $this->creator->dbid($user["bsdusr_password_disabled"]));

		if (!isset($user["bsdusr_locked"])) {
			throw new Exception("SQL: bsdusr_locked not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_locked", $this->creator->dbid($user["bsdusr_locked"]));

		if (!isset($user["bsdusr_username"])) {
			throw new Exception("SQL: bsdusr_username not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_username", $this->creator->dbstr($user["bsdusr_username"]));

		if (!isset($user["bsdusr_group_id"])) {
			throw new Exception("SQL: bsdusr_group_id not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_group_id", $this->creator->dbid($user["bsdusr_group_id"]));

		if (!isset($user["bsdusr_uid"])) {
			throw new Exception("SQL: bsdusr_uid not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_uid", $this->creator->dbid($user["bsdusr_uid"]));

		if (!isset($user["bsdusr_microsoft_account"])) {
			throw new Exception("SQL: bsdusr_microsoft_account not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_microsoft_account", $this->creator->dbid($user["bsdusr_microsoft_account"]));

		if (!isset($user["bsdusr_unixhash"])) {
			throw new Exception("SQL: bsdusr_unixhash not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_unixhash", $this->creator->dbcsh($user["bsdusr_unixhash"]));

		if (!isset($user["bsdusr_shell"])) {
			throw new Exception("SQL: bsdusr_shell not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_shell", $this->creator->dbstr($user["bsdusr_shell"]));

		if (!isset($user["bsdusr_builtin"])) {
			throw new Exception("SQL: bsdusr_builtin not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_builtin", $this->creator->dbid($user["bsdusr_builtin"]));

		if (!isset($user["bsdusr_home"])) {
			throw new Exception("SQL: bsdusr_home not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_home", $this->creator->dbstr($user["bsdusr_home"]));

		if (!isset($user["bsdusr_sudo"])) {
			throw new Exception("SQL: bsdusr_sudo not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_sudo", $this->creator->dbid($user["bsdusr_sudo"]));

		if (!isset($user["bsdusr_email"])) {
			throw new Exception("SQL: bsdusr_email not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_email", $this->creator->dbstr($user["bsdusr_email"]));

		if (!isset($user["bsdusr_smbhash"])) {
			throw new Exception("SQL: bsdusr_smbhash not found", E_USER_ERROR);
		}
		$this->creator->addCol("bsdusr_smbhash", $this->creator->dbstr($user["bsdusr_smbhash"]));

		// Add 2019.11.05
		$this->creator->addCol("bsdusr_attributes", $this->creator->dbstr(""));

		$rets = array(
			"message" => "Insert " . $user["bsdusr_username"],
			"sql" => $this->creator->insert()
		);

        return $rets;
	}

	public function createUpdateSql($user) {
		$this->creator->setTable("account_bsdusers");

		if (!isset($user["id"])) {
			throw new Exception("SQL: id not found", E_USER_ERROR);
		}
		$this->creator->addKey("id", $this->creator->dbid($user["id"]));

		if (isset($user["bsdusr_full_name"])) {
			$this->creator->addCol("bsdusr_full_name", $this->creator->dbstr($user["bsdusr_full_name"]));
		}

		if (isset($user["bsdusr_password_disabled"])) {
			$this->creator->addCol("bsdusr_password_disabled", $this->creator->dbid($user["bsdusr_password_disabled"]));
		}

		if (isset($user["bsdusr_locked"])) {
			$this->creator->addCol("bsdusr_locked", $this->creator->dbid($user["bsdusr_locked"]));
		}

		if (isset($user["bsdusr_group_id"])) {
			$this->creator->addCol("bsdusr_group_id", $this->creator->dbid($user["bsdusr_group_id"]));
		}

		if (isset($user["bsdusr_microsoft_account"])) {
			$this->creator->addCol("bsdusr_microsoft_account", $this->creator->dbid($user["bsdusr_microsoft_account"]));
		}

		if (isset($user["bsdusr_unixhash"])) {
			$this->creator->addCol("bsdusr_unixhash", $this->creator->dbcsh($user["bsdusr_unixhash"]));
		}

		if (isset($user["bsdusr_shell"])) {
			$this->creator->addCol("bsdusr_shell", $this->creator->dbstr($user["bsdusr_shell"]));
		}

		if (isset($user["bsdusr_builtin"])) {
			$this->creator->addCol("bsdusr_builtin", $this->creator->dbid($user["bsdusr_builtin"]));
		}

		if (isset($user["bsdusr_home"])) {
			$this->creator->addCol("bsdusr_home", $this->creator->dbstr($user["bsdusr_home"]));
		}

		if (isset($user["bsdusr_sudo"])) {
			$this->creator->addCol("bsdusr_sudo", $this->creator->dbid($user["bsdusr_sudo"]));
		}

		if (isset($user["bsdusr_email"])) {
			$this->creator->addCol("bsdusr_email", $this->creator->dbstr($user["bsdusr_email"]));
		}

        $rets = array(
            "message" => "Update " . $user["bsdusr_username"],
            "sql" => $this->creator->update()
        );

        return $rets;
	}

	public function createDeleteSql($id)
	{
		$this->creator->setTable("account_bsdusers");
		$this->creator->addKey("id", $this->creator->dbid($id));

        $rets = array(
            "message" => "Delete " . $id,
            "sql" => $this->creator->delete()
        );

		return $rets;
	}
}
