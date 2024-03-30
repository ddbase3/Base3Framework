<?php

error_reporting(1023);
ini_set("display_errors", 1);

include("lib/PHPMailer/PHPMailer.php");
include("lib/PHPMailer/Exception.php");
include("lib/PHPMailer/SMTP.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer;
$mail->isSMTP();
$mail->SMTPDebug = 2;
$mail->Host = "wp12064820.mailout.server-he.de";
$mail->Port = 25;
// $mail->SMTPSecure = "STARTTLS"; // "TLS";
$mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);
$mail->SMTPAuth = true;
$mail->Username = "wp12064820-0000_info";
$mail->Password = "wslbnib!";
$mail->setFrom("info@base3-cms.de", "BASE3 CMS");
$mail->addAddress("danieldahme@gmx.de", "Daniel Dahme");
$mail->Subject = "E-Mail von BASE3";
$mail->msgHTML('<p>Das ist ein <strong>Test</strong></p>');
$mail->AltBody = "Das ist ein Test";

if ($mail->send())
	echo "Gesendet. " . date("H:i:s");
else
	echo "Fehler: " . $mail->ErrorInfo;
