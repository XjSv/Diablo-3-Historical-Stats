<?php
function secondsToTime($seconds) {
    // extract hours
    $hours = floor($seconds / (60 * 60));

    // extract minutes
    $divisor_for_minutes = $seconds % (60 * 60);
    $minutes             = floor($divisor_for_minutes / 60);

    // extract the remaining seconds
    $divisor_for_seconds = $divisor_for_minutes % 60;
    $seconds             = ceil($divisor_for_seconds);

    // return the final array
    $obj = array("h" => (int)$hours,
                 "m" => (int)$minutes,
                 "s" => (int)$seconds);

    $time = implode(':', $obj);

    return $time;
}

function stripSlashesDeep($value) {
   $value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);
   return $value;
}

function removeMagicQuotes() {
   if(get_magic_quotes_gpc()) {
      $_GET    = stripSlashesDeep($_GET);
      $_POST   = stripSlashesDeep($_POST);
      $_COOKIE = stripSlashesDeep($_COOKIE);
   }
}

// Check register globals and remove them
//
function unregisterGlobals() {
    if(ini_get('register_globals')) {
        $array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

        foreach($array as $value) {
            foreach($GLOBALS[$value] as $key => $var) {
                if($var === $GLOBALS[$key]) {
                    unset($GLOBALS[$key]);
                }
            }
        }
    }
}
