<?php
require_once('../config/settings.php');

if(isset($_POST['spam']) && $_POST['spam'] == '') {
    $email_to   = DEFAULT_EMAIL;
    $name       = $_POST['contact_name'];
    $email      = $_POST['contact_email'];
    $subject    = $_POST['contact_subject'];
    $message    = $_POST['contact_message'];
    $MAILER_URL = MAILER_URL;

    $subject_formated = 'Diablo 3 Historical Stats Contact Form: '.$subject;

    $message_formated = "
    <html>
    <head>
      <title>Diablo 3 Historical Stats Contact Form</title>
    </head>
    <body>
      <table>
	    <tr>
		    <td><b>Name:<b/> </td>
		    <td>$name</td>
	    </tr>
	    <tr>
		    <td><b>E-Mail:<b/> </td>
		    <td>$email</td>
	    </tr>
	    <tr>
		    <td><b>Subject:<b/> </td>
		    <td>$subject</td>
	    </tr>
	    <tr>
		    <td><b>Message:<b/> </td>
		    <td>$message</td>
	    </tr>
      </table>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Return-Path: $email_to\r\n";
    $headers .= "X-Mailer: $MAILER_URL (http://www.$MAILER_URL)";

    if(mail($email_to, $subject_formated, $message_formated, $headers)) {
	    echo 'sent';
    } else {
        echo 'failed';
    }
} else {
    echo 'failed';
}
