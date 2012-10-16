<?php
// Database Name (All Required)
//
define('PROD_DB', 'git_diablo');
define('DEV_DB',  'git_dev_diablo');

// Database Collections (All Required)
//
define('CAREER_COLLECTION',    'career');
define('HERO_COLLECTION',      'hero');
define('ITEM_COLLECTION',      'item');
define('APP_STATS_COLLECTION', 'app_stats');

// Application Settings (All Required)
//
define('HERO_HISTORY_LIMIT', 50);
define('HERO_GRAPH_LIMIT',   50);
define('APP_STATS_LIMIT',    1000);
define('BATTLENET_ACCOUNT',  'XjSv#1677');
define('DEFAULT_SERVER',     'us');
define('DEFAULT_LOCALE',     'en_US');

// Debug
//
define('TEST_MODE',   false);
define('FULL_INSERT', false);

$user_profiles     = array(array("us" => "XjSv#1677"));
$dev_user_profiles = array(array("us" => "XjSv#1677"));