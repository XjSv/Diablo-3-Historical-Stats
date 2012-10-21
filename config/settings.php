<?php
// Database Name (All Required)
//
define('PROD_DB',      'DB NAME HERE');
define('PROD_DB_USER', 'USERNAME HERE');
define('PROD_DB_PASS', 'PASSWORD HERE');

// Database Collections DONT CHANGE
//
define('CAREER_COLLECTION',        'career');
define('HERO_COLLECTION',          'hero');
define('ITEM_COLLECTION',          'item');
define('APP_STATS_COLLECTION',     'app_stats');
define('APP_DATA_COLLECTION',      'app_data');
define('APP_USERS_COLLECTION',     'app_users');
define('APP_USER_FAVS_COLLECTION', 'app_user_favs');

// Application Settings (All Required)
//
define('HERO_HISTORY_LIMIT', 50);
define('HERO_GRAPH_LIMIT',   50);
define('APP_STATS_LIMIT',    1000);
define('APP_USERS_LIMIT',    15);
define('LOAD_USER_LIMIT',    50);
define('NOREPLY_EMAIL',      'no-reply@domain.com');
define('DEFAULT_EMAIL',      'YOUR EMAIL HERE');
define('GOOGLE_ANALYTICS',   'GOOGLE ANALYTICS HERE');
define('MAILER_URL',         'YOUR DOMAIN NAME'); // for mailer (e.g armandotresova.com)
define('BATTLENET_ACCOUNT',  'YOUR BATTLETAG HERE');
define('DEFAULT_SERVER',     'YOUR REGION HERE'); // us, eu, etc...
define('DEFAULT_LOCALE',     'en_US');

// Debug for load process
//
define('TEST_MODE',   false); // DONT SET TO TRUE
define('FULL_INSERT', false); // DONT SET TO TRUE
