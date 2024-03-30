<?php

namespace Mailer;

use Mailer\Api\IMail;

class Mail implements IMail {

	private $data;

	public function __construct() {
		$this->data = array(
			"from" => null,
			"replyto" => null,
			"to" => array(),
			"cc" => array(),
			"bcc" => array(),
			"subject" => "",
			"text" => "",
			"html" => "",
			"attachmentstring" => array(),
			"attachmentfile" => array(),
			"embeddedstring" => array(),
			"embeddedfile" => array()
		);
	}

	// Implementation of IMail

	public function setFrom($email, $name = null) {
		$this->data["from"] = array("email" => $email, "name" => $name);
	}

	public function setReplyTo($email, $name = null) {
		$this->data["replyto"] = array("email" => $email, "name" => $name);
	}

	public function addTo($email, $name = null) {
		$this->data["to"][] = array("email" => $email, "name" => $name);
	}

	public function addCc($email, $name = null) {
		$this->data["cc"][] = array("email" => $email, "name" => $name);
	}

	public function addBcc($email, $name = null) {
		$this->data["bcc"][] = array("email" => $email, "name" => $name);
	}

	public function setSubject($subject) {
		$this->data["subject"] = $subject;
	}

	public function setText($text) {
		$this->data["text"] = $text;
	}

	public function setHtml($html) {
		$this->data["html"] = $html;
	}

	public function addAttachmentString($string, $filename) {
		$this->data["attachmentstring"][] = array(
			"string" => $string,
			"filename" => $filename
		);
	}

	public function addAttachmentFile($path, $filename = null) {
		$this->data["attachmentfile"][] = array(
			"path" => $path,
			"filename" => $filename
		);
	}

	public function addEmbeddedImageString($string, $cid, $filename = null) {
		$this->data["embeddedstring"][] = array(
			"string" => $string,
			"cid" => $cid,
			"filename" => $filename
		);
	}

	public function addEmbeddedImageFile($path, $cid, $filename = null) {
		$this->data["embeddedfile"][] = array(
			"path" => $path,
			"cid" => $cid,
			"filename" => $filename
		);
	}

	public function getData() {
		return $this->data;
	}

}
