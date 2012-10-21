<?php
session_start();
date_default_timezone_set('America/New_York');

require_once('class/diablo3.api.class.php');
require_once('config/settings.php');
require_once('include/functions.php');

unregisterGlobals();
removeMagicQuotes();

$bad_search_tag   = '';
$search_tag_added = '';
$GOOGLE_ANALYTICS = GOOGLE_ANALYTICS;

if(!isset($_SESSION['INITIALIZE']) || !$_SESSION['INITIALIZE']) {
   $_SESSION['ACTIVE_USER']      = '';
   $_SESSION['ACTIVE_HERO_ID']   = '';
   $_SESSION['HISTORIC_HERO_ID'] = '';
   $_SESSION['ACTIVE_REGION']    = '';
   $_SESSION['INITIALIZE']       = true;
}

if(isset($_GET['user']) && $_GET['user'] != '') {
    $_SESSION['ACTIVE_USER'] = $_GET['user'];
}

if(isset($_GET['region']) && $_GET['region'] != '') {
    $_SESSION['ACTIVE_REGION'] = $_GET['region'];
}

if(isset($_GET['hero_id']) && $_GET['hero_id'] != '') {
    $_SESSION['ACTIVE_HERO_ID']   = (int)$_GET['hero_id'];
    $_SESSION['HISTORIC_HERO_ID'] = '';
}

if(isset($_GET['historic_hero_id']) && $_GET['historic_hero_id'] != '') {
    $_SESSION['HISTORIC_HERO_ID'] = $_GET['historic_hero_id'];
}

$connection = new Mongo();
$db         = $connection->selectDB(PROD_DB);
$db->authenticate(PROD_DB_USER, PROD_DB_PASS);

// Setup collections
//
$career_collection        = $db->selectCollection(CAREER_COLLECTION);
$hero_collection          = $db->selectCollection(HERO_COLLECTION);
$item_collection          = $db->selectCollection(ITEM_COLLECTION);
$app_data_collection      = $db->selectCollection(APP_DATA_COLLECTION);
//$app_users_collection     = $db->selectCollection(APP_USERS_COLLECTION);
//$app_user_favs_collection = $db->selectCollection(APP_USER_FAVS_COLLECTION);

// Search function
//
if(isset($_POST['search_battletag'])){
    $search_results = $career_collection->find(array('battleTag' => $_POST['search_battletag'], '_region' => $_POST['search_region']))->sort(array('_id' => -1))->limit(1);

    // Set User and Region if we found anything
    //
    if($search_results->count() > 0) {
        $_SESSION['ACTIVE_USER']   = $_POST['search_battletag'];
        $_SESSION['ACTIVE_REGION'] = $_POST['search_region'];

        // Add data to APP_DATA collection (Incriment Search)
        //
        $app_data_results = $app_data_collection->find(array('battletag' => $_SESSION['ACTIVE_USER'], 'region' => $_SESSION['ACTIVE_REGION']))->sort(array('_id' => -1))->limit(1);
        if($app_data_results->count() > 0) {
            $app_data_collection->update(array("battletag" => $_SESSION['ACTIVE_USER']), array('$set' => array("last_viewd_date" => date('F j, Y, g:i:s A'))));
            $app_data_collection->update(array("battletag" => $_SESSION['ACTIVE_USER']), array('$inc' => array("searched_count" => 1)));
        }
    } else {
        // We dont have battle tag we need to add it (Validate it first)
        //
        $Diablo3     = new Diablo3($_POST['search_battletag'], $_POST['search_region'], 'en_US');
        $CAREER_DATA = $Diablo3->getCareer();
        if(is_array($CAREER_DATA)) {
            $app_data_insert = array('battletag'       => $_POST['search_battletag'],
                                     'region'          => $_POST['search_region'],
                                     'searched_count'  => 0,
                                     'views'           => 1,
                                     'last_viewd_date' => date('F j, Y, g:i:s A'));

            $app_data_collection->insert($app_data_insert);

            $no_reply      = NOREPLY_EMAIL;
            $default_email = DEFAULT_EMAIL;
            $MAILER_URL    = MAILER_URL;
            // Mail me when someone adds a new battletag
            //
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
            $headers .= "From: {$no_reply}\r\n";
            $headers .= "Reply-To: {$no_reply}\r\n";
            $headers .= "Return-Path: {$default_email}\r\n";
            $headers .= "X-Mailer: {$MAILER_URL} (http://www.{$MAILER_URL})";

            @mail($default_email, 'Diablo 3 Stats App - New BattleTag Added.', "BattleTag ".$_POST['search_battletag']." added to the DB.", $headers);

            $search_tag_added = '<div class="alert alert-success">
                                     <button type="button" class="close" data-dismiss="alert">×</button>
                                     <h4>Success!</h4>
                                     The BattleTag you added has just been added to queue for processing. Check back later.
                                 </div>';
        } else {
            // Not a valid battletag or region.
            //
            $bad_search_tag = '<div class="alert alert-error">
                                   <button type="button" class="close" data-dismiss="alert">×</button>
                                   <h4>Warning!</h4>
                                   The BattleTag and/or Region you entered is not valid.
                               </div>';
        }
    }
}

// Get top Viewd users
//
$top_users_results = $app_data_collection->find()->sort(array('views' => -1))->limit(APP_USERS_LIMIT);
$user_list = '<div class="navbar"><div class="navbar-inner"><div class="container"><a class="brand" href="#">Users</a><ul class="nav">';
foreach($top_users_results as $tu_key => $tu_value) {
    ($tu_value['battletag'] == $_SESSION['ACTIVE_USER']) ? $active = "class='active'" : $active = "";
    $user_list .= "<li {$active}><a href='?user=".urlencode($tu_value['battletag'])."&amp;region={$tu_value['region']}'>{$tu_value['battletag']}</a></li>";
}
$user_list .= '</ul></div></div></div>';


// Build User Hero List based on career data TODO change to get from APP_DATA
//
if($_SESSION['ACTIVE_USER'] != '' && $_SESSION['ACTIVE_REGION'] != '') {
    $careers = $career_collection->find(array('battleTag' => $_SESSION['ACTIVE_USER'], '_region' => $_SESSION['ACTIVE_REGION']))->sort(array('_id' => -1))->limit(1);
    if($careers->count() > 0) {
        // We have battle tag stored
        //
        $user_heros = '<div class="navbar"><div class="navbar-inner"><div class="container"><a class="brand" href="#">Heroes</a><ul class="nav">';
        foreach($careers as $key => $value) {
            foreach($value['heroes'] as $key3 => $value3) {
                if($value3['id'] == $_SESSION['ACTIVE_HERO_ID']) {
                    $active = "class='active '";
                } else {
                    $active = "";
                }

                ($value3['gender'] == 0) ? $gender = "male" : $gender = "female";
                $user_heros .= "<li {$active}><a href='?hero_id={$value3['id']}'>{$value3['name']} - {$value3['class']} - {$value3['level']}</a></li>";
            }
        }
        $user_heros .= '</ul></div></div></div>';

        // Add data to APP_DATA collection
        //
        $app_data_results2 = $app_data_collection->find(array('battletag' => $_SESSION['ACTIVE_USER'], 'region' => $_SESSION['ACTIVE_REGION']))->sort(array('_id' => -1))->limit(1);

        if($app_data_results2->count() > 0) {
            $app_data_collection->update(array("battletag" => $_SESSION['ACTIVE_USER']), array('$set' => array("last_viewd_date" => date('F j, Y, g:i:s A'))));
            $app_data_collection->update(array("battletag" => $_SESSION['ACTIVE_USER']), array('$inc' => array("views" => 1)));
        } else {
            // Since its a new collection we have many battletags that have not been inserted into APP_DATA. This should take care of it.
            //
            $app_data_insert = array('battletag'       => $_SESSION['ACTIVE_USER'],
                                     'region'          => $_SESSION['ACTIVE_REGION'],
                                     'searched_count'  => 0,
                                     'views'           => 1,
                                     'last_viewd_date' => date('F j, Y, g:i:s A'));

            $app_data_collection->insert($app_data_insert);
        }
    }
} else {
    $user_heros = '';
}

// Build Hero Details
//
if(!empty($_SESSION['ACTIVE_HERO_ID']) || !empty($_SESSION['HISTORIC_HERO_ID'])) {
    $user_history_list   = '';
    $user_stats_list     = '<table class="table table-bordered">';
    $data_string         = '';
    $last_updated_string = '';
    $items               = "<ul class='gear-slots'>";

    $hero_history       = $hero_collection->find(array('id' => $_SESSION['ACTIVE_HERO_ID']))->sort(array('_id' => -1))->limit(HERO_HISTORY_LIMIT);
    $hero_history_chart = $hero_collection->find(array('id' => $_SESSION['ACTIVE_HERO_ID']))->sort(array('_id' => 1))->limit(HERO_GRAPH_LIMIT);

    if(!empty($_SESSION['HISTORIC_HERO_ID'])) {
        $hero_stats = $hero_collection->find(array('_id' => new MongoId($_SESSION['HISTORIC_HERO_ID']), 'id' => $_SESSION['ACTIVE_HERO_ID']))->limit(1);
    } else {
        $hero_stats = $hero_collection->find(array('id' => $_SESSION['ACTIVE_HERO_ID']))->sort(array('_id' => -1))->limit(1);
    }

    $i = 1;
    foreach($hero_history_chart as $key => $value) {
        $life_array[]         = $value['stats']['life'];
        $armor_array[]        = $value['stats']['armor'];
        $damage_array[]       = $value['stats']['damage'];
        $strength_array[]     = $value['stats']['strength'];
        $dexterity_array[]    = $value['stats']['dexterity'];
        $vitality_array[]     = $value['stats']['vitality'];
        $intelligence_array[] = $value['stats']['intelligence'];
        $last_updated_array[] = "'".$i." - ".date('n/j/y', $value['last-updated'])."'";
        $i++;
    }

    $i = $i - 1;
    foreach($hero_history as $key => $value) {
        $date               = date('F j, Y, g:i:s A', $value['last-updated']);
        $date_id            = date('n/j/y', $value['last-updated']);
        $user_history_list .= "<li><a rel='".$i." - {$date_id}' href='?historic_hero_id={$value['_id']}'>{$date} Level: {$value['level']}</a></li>";
        $i--;
    }

    foreach($hero_stats as $key2 => $value2) {
        // Set gender and class for paperdoll
        //
        $_SESSION['ACTIVE_CLASS']  = $value2['class'];
        ($value2['gender'] == 0) ? $gender = "male" : $gender = "female";
        $_SESSION['ACTIVE_GENDER'] = $gender;

        $resource = $value2['stats']['primaryResource'];
        if($value2['stats']['secondaryResource'] != 0) {
            $resource = $value2['stats']['primaryResource']. '/' .$value2['stats']['secondaryResource'];
        }

        $user_stats_list .= "<tr><td>Strength</td><td colspan='2'>{$value2['stats']['strength']}</td></tr>";
        $user_stats_list .= "<tr><td>Dexerity</td><td colspan='2'>{$value2['stats']['dexterity']}</td></tr>";
        $user_stats_list .= "<tr><td>Intelligence</td><td colspan='2'>{$value2['stats']['intelligence']}</td></tr>";
        $user_stats_list .= "<tr><td>Vitality</td><td colspan='2'>{$value2['stats']['vitality']}</td></tr>";
        $user_stats_list .= "<tr><td>Armor</td><td colspan='2'>{$value2['stats']['armor']}</td></tr>";
        $user_stats_list .= "<tr><td>Damage</td><td colspan='2'>{$value2['stats']['damage']}</td></tr>";
        $user_stats_list .= "<tr><td>Life</td><td colspan='2'>{$value2['stats']['life']}</td></tr>";
        $user_stats_list .= "<tr><td>Resource</td><td colspan='2'>{$resource}</td></tr>";

        // Active Skills
        //
        $skill_a_1 = '';
        if(isset($value2['skills']['active'][0]['skill']['icon'])) {
            $skill_a_1 = $value2['skills']['active'][0]['skill']['icon'];
        }
        $skill_a_2 = '';
        if(isset($value2['skills']['active'][1]['skill']['icon'])) {
            $skill_a_2 = $value2['skills']['active'][1]['skill']['icon'];
        }
        $skill_a_3 = '';
        if(isset($value2['skills']['active'][2]['skill']['icon'])) {
            $skill_a_3 = $value2['skills']['active'][2]['skill']['icon'];
        }
        $skill_a_4 = '';
        if(isset($value2['skills']['active'][3]['skill']['icon'])) {
            $skill_a_4 = $value2['skills']['active'][3]['skill']['icon'];
        }
        $skill_a_5 = '';
        if(isset($value2['skills']['active'][4]['skill']['icon'])) {
            $skill_a_5 = $value2['skills']['active'][4]['skill']['icon'];
        }
        $skill_a_6 = '';
        if(isset($value2['skills']['active'][5]['skill']['icon'])) {
            $skill_a_6 = $value2['skills']['active'][5]['skill']['icon'];
        }

        // Passive Skills
        //
        $skill_p_1 = '';
        if(isset($value2['skills']['passive'][0]['skill']['icon'])) {
            $skill_p_1 = $value2['skills']['passive'][0]['skill']['icon'];
        }
        $skill_p_2 = '';
        if(isset($value2['skills']['passive'][1]['skill']['icon'])) {
            $skill_p_2 = $value2['skills']['passive'][1]['skill']['icon'];
        }
        $skill_p_3 = '';
        if(isset($value2['skills']['passive'][2]['skill']['icon'])) {
            $skill_p_3 = $value2['skills']['passive'][2]['skill']['icon'];
        }

        $user_stats_list .= "<tr>";
        $user_stats_list .= "<td><img src='img/skills/21/{$skill_p_1}.png'></td>";
        $user_stats_list .= "<td><img src='img/skills/21/{$skill_p_2}.png'></td>";
        $user_stats_list .= "<td><img src='img/skills/21/{$skill_p_3}.png'></td>";
        $user_stats_list .= "</tr>";

        $user_stats_list .= "<tr><td><img src='img/skills/21/{$skill_a_1}.png'></td><td><img src='img/skills/21/{$skill_a_2}.png'></td></tr>";
        $user_stats_list .= "<tr><td><img src='img/skills/21/{$skill_a_3}.png'></td><td><img src='img/skills/21/{$skill_a_4}.png'></td></tr>";
        $user_stats_list .= "<tr><td><img src='img/skills/21/{$skill_a_5}.png'></td><td><img src='img/skills/21/{$skill_a_6}.png'></td></tr>";

        $item_head_stats        = (isset($value2['items']['head']['tooltipParams']))        ? $item_collection->find(array('tooltipParams' => $value2['items']['head']['tooltipParams']))->limit(1)        : false;
        $item_torso_stats       = (isset($value2['items']['torso']['tooltipParams']))       ? $item_collection->find(array('tooltipParams' => $value2['items']['torso']['tooltipParams']))->limit(1)       : false;
        $item_feet_stats        = (isset($value2['items']['feet']['tooltipParams']))        ? $item_collection->find(array('tooltipParams' => $value2['items']['feet']['tooltipParams']))->limit(1)        : false;
        $item_hands_stats       = (isset($value2['items']['hands']['tooltipParams']))       ? $item_collection->find(array('tooltipParams' => $value2['items']['hands']['tooltipParams']))->limit(1)       : false;
        $item_shoulders_stats   = (isset($value2['items']['shoulders']['tooltipParams']))   ? $item_collection->find(array('tooltipParams' => $value2['items']['shoulders']['tooltipParams']))->limit(1)   : false;
        $item_legs_stats        = (isset($value2['items']['legs']['tooltipParams']))        ? $item_collection->find(array('tooltipParams' => $value2['items']['legs']['tooltipParams']))->limit(1)        : false;
        $item_bracers_stats     = (isset($value2['items']['bracers']['tooltipParams']))     ? $item_collection->find(array('tooltipParams' => $value2['items']['bracers']['tooltipParams']))->limit(1)     : false;
        $item_mainHand_stats    = (isset($value2['items']['mainHand']['tooltipParams']))    ? $item_collection->find(array('tooltipParams' => $value2['items']['mainHand']['tooltipParams']))->limit(1)    : false;
        $item_offHand_stats     = (isset($value2['items']['offHand']['tooltipParams']))     ? $item_collection->find(array('tooltipParams' => $value2['items']['offHand']['tooltipParams']))->limit(1)     : false;
        $item_waist_stats       = (isset($value2['items']['waist']['tooltipParams']))       ? $item_collection->find(array('tooltipParams' => $value2['items']['waist']['tooltipParams']))->limit(1)       : false;
        $item_rightFinger_stats = (isset($value2['items']['rightFinger']['tooltipParams'])) ? $item_collection->find(array('tooltipParams' => $value2['items']['rightFinger']['tooltipParams']))->limit(1) : false;
        $item_leftFinger_stats  = (isset($value2['items']['leftFinger']['tooltipParams']))  ? $item_collection->find(array('tooltipParams' => $value2['items']['leftFinger']['tooltipParams']))->limit(1)  : false;
        $item_neck_stats        = (isset($value2['items']['neck']['tooltipParams']))        ? $item_collection->find(array('tooltipParams' => $value2['items']['neck']['tooltipParams']))->limit(1)        : false;

        $socket_head = "";
        if($item_head_stats !== false) {
            foreach($item_head_stats as $value4) {
                if(count($value4['gems']) > 0) {
                    $socket_head = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value4['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_torso = "";
        if($item_torso_stats !== false) {
            foreach($item_torso_stats as $value5) {
                if(count($value5['gems']) > 0) {
                    $socket_torso = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value5['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_feet = "";
        if($item_feet_stats !== false) {
            foreach($item_feet_stats as $value6) {
                if(count($value6['gems']) > 0) {
                    $socket_feet = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value6['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_hands = "";
        if($item_hands_stats !== false) {
            foreach($item_hands_stats as $value7) {
                if(count($value7['gems']) > 0) {
                    $socket_hands = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value7['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_shoulders = "";
        if($item_shoulders_stats !== false) {
            foreach($item_shoulders_stats as $value8) {
                if(count($value8['gems']) > 0) {
                    $socket_shoulders = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value8['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_legs = "";
        if($item_legs_stats !== false) {
            foreach($item_legs_stats as $value9) {
                if(count($value9['gems']) > 0) {
                    $socket_legs = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value9['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_bracers = "";
        if($item_bracers_stats !== false) {
            foreach($item_bracers_stats as $value10) {
                if(count($value10['gems']) > 0) {
                    $socket_bracers = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value10['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_mainHand = "";
        if($item_mainHand_stats !== false) {
            foreach($item_mainHand_stats as $value11) {
                if(count($value11['gems']) > 0) {
                    $socket_mainHand = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value11['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_offHand = "";
        if($item_offHand_stats !== false) {
            foreach($item_offHand_stats as $value12) {
                if(count($value12['gems']) > 0) {
                    $socket_offHand = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value12['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_waist = "";
        if($item_waist_stats !== false) {
            foreach($item_waist_stats as $value13) {
                if(count($value13['gems']) > 0) {
                    $socket_waist = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value13['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_rightFinger = "";
        if($item_rightFinger_stats !== false) {
            foreach($item_rightFinger_stats as $value14) {
                if(count($value14['gems']) > 0) {
                    $socket_rightFinger = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value14['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_leftFinger = "";
        if($item_leftFinger_stats !== false) {
            foreach($item_leftFinger_stats as $value15) {
                if(count($value15['gems']) > 0) {
                    $socket_leftFinger = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value15['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }
        $socket_neck = "";
        if($item_neck_stats !== false) {
            foreach($item_neck_stats as $value16) {
                if(count($value16['gems']) > 0) {
                    $socket_neck = "<span class='sockets-wrapper'>
                                        <span class='sockets-align'>
                                            <span class='socket'>
                                                <img class='gem' src='http://us.media.blizzard.com/d3/icons/items/small/{$value16['gems'][0]['item']['icon']}.png'>
                                            </span>
                                            <br>
                                        </span>
                                    </span>";
                }
            }
        }

        $items .= (isset($value2['items']['head']['tooltipParams']))        ? "<li class='slot-head'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['head']['tooltipParams']}' data-d3tooltip='{$value2['items']['head']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['head']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['head']['icon']}.png' alt=''></span>{$socket_head}</a></li>" : '';
        $items .= (isset($value2['items']['torso']['tooltipParams']))       ? "<li class='slot-torso'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['torso']['tooltipParams']}' data-d3tooltip='{$value2['items']['torso']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['torso']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['torso']['icon']}.png' alt=''></span>{$socket_torso}</a></li>" : '';
        $items .= (isset($value2['items']['feet']['tooltipParams']))        ? "<li class='slot-feet'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['feet']['tooltipParams']}' data-d3tooltip='{$value2['items']['feet']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['feet']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['feet']['icon']}.png' alt=''></span>{$socket_feet}</a></li>" : '';
        $items .= (isset($value2['items']['hands']['tooltipParams']))       ? "<li class='slot-hands'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['hands']['tooltipParams']}' data-d3tooltip='{$value2['items']['hands']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['hands']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['hands']['icon']}.png' alt=''></span>{$socket_hands}</a></li>" : '';
        $items .= (isset($value2['items']['shoulders']['tooltipParams']))   ? "<li class='slot-shoulders'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['shoulders']['tooltipParams']}' data-d3tooltip='{$value2['items']['shoulders']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['shoulders']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['shoulders']['icon']}.png' alt=''></span>{$socket_shoulders}</a></li>" : '';
        $items .= (isset($value2['items']['legs']['tooltipParams']))        ? "<li class='slot-legs'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['legs']['tooltipParams']}' data-d3tooltip='{$value2['items']['legs']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['legs']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['legs']['icon']}.png' alt=''></span>{$socket_legs}</a></li>" : '';
        $items .= (isset($value2['items']['bracers']['tooltipParams']))     ? "<li class='slot-bracers'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['bracers']['tooltipParams']}' data-d3tooltip='{$value2['items']['bracers']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['bracers']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['bracers']['icon']}.png' alt=''></span>{$socket_bracers}</a></li>" : '';
        $items .= (isset($value2['items']['mainHand']['tooltipParams']))    ? "<li class='slot-mainHand'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['mainHand']['tooltipParams']}' data-d3tooltip='{$value2['items']['mainHand']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['mainHand']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['mainHand']['icon']}.png' alt=''></span>{$socket_mainHand}</a></li>" : '';
        $items .= (isset($value2['items']['offHand']['tooltipParams']))     ? "<li class='slot-offHand'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['offHand']['tooltipParams']}' data-d3tooltip='{$value2['items']['offHand']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['offHand']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['offHand']['icon']}.png' alt=''></span>{$socket_offHand}</a></li>" : '';
        $items .= (isset($value2['items']['waist']['tooltipParams']))       ? "<li class='slot-waist'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['waist']['tooltipParams']}' data-d3tooltip='{$value2['items']['waist']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['waist']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['waist']['icon']}.png' alt=''></span>{$socket_waist}</a></li>" : '';
        $items .= (isset($value2['items']['rightFinger']['tooltipParams'])) ? "<li class='slot-rightFinger'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['rightFinger']['tooltipParams']}' data-d3tooltip='{$value2['items']['rightFinger']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['rightFinger']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['rightFinger']['icon']}.png' alt=''></span>{$socket_rightFinger}</a></li>" : '';
        $items .= (isset($value2['items']['leftFinger']['tooltipParams']))  ? "<li class='slot-leftFinger'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['leftFinger']['tooltipParams']}' data-d3tooltip='{$value2['items']['leftFinger']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['leftFinger']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['leftFinger']['icon']}.png' alt=''></span>{$socket_leftFinger}</a></li>" : '';
        $items .= (isset($value2['items']['neck']['tooltipParams']))        ? "<li class='slot-neck'><a rel='getItemToolTip.php?tooltipUrl={$value2['items']['neck']['tooltipParams']}' data-d3tooltip='{$value2['items']['neck']['tooltipParams']}' class='slot-link' href='javascript:void(0);'><span class='d3-icon d3-icon-item d3-icon-item-{$value2['items']['neck']['displayColor']}'><span class='icon-item-gradient'><span class='icon-item-inner'></span></span></span><span class='image'><img src='img/items/large/{$value2['items']['neck']['icon']}.png' alt=''></span>{$socket_neck}</a></li>" : '';
    }

    $data_string .= '{';
    $data_string .= 'name: \'Life\',';
    $data_string .= 'data: ['.implode(', ', $life_array).']';
    $data_string .= '},';

    $data_string .= '{';
    $data_string .= 'name: \'Armor\',';
    $data_string .= 'data: ['.implode(', ', $armor_array).']';
    $data_string .= '},';

    $data_string .= '{';
    $data_string .= 'name: \'Damage\',';
    $data_string .= 'data: ['.implode(', ', $damage_array).']';
    $data_string .= '},';

    $data_string .= '{';
    $data_string .= 'name: \'Strength\',';
    $data_string .= 'data: ['.implode(', ', $strength_array).']';
    $data_string .= '},';

    $data_string .= '{';
    $data_string .= 'name: \'Dexterity\',';
    $data_string .= 'data: ['.implode(', ', $dexterity_array).']';
    $data_string .= '},';

    $data_string .= '{';
    $data_string .= 'name: \'Vitality\',';
    $data_string .= 'data: ['.implode(', ', $vitality_array).']';
    $data_string .= '},';

    $data_string .= '{';
    $data_string .= 'name: \'Intelligence\',';
    $data_string .= 'data: ['.implode(', ', $intelligence_array).']';
    $data_string .= '},';

    $last_updated_string .= implode(', ', $last_updated_array);
    $data_string          = substr($data_string, 0, -1);
    $user_stats_list     .= '</table>';
} else {
    $user_history_list   = '';
    $user_stats_list     = '';
    $data_string         = '';
    $last_updated_string = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Diablo 3 Historical Statistics</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Diablo 3 Historical Statistics">
<meta name="author" content="Armando Tresova <xjsv24@gmail.com>">
<link href="css/bootstrap.css" rel="stylesheet">
<link href="css/common.css?v42" rel="stylesheet">
<link href="css/d3.css?v53" rel="stylesheet">
<link href="css/tooltips.css?v53" rel="stylesheet">
<link href="css/shared.css?v53" rel="stylesheet">
<link href="css/hero.css?v53" rel="stylesheet">
<link href="css/hero-slots.css?v53" rel="stylesheet">
<!--<link href="css/jquery.cluetip.css?v1.2.6" rel="stylesheet">-->
<link href="css/styles.css?v1" rel="stylesheet">
<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>
<div class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container-fluid">
            <a class="brand" href="javascript:void(0)">Diablo 3 Historical Statistics</a>
            <div class="nav-collapse collapse">
                <ul class="nav">
                  <li class="active">
                    <a class="" href="/index.php">Home</a>
                  </li>
                  <li class="">
                    <a class="" href="/app_stats.php">App Stats</a>
                  </li>
                  <li class="">
                    <a class="" href="/change_log.php">Change Log</a>
                  </li>
                  <li class="">
                    <a class="" href="/contact.php">Contact</a>
                  </li>
                </ul>
            </div>

            <form method="post" id="search_battletags" name="search_battletags" class="navbar-search pull-right form-search">
                <div class="input-append search-override">
                    <input type="text" name="search_battletag" id="search_battletag" placeholder="Search/Add BattleTag" class="span2 search-query">
                    <select name="search_region" id="search_region" class="span1 search-query">
                        <option value="us">US</option>
                        <option value="eu">EU</option>
                        <option value="tw">TW</option>
                        <option value="kr">KR</option>
                        <option value="cn">CN</option>
                    </select>
                    <button type="submit" class="btn btn-inverse">Search</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container-fluid">
    <?=$bad_search_tag?>
    <?=$search_tag_added?>
    <?=$user_list?>
    <?=$user_heros?>
    <div class="row-fluid">
        <div class="span3">
            <div class="well sidebar-nav">
                <ul class="nav nav-list">
                    <li class="nav-header">Character History</li>
                    <?=$user_history_list?>
                </ul>
            </div>
        </div>

        <div class="span9 character-info">
            <div class="row-fluid">
                <div class="span3">
                    <div class="well well-small">
                    <?=$user_stats_list?>
                    </div>
                </div>
                <div class="span9">
                    <div class="paperdoll-sheet" style="background: url(img/paperdolls/<?=$_SESSION['ACTIVE_CLASS']?>-<?=$_SESSION['ACTIVE_GENDER']?>.jpg) no-repeat 0 0 transparent;">
                        <div id="paperdoll" class="paperdoll"> <!--class="inventory-lines"-->
                        <?=$items?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="row-fluid">
            <div id="graph"></div>
        </div>
    </div>

    <hr>

    <footer>
        <p>&copy; Diablo 3 Historical Statistics</p>
        <p>This application or armandotresova.com is no way affiliated or endorsed by Blizzard Entertainment&#174;. All artwork related to the game and all other copyrighted content related to Diablo&#174; III is property of Blizzard Entertainment&#174;, Inc.</p>
    </footer>
</div>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/highcharts.js"></script>
<script src="js/themes/grid.js"></script>
<script src="js/jquery.hoverIntent.js"></script>
<script src="js/jquery.cluetip.min.js"></script>
<script>
var statsChart;
$(document).ready(function() {
    // clueTip listner for item tooltips
    //
    $("a.slot-link").cluetip({
        closePosition : "title",
        sticky        : false,
        mouseOutClose : true,
        width         : 355,
        height        : 'auto',
        activation    : 'hover',
        showTitle     : false,
        cluetipClass  : 'd3-tooltip-wrapper d3-tooltip-wrapper-inner',
        tracking      : false,
        hoverIntent: {
            sensitivity : 3,
            interval    : 50,
            timeout     : 0
        },
        ajaxSettings  : {
            type     : "GET",
            dataType : 'html'
        },
        ajaxProcess : function(data) {
            return data;
        },
        onShow : function() {
        }
    });

    // Highcharts
    //
    statsChart = new Highcharts.Chart({
       chart: {
          renderTo: 'graph',
          type: 'line'
       },
       title: {
          text: 'Main Stats'
       },
       xAxis: {
          categories: [<?=$last_updated_string?>]
       },
       yAxis: {
          title: {
             text: 'Amount'
          }
       },
       plotOptions: {
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function() {
                            var href = $(".nav-list li a[rel='"+this.category+"']").attr('href');
                            window.location = href;
                        }
                    }
                }
            }
        },
       series: [<?=$data_string?>]
    });
});
</script>
<script>
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?=$GOOGLE_ANALYTICS?>']);
  _gaq.push(['_trackPageview']);
  _gaq.push(['_trackPageLoadTime']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</body>
</html>
