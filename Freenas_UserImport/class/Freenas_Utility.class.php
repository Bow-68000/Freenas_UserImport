<?php
/**
Freenas_Utility.class.php

Copyright (c) 2019 Bow.68000

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

class Freenas_Utility {
    static public function isNum($s) {
        return preg_match('/^\d+$/', $s);
    }

    static public function is01($s) {
        return preg_match('/^(0|1)$/', $s);
    }

    static public function isUsername($s) {
        return preg_match('/^[a-z_][a-zA-Z0-9_-]+$/', $s);
    }

    static public function isPassword($s) {
        return preg_match('/^[a-zA-Z0-9]+$/', $s);
    }

    static public function isHome($s) {
        return preg_match('/^\/[a-zA-Z\/][a-zA-Z\/_-]+$/', $s);
    }

    static public function isShell($s) {
        return Freenas_Utility::isHome($s);
    }

    static public function isEmail($s) {
        return preg_match('/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/', $s);
    }
}
