<?php
/**
Freenas_Ssh2.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("Freenas_Base.class.php");

class Freenas_Ssh2 extends Freenas_Base {
	const SSH_RETRY_WAIT = 2;
	const SSH_RETRY_LIMIT = 5;

	const SQL_BUFFER_SIZE = 10;

	private $ssh;

	private $buffers;

	public function __construct($ssh) {
		parent::__construct();

		$this->ssh = $ssh;
		$this->clearSqlBuffer();
	}

	private function execRetry($command) {
		$loop_count = 0;
		while(true) {
			$loop_count += 1;

			$this->ssh->exec($command);

			if ($this->ssh->getStderr() == "") {
				break;
			}

			if ($loop_count >= Freenas_Ssh2::SSH_RETRY_LIMIT) {
				throw new Exception($command . " do not execute", E_USER_ERROR);
			}

			$this->writeLog("execute error", LogFileMaker::DEBUG);
            $this->writeLog(chop($this->ssh->getStderr()), LogFileMaker::DEBUG);

			$sleep_sec = $loop_count * Freenas_Ssh2::SSH_RETRY_WAIT;
			$this->writeLog("Sleep " . $sleep_sec, LogFileMaker::NORMAL);
			sleep($sleep_sec);

			$this->writeLog("retry", LogFileMaker::DEBUG);
		}
	}

	private function clearSqlBuffer() {
		$this->buffers = array();
	}

	public function execSql($sql) {
		$command = <<<EOL
sqlite3 /data/freenas-v1.db "BEGIN TRANSACTION;{$sql};COMMIT;"
EOL;

		$this->execRetry($command);
	}

	public function execSqlBuffer($sql) {
        $this->buffers[] = $sql;
        if (count($this->buffers) >= self::SQL_BUFFER_SIZE) {
            $this->execSql(implode(";", $this->buffers));
			$this->clearSqlBuffer();
        }
	}

	public function flushSqlBuffer() {
        $this->execSql(implode(";", $this->buffers));
        $this->clearSqlBuffer();
	}

	public function execPdbedit($username, $password) {
		$command = <<<EOL
printf "{$password}\\n{$password}"|pdbedit -a -t -u {$username}
EOL;

        $this->writeLog($command, LogFileMaker::DEBUG);

		$this->execRetry($command);
	}

	public function query($sql)	{
		$command = <<<EOL
sqlite3 /data/freenas-v1.db "{$sql};"
EOL;
		$lines = $this->ssh->execLines($command);

		$stderr = $this->ssh->getStderr();
		if ($stderr != "") {
            throw new Exception($stderr);
        }

		return $lines;
	}

	public function queryUsers() {
		$sql = "SELECT bsdusr_full_name,bsdusr_password_disabled,bsdusr_locked,bsdusr_username,bsdusr_group_id,bsdusr_uid,id,bsdusr_microsoft_account,bsdusr_unixhash,bsdusr_shell,bsdusr_builtin,bsdusr_home,bsdusr_sudo,bsdusr_email,bsdusr_smbhash FROM account_bsdusers";
		return $this->query($sql);
	}

	public function queryGroups() {
		$sql = "SELECT bsdgrp_group,bsdgrp_gid,id,bsdgrp_builtin,bsdgrp_sudo FROM account_bsdgroups";
		return $this->query($sql);
	}

	public function queryMemberships() {
		$sql = "SELECT id,bsdgrpmember_group_id,bsdgrpmember_user_id FROM account_bsdgroupmembership";
		return $this->query($sql);
	}


	private function exec($cmd) {
		$command = <<<EOL
{$cmd}
EOL;
		$this->ssh->exec($command);
	}

	public function backup() {
		$now = date("Ymdhis");

		$src_fname = "/data/freenas-v1.db";
		$des_fname = "/data/freenas-v1.db." . $now;

		$this->exec("cp -f " . $src_fname . " " . $des_fname);
	}

	public function refrect() {
		$this->exec("/etc/ix.rc.d/ix-passwd start");
	}
}
