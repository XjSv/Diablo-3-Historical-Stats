<?php
session_start();
require_once('class/diablo3.api.class.php');
require_once('config/settings.php');
require_once('include/functions.php');

unregisterGlobals();
removeMagicQuotes();

$tooltipUrl = $_GET['tooltipUrl'];

if(isset($_SESSION['ITEM'][$tooltipUrl])) {
    $DATA_RETURN = $_SESSION['ITEM'][$tooltipUrl];
} else {
    $Diablo3                         = new Diablo3(BATTLENET_ACCOUNT, DEFAULT_SERVER, DEFAULT_LOCALE);
    $DATA_RETURN                     = $Diablo3->getSkillToolTip($tooltipUrl, false);
    $_SESSION['ITEM']['tooltipData'] = $DATA_RETURN;
    $_SESSION['ITEM']['tooltipUrl']  = $tooltipUrl;
}

echo $DATA_RETURN;
