<?php
require_once('class/diablo3.api.class.php');
require_once('config/settings.php');
require_once('include/functions.php');

session_start();

$connection           = new Mongo();
$db                   = $connection->selectDB(PROD_DB);
$app_stats_collection = $db->selectCollection(APP_STATS_COLLECTION);
$app_stats_data       = $app_stats_collection->find()->sort(array('date_time' => -1))->limit(APP_STATS_LIMIT);

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
<title>Diablo 3 App Statistics</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Diablo 3 App Statistics">
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
            <a class="brand" href="#">Diablo 3 App Statistics</a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row-fluid">
        <div class="span3">
            <div class="well sidebar-nav">

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
        <p>&copy; Diablo 3 App Statistics</p>
    </footer>
</div>
<script src="js/jquery.min.js"></script>
<script>
$(document).ready(function() {
});
</script>
</body>
</html>
