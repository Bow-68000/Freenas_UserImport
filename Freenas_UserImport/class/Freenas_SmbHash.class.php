<?php
/**
Freenas_SmbHash.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("Freenas_Base.class.php");

class Freenas_SmbHash extends Freenas_Base {
	public function __construct() {
		parent::__construct();
	}

	public function createSmbhash($uid, $username) {
		$smbhash = $username . ":" . $uid . ":XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX:[U          ]:LCT-00000000:";
		return $smbhash;
	}
}
