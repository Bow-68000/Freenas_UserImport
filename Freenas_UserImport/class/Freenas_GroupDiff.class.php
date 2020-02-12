<?php
/**
Freenas_GroupDiff.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

class Freenas_GroupDiff extends Freenas_Base {
	private $before_group_ids;
	private $after_group_ids;

	public function __construct() {
		parent::__construct();
	}

	public function setGroups($after_group_ids, $before_group_ids) {
		$this->after_group_ids = $after_group_ids;
		$this->before_group_ids = $before_group_ids;
	}

	private function existsBeforeGroupId($after_group_id) {
		foreach($this->before_group_ids as $before_group_id) {
			if ($after_group_id == $before_group_id) {
				return true;
			}
		}

		return false;
	}

	private function existsAfterGroupId($before_group_id) {
		foreach($this->after_group_ids as $after_group_id) {
			if ($after_group_id == $before_group_id) {
				return true;
			}
		}

		return false;
	}

	public function getInsertGroupIds() {
		$insert_group_ids = array();
		foreach($this->after_group_ids as $after_group_id) {
			if (!$this->existsBeforeGroupId($after_group_id)) {
				$insert_group_ids[] = $after_group_id;
			}
		}

		return $insert_group_ids;
	}

	public function getDeleteGroupIds() {
		$delete_group_ids = array();
		foreach($this->before_group_ids as $before_group_id) {
			if (!$this->existsAfterGroupId($before_group_id)) {
				$delete_group_ids[] = $before_group_id;
			}
		}

		return $delete_group_ids;
	}
}
