<?php
/**
LogFileMaker.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

class LogFileMaker {
	const NORMAL = 1;
	const WARNING = 2;
	const ERROR = 3;
	const DEBUG = 9;

	private static $instance;
	private $fp = null;
	private $levels;

	private function __construct() {
	}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new LogFileMaker();
			self::$instance->initLevel();
		}
		return self::$instance;
	}

	public function __destruct() {
		if ($this->fp != null) {
			fclose($this->fp);
		}
	}

	final function __clone() {
		throw new Exception("Clone is not allowd against " . get_class($this), E_USER_ERROR);
	}

	public function open($fname) {
		if ($this->fp != null) {
			fclose($this->fp);
		}

		$this->fp = fopen($fname, "w");
		if ($this->fp === False) {
			throw new Exception("file not open " . $fname, E_USER_ERROR);
		}
    }

    public function initLevel() {
        $this->levels = array();
    }

    public function setLevel($level) {
		$this->levels[] = $level;
	}

	public function writeLog($msg, $level = LogFileMaker::WARNING) {
        if (in_array($level, $this->levels)) {
            print $msg . "\n";
        }

		if ($this->fp == null) {
			return;
		}

		if (in_array($level, $this->levels)) {
			fwrite($this->fp, $msg . "\n");
		}
	}
}
