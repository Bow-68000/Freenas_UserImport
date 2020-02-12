<?php
/**
Ssh2.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

class Ssh2 {
	private $cn;
	private $log;

	public function __construct($info) {
		$this->connect($info);
	}

	public function __destruct() {
		$this->close();
	}

	public function connect($info) {
		$this->cn = ssh2_connect($info[2], 22);

		if (!$this->cn) {
		    throw new Exception("ssh2_connect: error", E_USER_ERROR);
		}

		$auth = ssh2_auth_password($this->cn, $info[0], $info[1]);
		if (!$auth) {
			throw new Exception("ssh2_auth_pubkey_file : error", E_USER_ERROR);
		}
	}

	public function close() {
	}

	public function exec($cmd) {
		$stdout_stream = ssh2_exec($this->cn, $cmd);

		if (!$stdout_stream) {
			throw new Exception("ss2_exec : error", E_USER_ERROR);
		}

		$stderr_stream = ssh2_fetch_stream($stdout_stream, SSH2_STREAM_STDERR);

		stream_set_blocking($stdout_stream, true);
		stream_set_blocking($stderr_stream, true);

		$stdout_contents = stream_get_contents($stdout_stream);
		$stderr_contents = stream_get_contents($stderr_stream);

		$this->log = array(
			"command" => $cmd,
			"stdout" => $stdout_contents,
			"stderr" => $stderr_contents
		);

		return $stdout_contents;
	}

	public function execLines($cmd) {
		$contens = $this->exec($cmd);
		$rets = explode("\n", $contens);

		if ($rets[count($rets) - 1] == "") {
			unset($rets[count($rets) - 1]);
		}

		return $rets;
	}

	public function getCommand() {
		return $this->log["command"];
	}

	public function getStdout() {
		return $this->log["stdout"];
	}

	public function getStderr() {
		return $this->log["stderr"];
	}
}
