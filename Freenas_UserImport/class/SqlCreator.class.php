<?php
/**
SqlCreator.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

class SqlCreator {
	private $table;
	private $key;
	private $col;
	private $all;

	function __construct() {
	}

	private function createWhere() {
		$where = array();
		foreach($this->key as $c => $v) {
			$where[] = $c . "=" . $v;
		}
		return implode(" AND ", $where);
	}

	public function setTable($table) {
		$this->table = $table;
		$this->key = array();
		$this->col = array();
		$this->all = array();
	}

	public function addKey($col, $val) {
		$this->all[$col] = $val;
		$this->key[$col] = $val;
	}

	public function addCol($col, $val) {
		$this->all[$col] = $val;
		$this->col[$col] = $val;
	}

	public function addIns($col, $val) {
		$this->all[$col] = $val;
	}

	public function insert() {
		$sql = "INSERT INTO " . $this->table
		     . " ("
		     . implode(",", array_keys($this->all))
		     . ") VALUES ("
		     . implode(",", array_values($this->all))
		     . ")";
		return $sql;
	}

	public function update() {
		$set = array();
		foreach($this->col as $c => $v) {
			$set[] = $c . "=" . $v;
		}

		$sql = "UPDATE " . $this->table . " SET " . implode(",", $set) . " WHERE " . $this->createWhere();

		return $sql;
	}

	public function delete() {
		$set = array();
		foreach($this->col as $c => $v) {
			$set[] = $c . "=" . $v;
		}

		$sql = "DELETE FROM " . $this->table . " WHERE " . $this->createWhere();

		return $sql;
	}

	public function dbid($s) {
		return $s;
	}

	public function dbstr($s) {
		return "'" . $s . "'";
	}

	public function dbcsh($s) {
		$s = str_replace('$', "\"'\$'\"", $s);
		return $this->dbstr($s);
	}
}
