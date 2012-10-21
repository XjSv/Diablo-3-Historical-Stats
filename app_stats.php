<?php
session_start();
require_once('config/settings.php');
require_once('include/functions.php');

unregisterGlobals();
removeMagicQuotes();

$connection           = new Mongo();
$db                   = $connection->selectDB(PROD_DB);
$db->authenticate(PROD_DB_USER, PROD_DB_PASS);
$app_stats_collection = $db->selectCollection(APP_STATS_COLLECTION);
$app_stats_data       = $app_stats_collection->find()->sort(array('_id' => -1))->limit(APP_STATS_LIMIT);
$GOOGLE_ANALYTICS     = GOOGLE_ANALYTICS;

// Build User List
//
$app_stats = '<div id="stats_table"><table class="table table-condensed table-hover"><thead><th>Date Time</th><th>Source</th><th># of Accounts</th><th># of Calls</th><th>Run Time</th></thead>';
foreach($app_stats_data as $value) {
    $source     = ucfirst($value['source']);
    $app_stats .= "<tr><td>{$value['date_time']}</td><td>{$source}</td><td>{$value['number_of_accounts']}</td><td>{$value['number_of_calls']}</td><td>{$value['run_time']} seconds</td></tr>";
}
$app_stats .= '</table></div>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Diablo 3 Historical Statistics - App Statistics</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Diablo 3 Historical Statistics - App Statistics">
<meta name="author" content="Armando Tresova <xjsv24@gmail.com>">
<link href="css/bootstrap.css" rel="stylesheet">
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
            <a class="brand" href="#">Diablo 3 Historical Statistics</a>
            <div class="nav-collapse collapse">
                <ul class="nav">
                  <li class="">
                    <a class="" href="/index.php">Home</a>
                  </li>
                  <li class="active">
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
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row-fluid">
        <div class="span3">
            <div class="well">
                This area show a log of the data load process. Currently this happens manually until I create a scheduled job to run it on an interval.
            </div>
        </div>

        <div class="span9">
            <div class="row-fluid">
                <?=$app_stats?>
            </div>
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
<script>
$(document).ready(function() {
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
