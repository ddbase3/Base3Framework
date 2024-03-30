<?php

namespace Mailer\Full;

use Mailer\Api\IMailer;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Api\ICheck;

class FullMailer implements IMailer, ICheck {

	private $servicelocator;

	private $mailer = "smtp";
	private $smtpauth = true;
	private $host = "localhost";
	private $port = 25;
	private $user = "";
	private $pass = "";

	public $debug = "";

	public function __construct($cnf = null) {

		$this->servicelocator = \Base3\ServiceLocator::getInstance();

		if ($cnf == null) {
			$configuration = $this->servicelocator->get('configuration');
			if ($configuration != null ) $cnf = $configuration->get('mailer');
		}

		if ($cnf == null || !is_array($cnf)) return;
		if (isset($cnf["mailer"])) $this->mailer = $cnf["mailer"];
		if (isset($cnf["smtpauth"])) $this->smtpauth = $cnf["smtpauth"];
		if (isset($cnf["host"])) $this->host = $cnf["host"];
		if (isset($cnf["port"])) $this->port = $cnf["port"];
		if (isset($cnf["user"])) $this->user = $cnf["user"];
		if (isset($cnf["pass"])) $this->pass = $cnf["pass"];
	}

	// Implementation of IMailer

	public function isMail() {
		$this->mailer = "mail";
	}

	public function isSmtp() {
		$this->mailer = "smtp";
	}

	public function setSmtpAuth($smtpauth) {
		$this->smtpauth = !!$smtpauth;
	}

	public function setHost($host) {
		$this->host = $host;
	}

	public function setPort($port = 25) {
		$this->port = $port;
	}

	public function setCredentials($user, $pass) {
		$this->user = $user;
		$this->pass = $pass;
	}

	/* send an IMail object */
	public function send($mail) {
		$data = $mail->getData();

		$this->includeLib();

		ob_start();

		$phpmailer = new PHPMailer;
		$phpmailer->isSMTP();
		$phpmailer->SMTPDebug = 2;
		$phpmailer->Host = $this->host;
		$phpmailer->Port = $this->port;
		// $phpmailer->SMTPSecure = "STARTTLS"; // "TLS";
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
			        'verify_peer' => false,
			        'verify_peer_name' => false,
			        'allow_self_signed' => true
			)
		);
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $this->user;
		$phpmailer->Password = $this->pass;

		if ($data["from"] != null) $phpmailer->setFrom($data["from"]["email"], $data["from"]["name"]);
		if ($data["replyto"] != null) $phpmailer->AddReplyTo($data["replyto"]["email"], $data["replyto"]["name"]);
		foreach ($data["to"] as $to) $phpmailer->addAddress($to["email"], $to["name"]);
		foreach ($data["cc"] as $cc) $phpmailer->AddCC($cc["email"], $cc["name"]);
		foreach ($data["bcc"] as $bcc) $phpmailer->AddBCC($bcc["email"], $bcc["name"]);

		foreach ($data["attachmentstring"] as $attachmentstring) $phpmailer->AddStringAttachment($attachmentstring["string"], $attachmentstring["filename"]);
		foreach ($data["attachmentfile"] as $attachmentfile) $phpmailer->AddAttachment($attachmentfile["path"], $attachmentfile["filename"]);
		foreach ($data["embeddedstring"] as $embeddedstring) $phpmailer->addStringEmbeddedImage($embeddedstring["string"], $embeddedstring["cid"], $embeddedstring["filename"]);
		foreach ($data["embeddedfile"] as $embeddedfile) $phpmailer->addEmbeddedImage($embeddedfile["path"], $embeddedfile["cid"], $embeddedfile["filename"]);

		$phpmailer->Subject = $data["subject"];
		$phpmailer->msgHTML($data["html"]);
		$phpmailer->AltBody = $data["text"];
		$phpmailer->isHTML(strlen($data["html"]) > 0);

		$success = $phpmailer->send();
		echo $success ? "Gesendet. " . date("H:i:s") : "Fehler: " . $phpmailer->ErrorInfo;

		$this->debug = ob_get_contents();
		ob_end_clean();

		return $success;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('configuration') == null ? "Fail" : "Ok"
		);
	}

	// private methods

	private function includeLib() {

		$incdir = __DIR__ . DIRECTORY_SEPARATOR
			. ( version_compare(PHP_VERSION, '5.5.0') >= 0 ? "_PHPMailer_6.0.5_geq5.5" : "_PHPMailer_6.0.5_lt5.5" )
			. DIRECTORY_SEPARATOR;

		include($incdir . "PHPMailer.php");
		include($incdir . "Exception.php");
		include($incdir . "SMTP.php");
	}

}
