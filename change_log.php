<?php
session_start();
require_once('config/settings.php');
require_once('include/functions.php');

unregisterGlobals();
removeMagicQuotes();

$GOOGLE_ANALYTICS = GOOGLE_ANALYTICS;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Diablo 3 Historical Statistics - Change Log</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Diablo 3 Historical Statistics - Change Log">
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
                  <li class="">
                    <a class="" href="/app_stats.php">App Stats</a>
                  </li>
                  <li class="active">
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
                This area shows the change log of the application.
            </div>
        </div>

        <div class="span9">
            <div class="row-fluid">
                <ul class="well">
                    <li>10/20/2012: Added seach/add battletag functionality, contact page, app stats and now the "Hero" list shows top 15 most viewed battletags. If your battletag does not exist in the db it will be added to the queue.</li>
                </ul>
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
