<?php
/**
 *
 * Get all Diablo 3 item images.
 *
 **/

$time  = microtime();
$time  = explode(' ', $time);
$time  = $time[1] + $time[0];
$start = $time;

require_once('../class/diablo3.api.class.php');
require_once('../config/settings.php');
require_once('../include/functions.php');

date_default_timezone_set('America/New_York');
set_time_limit(0);
ini_set('memory_limit', '256M');

unregisterGlobals();
removeMagicQuotes();

$Diablo3    = new Diablo3(BATTLENET_ACCOUNT, DEFAULT_SERVER, DEFAULT_LOCALE);
$connection = new Mongo();
$db         = $connection->selectDB(PROD_DB);
$db->authenticate(PROD_DB_USER, PROD_DB_PASS);
$all_items  = $db->command(array("distinct" => ITEM_COLLECTION, "key" => "icon"));
$count      = 0;

// Download Small Images
//
foreach($all_items['values'] as $item) {
    $count++;
    $Diablo3->getItemImage($item, 'small');
}

// Download Large Images
//
foreach($all_items['values'] as $item) {
    $count++;
    $Diablo3->getItemImage($item, 'large');
}

$time       = microtime();
$time       = explode(' ', $time);
$time       = $time[1] + $time[0];
$finish     = $time;
$total_time = round(($finish - $start), 4);
$total_time = secondsToTime($total_time);

echo '<br>Proccess finished in '.$total_time.' seconds. '.$count." Images Saved<br>";
