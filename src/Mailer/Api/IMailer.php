<?php

namespace Mailer\Api;

interface IMailer {

	public function isMail();

	public function isSmtp();
	public function setSmtpAuth($smtpauth);

	public function setHost($host);
	public function setPort($port = 25);
	public function setCredentials($user, $pass);

	/* send an IMail object */
	public function send($mail);

}
