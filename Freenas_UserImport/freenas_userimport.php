<?php
/**
freenas_userimport.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

include_once("class/Ssh2.class.php");
include_once("class/Freenas_Import.class.php");

if (count($argv) != 4 && count($argv) != 5) {
    print "Usage: php freenas_userimport.php server root_password csv_filename [log filename]\n";
    exit();
}

$user = "root";
$server = $argv[1];
$password = $argv[2];
$csv_filename = $argv[3];

if (!file_exists($csv_filename)) {
    print $csv_filename . " not found.\n";
    exit();
}

$log = LogFileMaker::getInstance();
if (count($argv) == 5) {
    $log->open($argv[4]);
}
$log->setLevel(LogFileMaker::NORMAL);
$log->setLevel(LogFileMaker::WARNING);
$log->setLevel(LogFileMaker::ERROR);
//$log->setLevel(LogFileMaker::DEBUG);

if (count($argv) == 5) {
    $log->open($argv[4]);
}

$connect = array(
    $user,
    $password,
    $server
);

try {
    $ssh = new Ssh2($connect);
} catch (Exception $e) {
    print $e->getMessage() . "\n";
    exit();
}
$freenas_import = new Freenas_Import($ssh);

try {
    $freenas_import->importCsvFile($csv_filename);
} catch(Exception $e) {
    $log->writeLog($e->getMessage(), LogFileMaker::ERROR);
}
