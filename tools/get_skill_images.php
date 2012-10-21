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

$Diablo3         = new Diablo3(BATTLENET_ACCOUNT, DEFAULT_SERVER, DEFAULT_LOCALE);
$connection      = new Mongo();
$db              = $connection->selectDB(PROD_DB);
$db->authenticate(PROD_DB_USER, PROD_DB_PASS);
$hero_collection = $db->selectCollection(HERO_COLLECTION);
$all_heros       = $hero_collection->find();
$count           = 0;
$skills          = array();

// Build Skills List
//
foreach($all_heros as $hero) {
    foreach($hero['skills']['active'] as $key => $value) {
        if(isset($value['skill']['icon']) && !empty($value['skill']['icon'])) {
            $skills[$value['skill']['icon']] = 1;
        }
    }

    foreach($hero['skills']['passive'] as $key => $value) {
        if(isset($value['skill']['icon']) && !empty($value['skill']['icon'])) {
            $skills[$value['skill']['icon']] = 1;
        }
    }
}

// Download Size 21px x 21px Images
//
foreach($skills as $key => $value) {
    $count++;
    $Diablo3->getSkillImage($key, '21');
}

// Download Size 42px x 42px Images
//
foreach($skills as $key => $value) {
    $count++;
    $Diablo3->getSkillImage($key, '42');
}

// Download Size 64px x 64px Images
//
foreach($skills as $key => $value) {
    $count++;
    $Diablo3->getSkillImage($key, '64');
}

$time       = microtime();
$time       = explode(' ', $time);
$time       = $time[1] + $time[0];
$finish     = $time;
$total_time = round(($finish - $start), 4);
$total_time = secondsToTime($total_time);

echo '<br>Proccess finished in '.$total_time.' seconds. '.$count." Images Saved<br>";
