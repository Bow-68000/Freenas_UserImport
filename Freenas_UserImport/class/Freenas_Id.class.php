<?php
/**
Freenas_Id.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("Freenas_Base.class.php");

class Freenas_Id extends Freenas_Base {
	private $ids;

	public function __construct() {
		parent::__construct();

		$this->init();
	}

	public function init() {
		$this->ids = array();
	}

	public function addId($id) {
		$this->ids[] = $id;
	}

	public function existsId($id) {
		return in_array($id, $this->ids);
	}

	public function nextvalId() {
		$max_id = 0;
		foreach($this->ids as $id) {
			if ($max_id < $id) {
				$max_id = $id;
			}
		}

		$nextval_id = $max_id + 1;

		$this->ids[] = $nextval_id;

		return $nextval_id;
	}
}
