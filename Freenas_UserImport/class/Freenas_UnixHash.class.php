<?php
/**
Freenas_UnixHash.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("Freenas_Base.class.php");

class Freenas_UnixHash extends Freenas_Base {
	public function __construct() {
		parent::__construct();
	}

	public function getSalt($unixhash) {
		$ret = substr($unixhash, 0, 19);

		return $ret;
	}

	public function createUnixHash($password, $salt = "") {
		if ($salt == "") {
			$salt = $this->createSalt();
		}

		$unixhash = crypt($password, $salt);

		return $unixhash;
	}

	public function createSalt() {
		$chars = "0123456789abcdefghujklmnopqrstuvwxyzABCDEFGHIJKLMNIOQRSTUVWXYZ";

		$salt = "$6$";

		for($i = 0; $i < 16; $i++) {
			$salt .= substr(
				$chars,
				rand(0, strlen($chars) - 1),
				1
			);
		}

		return $salt;
	}
}
