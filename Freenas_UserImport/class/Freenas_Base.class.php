<?php
/**
Freenas_Base.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("LogFileMaker.class.php");

class Freenas_Base {
    private $log;

    protected function __construct() {
        $this->log = LogFileMaker::getInstance();
    }

    protected function writeLog($msg, $level) {
        $this->log->writeLog($msg, $level);
    }
}
