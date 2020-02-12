<?php
/**
Freenas_MembershipSqlCreator.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("Freenas_Base.class.php");
include_once("SqlCreator.class.php");

class Freenas_MembershipSqlCreator extends Freenas_Base {
	private $creator;

	function __construct() {
		parent::__construct();

		$this->creator = new SqlCreator();
	}

	/*
	 * id
	 * bsdgrpmember_group_id
	 * bsdgrpmember_user_id
	 */
	public function createInsertSql($id, $user_id, $group_id) {
		$this->creator->setTable("account_bsdgroupmembership");

		$this->creator->addCol("id", $this->creator->dbid($id));
		$this->creator->addCol("bsdgrpmember_group_id", $this->creator->dbid($group_id));
		$this->creator->addCol("bsdgrpmember_user_id", $this->creator->dbid($user_id));

		return $this->creator->insert();
	}

	public function createDeleteSql($id) {
		$this->creator->setTable("account_bsdgroupmembership");
		$this->creator->addKey("id", $this->creator->dbid($id));

		return $this->creator->delete();
	}
}
