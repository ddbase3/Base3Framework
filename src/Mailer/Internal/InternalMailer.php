<?php

namespace Mailer\Internal;

use Mailer\Api\IMailer;

class InternalMailer implements IMailer {

	// Implementation of IMailer

	public function isMail() { /* not needed */ }

	public function isSmtp() { /* not used */ }
	public function setSmtpAuth($smtpauth) { /* not used */ }

	public function setHost($host) { /* not used */ }
	public function setPort($port = 25) { /* not used */ }
	public function setCredentials($user, $pass) { /* not used */ }

	/* send an IMail object */
	public function send($mail) {
		$data = $mail->getData();
		mail(implode(", ", $data["to"]), $data["subject"], $data["text"]);
	}

}
