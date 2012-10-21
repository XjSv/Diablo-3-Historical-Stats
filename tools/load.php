<?php
/**
 *
 * Get all Diablo 3 data. Includes Career, Heros & All Heros Items Data.
 *
 * 10,000 requests per day == 415 requests per hour == 6 requests per min
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

$source = 'manual';
if(isset($_GET['source']) && $_GET['source'] == 'auto') {
    $source = 'auto';
}

$test_mode     = TEST_MODE;
$full_insert   = FULL_INSERT;
$db_name       = PROD_DB;
$calls         = 0;
$session_ids   = array();

$connection           = new Mongo();
$db                   = $connection->selectDB($db_name);
$db->authenticate(PROD_DB_USER, PROD_DB_PASS);
$career_collection    = $db->selectCollection(CAREER_COLLECTION);
$hero_collection      = $db->selectCollection(HERO_COLLECTION);
$item_collection      = $db->selectCollection(ITEM_COLLECTION);
$app_stats_collection = $db->selectCollection(APP_STATS_COLLECTION);
$app_data_collection  = $db->selectCollection(APP_DATA_COLLECTION);

// Limit this somehow algorithmically. Due to API call # limit
//
$app_data_results = $app_data_collection->find()->sort(array('views' => -1))->limit(LOAD_USER_LIMIT);
if($app_data_results->count() > 0) {
    foreach($app_data_results as $app_k => $app_v) {
        $user_profiles[] = array($app_v['region'] => $app_v['battletag']);
    }
} else {
    $user_profiles = array();
}

if(!$test_mode) {
    $career_collection->ensureIndex(array("battleTag" => 1, "lastUpdated" => 1, "_region" => 1), array("unique" => 1, "dropDups" => 1));
    $hero_collection->ensureIndex(array("id" => 1, "last-updated" => 1), array("unique" => 1, "dropDups" => 1));
    $item_collection->ensureIndex(array("id" => 1, "tooltipParams" => 1), array("unique" => 1, "dropDups" => 1));
}

if(!empty($user_profiles)) {
    foreach($user_profiles as $item) {
        foreach($item as $region => $user) {
            $time2  = microtime();
            $time2  = explode(' ', $time2);
            $time2  = $time2[1] + $time2[0];
            $start2 = $time2;

            // GET CAREER
            //
            $Diablo3     = new Diablo3($user, $region, DEFAULT_LOCALE);  // Battle.net Tag. (e.g. 'XjSv#1677' or 'XjSv-1677') (string), Server: 'us', 'eu', etc. (string) [Optional, Defaults to 'us'], Locale: 'en', 'es', etc. (string)
            $CAREER_DATA = $Diablo3->getCareer();
            $calls++;

            if(is_array($CAREER_DATA)) {
                // MAKE HERO LIST
                //
                $hero_list = array();
                foreach($CAREER_DATA['heroes'] as $key => $value) {
                    $hero_list[] = $value['id'];
                }

                // GET HERO DATA
                //
                $HERO_DATA = array();
                foreach($hero_list as $key => $hero) {
                    $HERO_DATA[] = $Diablo3->getHero($hero); // Hero ID (int)
                    $calls++;
                }

                // Career
                //
                $last_career_insert = $career_collection->find(array('battleTag'=> $CAREER_DATA['battleTag'], '_region' => $region))->sort(array('_id' => -1))->limit(1);
                $last_career_date   = null;
                foreach($last_career_insert as $key => $value) {
                    $last_career_date = $value['lastUpdated'];
                }

                // INSERT CAREER IF (lastUpdated > $last_career_date)
                //
                if(!is_null($last_career_date)) {
                    if($full_insert || $CAREER_DATA['lastUpdated'] > $last_career_date) {
                       echo "Inserted 1 Career Record<br>";
                       $CAREER_DATA['_region'] = $region;
                       $career_collection->insert($CAREER_DATA);
                       $session_ids[] = $CAREER_DATA['_id'];
                    } else {
                        echo "Career: Last updated date is the same. No insert<br>";
                    }
                } else {
                    $CAREER_DATA['_region'] = $region;
                    $career_collection->insert($CAREER_DATA);
                    $session_ids[] = $CAREER_DATA['_id'];
                    echo "Career: No data found assuming new career. Insert done<br>";
                }

                // Hero
                //
                foreach($HERO_DATA as $key => $hero) {
                    if(is_array($hero)) {
                        // GET LAST HERO INSERT
                        //
                        $last_hero_insert = $hero_collection->find(array('id'=> $hero['id']))->sort(array('_id' => -1))->limit(1);
                        $last_hero_date   = null;
                        foreach($last_hero_insert as $key2 => $value2) {
                            $last_hero_date  = $value2['last-updated'];
                            $last_hero_class = $value2['class'];
                            $last_hero_level = $value2['level'];
                        }

                        // INSERT HERO IF (last-updated > $last_hero_date)
                        //
                        if(!is_null($last_hero_date)) {
                            if($full_insert || $hero['last-updated'] > $last_hero_date) {
                                echo $last_hero_class.' Level: '.$last_hero_level." Inserted<br>";
                                $hero_collection->insert($hero);
                                $session_ids[] = $hero['_id'];

                                // Items
                                //
                                $hero_items = array();
                                foreach($hero['items'] as $key3 => $value3) {
                                    // GET LAST ITEM INSERT BY tooltipParams
                                    //
                                    $last_item_insert  = $item_collection->find(array('tooltipParams'=> $value3['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                    $last_item_tooltip = null;
                                    foreach($last_item_insert as $item_key => $item_value) {
                                        $last_item_tooltip = $item_value['tooltipParams'];
                                    }

                                    // CREATE HERO ITEMS LIST
                                    //
                                    if($full_insert || is_null($last_item_tooltip)) {
                                        $hero_items[$hero['id']][$key3] = $value3['tooltipParams'];
                                    } else {
                                        echo "Item record already exists, left out<br>";
                                    }
                                }

                                // GET HERO ITEMS
                                //
                                if(($hero_items) > 0) {
                                    $ITEM_DATA = array();
                                    foreach($hero_items as $hero_id => $items) {
                                        if(is_array($items)) {
                                            foreach($items as $name => $tooltipParams) {
                                                $ITEM_DATA[$hero_id][$name] = $Diablo3->getItem($tooltipParams); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                                $calls++;
                                            }
                                        }
                                    }
                                }

                                // INSERT HERO ITEMS
                                //
                                if(isset($ITEM_DATA[$hero['id']])) {
                                    foreach($ITEM_DATA[$hero['id']] as $key4 => $value4) {
                                        if(is_array($value4)) {
                                            $value4['_itemType'] = $key4;
                                            $item_collection->insert($value4);
                                            $session_ids[] = $value4['_id'];
                                            echo "Hero Items Inserted<br>";
                                        } else {
                                            echo $value4;
                                        }

                                        if(is_array($value4['gems']) && count($value4['gems']) > 0) {
                                            foreach($value4['gems'] as $gems) {
                                                $last_gem_insert  = $item_collection->find(array('tooltipParams'=> $gems['item']['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                                $last_gem_tooltip = null;
                                                foreach($last_gem_insert as $item_key => $item_value) {
                                                    $last_gem_tooltip = $item_value['tooltipParams'];
                                                }

                                                if(is_null($last_gem_tooltip)) {
                                                    $GEMS_DATA = $Diablo3->getItem($gems['item']['tooltipParams']); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                                    $item_collection->insert($GEMS_DATA);
                                                    $session_ids[] = $GEMS_DATA['_id'];
                                                    echo "Item Gem Inserted<br>";
                                                } else {
                                                    echo 'Gem Already Exists No Insert<br>';
                                                }
                                            }
                                        }
                                    }
                                }

                                // FOLLOWER ITEMS templar
                                //
                                if(isset($hero['followers']['templar']['items'])) {
                                    $templar_items = array();
                                    foreach($hero['followers']['templar']['items'] as $key5 => $value5) {
                                        // GET LAST ITEM INSERT BY tooltipParams
                                        //
                                        $last_item_insert  = $item_collection->find(array('tooltipParams'=> $value5['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                        $last_item_tooltip = null;
                                        foreach($last_item_insert as $item_key => $item_value) {
                                            $last_item_tooltip = $item_value['tooltipParams'];
                                        }

                                        // CREATE HERO ITEMS LIST
                                        //
                                        if($full_insert || is_null($last_item_tooltip)) {
                                            $templar_items[$hero['id']][$key5] = $value5['tooltipParams'];
                                        } else {
                                            echo "Follower Item record already exists, left out<br>";
                                        }
                                    }

                                    // GET FOLLOWER ITEMS templar ITEMS
                                    //
                                    if(($templar_items) > 0) {
                                        $ITEM_DATA = array();
                                        foreach($templar_items as $hero_id => $items) {
                                            if(is_array($items)) {
                                                foreach($items as $name => $tooltipParams) {
                                                    $ITEM_DATA[$hero_id][$name] = $Diablo3->getItem($tooltipParams); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                                    $calls++;
                                                }
                                            }
                                        }
                                    }

                                    // INSERT FOLLOWER ITEMS templar
                                    //
                                    if(isset($ITEM_DATA[$hero['id']])) {
                                        foreach($ITEM_DATA[$hero['id']] as $key8 => $value8) {
                                            if(is_array($value8)) {
                                                $value8['_itemType'] = $key8;
                                                $item_collection->insert($value8);
                                                $session_ids[] = $value8['_id'];
                                                echo "Follower Hero Items Inserted<br>";
                                            } else {
                                                echo $value8;
                                            }

                                            if(is_array($value8['gems']) && count($value8['gems']) > 0) {
                                                foreach($value8['gems'] as $gems) {
                                                    $last_gem_insert  = $item_collection->find(array('tooltipParams'=> $gems['item']['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                                    $last_gem_tooltip = null;
                                                    foreach($last_gem_insert as $item_key => $item_value) {
                                                        $last_gem_tooltip = $item_value['tooltipParams'];
                                                    }

                                                    if(is_null($last_gem_tooltip)) {
                                                        $GEMS_DATA = $Diablo3->getItem($gems['item']['tooltipParams']); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                                        $item_collection->insert($GEMS_DATA);
                                                        $session_ids[] = $GEMS_DATA['_id'];
                                                        echo "Follower Item Gem Inserted<br>";
                                                    } else {
                                                        echo 'Follower Gem Already Exists No Insert<br>';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                // FOLLOWER ITEMS scoundrel
                                //
                                if(isset($hero['followers']['scoundrel']['items'])) {
                                    $scoundrel_items = array();
                                    foreach($hero['followers']['scoundrel']['items'] as $key6 => $value6) {
                                        // GET LAST ITEM INSERT BY tooltipParams
                                        //
                                        $last_item_insert  = $item_collection->find(array('tooltipParams'=> $value6['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                        $last_item_tooltip = null;
                                        foreach($last_item_insert as $item_key => $item_value) {
                                            $last_item_tooltip = $item_value['tooltipParams'];
                                        }

                                        // CREATE HERO ITEMS LIST
                                        //
                                        if($full_insert || is_null($last_item_tooltip)) {
                                            $scoundrel_items[$hero['id']][$key6] = $value6['tooltipParams'];
                                        } else {
                                            echo "Follower Item record already exists, left out<br>";
                                        }
                                    }

                                    // GET ITEMS scoundrel ITEMS
                                    //
                                    if(($templar_items) > 0) {
                                        $ITEM_DATA = array();
                                        foreach($templar_items as $hero_id => $items) {
                                            if(is_array($items)) {
                                                foreach($items as $name => $tooltipParams) {
                                                    $ITEM_DATA[$hero_id][$name] = $Diablo3->getItem($tooltipParams); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                                    $calls++;
                                                }
                                            }
                                        }
                                    }

                                    // INSERT ITEMS scoundrel
                                    //
                                    if(isset($ITEM_DATA[$hero['id']])) {
                                        foreach($ITEM_DATA[$hero['id']] as $key10 => $value10) {
                                            if(is_array($value10)) {
                                                $value10['_itemType'] = $key10;
                                                $item_collection->insert($value10);
                                                $session_ids[] = $value10['_id'];
                                                echo "Follower Hero Items Inserted<br>";
                                            } else {
                                                echo $value10;
                                            }

                                            if(is_array($value10['gems']) && count($value10['gems']) > 0) {
                                                foreach($value10['gems'] as $gems) {
                                                    $last_gem_insert  = $item_collection->find(array('tooltipParams'=> $gems['item']['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                                    $last_gem_tooltip = null;
                                                    foreach($last_gem_insert as $item_key => $item_value) {
                                                        $last_gem_tooltip = $item_value['tooltipParams'];
                                                    }

                                                    if(is_null($last_gem_tooltip)) {
                                                        $GEMS_DATA = $Diablo3->getItem($gems['item']['tooltipParams']); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                                        $item_collection->insert($GEMS_DATA);
                                                        $session_ids[] = $GEMS_DATA['_id'];
                                                        echo "Follower Item Gem Inserted<br>";
                                                    } else {
                                                        echo 'Follower Gem Already Exists No Insert<br>';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                // FOLLOWER ITEMS enchantress
                                //
                                if(isset($hero['followers']['enchantress']['items'])) {
                                    $enchantress_items = array();
                                    foreach($hero['followers']['enchantress']['items'] as $key7 => $value7) {
                                        // GET LAST ITEM INSERT BY tooltipParams
                                        //
                                        $last_item_insert  = $item_collection->find(array('tooltipParams'=> $value7['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                        $last_item_tooltip = null;
                                        foreach($last_item_insert as $item_key => $item_value) {
                                            $last_item_tooltip = $item_value['tooltipParams'];
                                        }

                                        // CREATE HERO ITEMS LIST
                                        //
                                        if($full_insert || is_null($last_item_tooltip)) {
                                            $enchantress_items[$hero['id']][$key7] = $value7['tooltipParams'];
                                        } else {
                                            echo "Follower Item record already exists, left out<br>";
                                        }
                                    }

                                    // GET ITEMS enchantress ITEMS
                                    //
                                    if(($enchantress_items) > 0) {
                                        $ITEM_DATA = array();
                                        foreach($enchantress_items as $hero_id => $items) {
                                            if(is_array($items)) {
                                                foreach($items as $name => $tooltipParams) {
                                                    $ITEM_DATA[$hero_id][$name] = $Diablo3->getItem($tooltipParams); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                                    $calls++;
                                                }
                                            }
                                        }
                                    }

                                    // INSERT ITEMS enchantress
                                    //
                                    if(isset($ITEM_DATA[$hero['id']])) {
                                        foreach($ITEM_DATA[$hero['id']] as $key9 => $value9) {
                                            if(is_array($value9)) {
                                                $value9['_itemType'] = $key9;
                                                $item_collection->insert($value9);
                                                $session_ids[] = $value9['_id'];
                                                echo "Follower Hero Items Inserted<br>";
                                            } else {
                                                echo $value9;
                                            }

                                            if(is_array($value9['gems']) && count($value9['gems']) > 0) {
                                                foreach($value9['gems'] as $gems) {
                                                    $last_gem_insert  = $item_collection->find(array('tooltipParams'=> $gems['item']['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                                    $last_gem_tooltip = null;
                                                    foreach($last_gem_insert as $item_key => $item_value) {
                                                        $last_gem_tooltip = $item_value['tooltipParams'];
                                                    }

                                                    if(is_null($last_gem_tooltip)) {
                                                        $GEMS_DATA = $Diablo3->getItem($gems['item']['tooltipParams']); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                                        $item_collection->insert($GEMS_DATA);
                                                        $session_ids[] = $GEMS_DATA['_id'];
                                                        echo "Follower Item Gem Inserted<br>";
                                                    } else {
                                                        echo 'Follower Gem Already Exists No Insert<br>';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
                                echo "Last update date same for ".$last_hero_class.' Level: '.$last_hero_level." No Insert<br>";
                            }
                        } else {
                            $hero_collection->insert($hero);
                            echo "No hero data found, assuming new hero. Insert done<br>";

                            // Items
                            //
                            $hero_items = array();
                            foreach($hero['items'] as $key3 => $value3) {
                                $last_item_insert  = $item_collection->find(array('tooltipParams'=> $value3['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                $last_item_tooltip = null;
                                foreach($last_item_insert as $item_key => $item_value) {
                                    $last_item_tooltip = $item_value['tooltipParams'];
                                }

                                if($full_insert || is_null($last_item_tooltip)) {
                                    $hero_items[$hero['id']][$key3] = $value3['tooltipParams'];
                                 } else {
                                    echo "Item record already exists, left out<br>";
                                 }
                            }

                            if(($hero_items) > 0) {
                                foreach($hero_items as $hero_id => $items) {
                                    if(is_array($items)) {
                                        foreach($items as $name => $tooltipParams) {
                                            $ITEM_DATA[$hero_id][$name] = $Diablo3->getItem($tooltipParams); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                            $calls++;
                                        }
                                    }
                                }
                            }

                            if(isset($ITEM_DATA[$hero['id']])) {
                                foreach($ITEM_DATA[$hero['id']] as $key4 => $value4) {
                                    if(is_array($value4)) {
                                        $value4['_itemType'] = $key4;
                                        $item_collection->insert($value4);
                                        $session_ids[] = $value4['_id'];
                                        echo "Hero Items Inserted<br>";
                                    } else {
                                        echo $value4;
                                    }

                                    if(is_array($value4['gems']) && count($value4['gems']) > 0) {
                                        foreach($value4['gems'] as $gems) {
                                            $last_gem_insert  = $item_collection->find(array('tooltipParams'=> $gems['item']['tooltipParams']))->sort(array('_id' => -1))->limit(1);
                                            $last_gem_tooltip = null;
                                            foreach($last_gem_insert as $item_key => $item_value) {
                                                $last_gem_tooltip = $item_value['tooltipParams'];
                                            }

                                            if(is_null($last_gem_tooltip)) {
                                                $GEMS_DATA = $Diablo3->getItem($gems['item']['tooltipParams']); // Item Data 'item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD'  (string)
                                                $item_collection->insert($GEMS_DATA);
                                                $session_ids[] = $GEMS_DATA['_id'];
                                                echo "Item Gem Inserted<br>";
                                            } else {
                                                echo 'Gem Already Exists No Insert<br>';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        echo $hero.'<br>';
                    }
                }
            } else {
                echo $user.' Region: '.$region.' -- '.$CAREER_DATA;
            }
            $time2       = microtime();
            $time2       = explode(' ', $time2);
            $time2       = $time2[1] + $time2[0];
            $finish2     = $time2;
            $total_time2 = round(($finish2 - $start2), 4);
            $total_time2 = secondsToTime($total_time2);

            echo '<br><b>'.$user.' Region: '.$region.'</b> finished in '.$total_time2.' seconds.'."<br><br>";
        }
    }
} else {
    echo 'No Accounts to load data for.';
}

//var_dump($db->lastError());

$time       = microtime();
$time       = explode(' ', $time);
$time       = $time[1] + $time[0];
$finish     = $time;
$total_time = round(($finish - $start), 4);
$total_time = secondsToTime($total_time);

$APP_STAT = array('date_time'           => date('m/d/Y h:i:s a', time()),
                  'number_of_accounts'  => count($user_profiles),
                  'accounts'            => $user_profiles,
                  'number_of_calls'     => $calls,
                  'run_time'            => $total_time,
                  'session_inserts_ids' => $session_ids,
                  'source'              => $source);

$app_stats_collection->insert($APP_STAT);

echo '<br>Proccess finished in '.$total_time.' seconds.'."<br>Total Calls: ".$calls;
