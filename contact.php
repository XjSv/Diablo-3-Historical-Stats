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
<title>Diablo 3 Historical Statistics - Contact</title>
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
            <a class="brand" href="#">Diablo 3 Historical Statistics</a>
            <div class="nav-collapse collapse">
                <ul class="nav">
                  <li class="">
                    <a class="" href="/index.php">Home</a>
                  </li>
                  <li class="">
                    <a class="" href="/app_stats.php">App Stats</a>
                  </li>
                  <li class="">
                    <a class="" href="/change_log.php">Change Log</a>
                  </li>
                  <li class="active">
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
                Use this to contact me with any questions, bug, or feature requests.
            </div>
        </div>

        <div class="span9">
            <div class="row-fluid">
                <form method="post" name="contact-form" id="contact-form">
                    <fieldset>
                        <legend>Contact</legend>

                        <div class="control-group">
                            <label class="control-label" for="contact_name">Full Name</label>
                            <div class="controls">
                                <input type="text" name="contact_name" id="contact_name" class="input-large" placeholder="Name">
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="contact_email">Email Address</label>
                            <div class="controls">
                                <input type="text" name="contact_email" id="contact_email" class="input-large" placeholder="Email">
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="contact_subject">Subject</label>
                            <div class="controls">
                                <select name="contact_subject" id="contact_subject"  multiple="multiple">
                                    <option value="General Question">General Question</option>
                                    <option value="Bug Report">Bug Report</option>
                                    <option value="Feature Request">Feature Request</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="contact_message">Message</label>
                            <div class="controls">
                                <textarea name="contact_message" id="contact_message" rows="8" cols="15" class="contact-message" placeholder="Message"></textarea>
                            </div>
                        </div>

                        <input type="text" name="spam" id="spam" class="spam" value="">

                        <div class="form-actions">
                            <button id="contact_send_btn" type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </fieldset>
                </form>
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
<script src="js/jquery.validate.min.js"></script>
<script>
var statsChart;
$(document).ready(function() {
    $('#contact-form').validate({
        rules: {
          contact_name: {
            minlength: 2,
            required: true
          },
          contact_email: {
            required: true,
            email: true
          },
          contact_subject: {
              required: true
          },
          contact_message: {
            minlength: 2,
            required: true
          },
        },
        highlight: function(label) {
            $(label).closest('.control-group').addClass('error');
        },
        success: function(label) {
            if($('#spam').val() == "") {
                label.text('OK!').addClass('valid').closest('.control-group').addClass('success');
            }
        },
        submitHandler: function() {
            $.post("lib/send_email.php", $("#contact-form").serialize(),function(result){
                if(result == 'sent') {
                    $('#contact-form').remove().html('Thank You!');
                }
            });
        }
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
